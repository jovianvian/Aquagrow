<?php

namespace Tests\Feature;

use App\Enums\CultivationCycleStatus;
use App\Enums\FarmMembershipRole;
use App\Enums\PondOperationalStatus;
use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\FishSpecies;
use App\Models\Location;
use App\Models\Pond;
use App\Models\User;
use App\Services\CultivationCycleService;
use App\Services\PondLifecycleService;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CultivationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_start_is_serialized_by_the_pond_lock(): void
    {
        [$farm, $pond, $cycle] = $this->cycleFixture();
        $this->assertTestingDatabase();
        $a = $this->secondConnection('concurrency_a');
        $b = $this->secondConnection('concurrency_b');

        try {
            $a->beginTransaction();
            $a->table('ponds')->where('id', $pond->id)->lockForUpdate()->first();
            $this->setDefaultConnection('concurrency_b', 400);
            $this->expectLockTimeout(fn () => app(CultivationCycleService::class)->start($cycle, $this->startData($farm)));
            $a->rollBack();
            $this->setDefaultConnection('pgsql');
            app(CultivationCycleService::class)->start($cycle->fresh(), $this->startData($farm));
            $this->assertSame(1, CultivationCycle::where('pond_id', $pond->id)->where('status', CultivationCycleStatus::ACTIVE->value)->count());
            $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'initial_fish_count' => 1000]);
        } finally {
            $this->cleanupConnections($a, $b);
        }
    }

    public function test_concurrent_open_cycle_creation_is_serialized_and_final_invariant_is_one(): void
    {
        [$farm, $pond] = $this->pondFixture();
        $this->assertTestingDatabase();
        $a = $this->secondConnection('concurrency_a');
        $b = $this->secondConnection('concurrency_b');
        $species = FishSpecies::factory()->create();
        try {
            $a->beginTransaction();
            $a->table('ponds')->where('id', $pond->id)->lockForUpdate()->first();
            $this->setDefaultConnection('concurrency_b', 400);
            $this->expectLockTimeout(fn () => app(CultivationCycleService::class)->createPlanned($this->userFor($farm), $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-02']));
            $a->rollBack();
            $this->setDefaultConnection('pgsql');
            app(CultivationCycleService::class)->createPlanned($this->userFor($farm), $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-01']);
            $this->assertSame(1, CultivationCycle::where('pond_id', $pond->id)->whereIn('status', ['PLANNED', 'ACTIVE'])->count());
        } finally {
            $this->cleanupConnections($a, $b);
        }
    }

    public function test_start_cannot_leave_maintenance_or_inactive_pond_active(): void
    {
        foreach ([PondOperationalStatus::MAINTENANCE, PondOperationalStatus::INACTIVE] as $status) {
            [$farm, $pond, $cycle] = $this->cycleFixture();
            $this->assertTestingDatabase();
            $a = $this->secondConnection('concurrency_a');
            $b = $this->secondConnection('concurrency_b');
            try {
                $a->beginTransaction();
                $a->table('ponds')->where('id', $pond->id)->lockForUpdate()->first();
                $this->setDefaultConnection('concurrency_b', 400);
                $this->expectLockTimeout(fn () => app(PondLifecycleService::class)->update($pond, ['operational_status' => $status->value]));
                $a->rollBack();
                $this->setDefaultConnection('pgsql');
                app(CultivationCycleService::class)->start($cycle->fresh(), $this->startData($farm));
                $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'ACTIVE']);
                $this->assertDatabaseHas('ponds', ['id' => $pond->id, 'operational_status' => 'AVAILABLE']);
            } finally {
                $this->cleanupConnections($a, $b);
            }
        }
    }

    public function test_start_and_location_reassignment_share_the_pond_lock(): void
    {
        [$farm, $pond, $cycle] = $this->cycleFixture();
        $otherLocation = Location::factory()->create(['farm_id' => $farm->id]);
        $a = $this->secondConnection('concurrency_a');
        $b = $this->secondConnection('concurrency_b');
        try {
            $a->beginTransaction();
            $a->table('ponds')->where('id', $pond->id)->lockForUpdate()->first();
            $this->setDefaultConnection('concurrency_b', 400);
            $this->expectLockTimeout(fn () => app(PondLifecycleService::class)->update($pond, ['location_id' => $otherLocation->id]));
            $a->rollBack();
            $this->setDefaultConnection('pgsql');
            app(CultivationCycleService::class)->start($cycle->fresh(), $this->startData($farm));
            $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'ACTIVE']);
            $this->assertDatabaseHas('ponds', ['id' => $pond->id, 'location_id' => $pond->location_id]);
        } finally {
            $this->cleanupConnections($a, $b);
        }
    }

    private function pondFixture(): array
    {
        $farm = Farm::factory()->create();
        $user = $this->userFor($farm);
        $location = Location::factory()->create(['farm_id' => $farm->id]);

        return [$farm, Pond::factory()->create(['farm_id' => $farm->id, 'location_id' => $location->id])];
    }

    private function cycleFixture(): array
    {
        [$farm, $pond] = $this->pondFixture();
        $species = FishSpecies::factory()->create(['is_active' => true]);
        $cycle = app(CultivationCycleService::class)->createPlanned($this->userFor($farm), $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => 'B-01']);

        return [$farm, $pond, $cycle];
    }

    private function userFor(Farm $farm): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        FarmMembership::factory()->create(['farm_id' => $farm->id, 'user_id' => $user->id, 'role' => FarmMembershipRole::FARM_OWNER]);

        return $user;
    }

    private function startData(Farm $farm): array
    {
        return ['stocked_at' => now()->subHour()->setTimezone($farm->timezone)->format('Y-m-d H:i'), 'initial_fish_count' => 1000, 'initial_average_weight_g' => '2.500'];
    }

    private function assertTestingDatabase(): void
    {
        $this->assertStringContainsString('testing', (string) DB::connection()->getDatabaseName());
    }

    private function secondConnection(string $name): Connection
    {
        config(['database.connections.'.$name => array_merge(config('database.connections.pgsql'), ['database' => DB::connection()->getDatabaseName()])]);
        $connection = DB::connection($name);
        $connection->statement('SET lock_timeout = \'400ms\'');

        return $connection;
    }

    private function setDefaultConnection(string $name, int $timeout = 400): void
    {
        DB::setDefaultConnection($name);
        DB::connection($name)->statement("SET lock_timeout = '{$timeout}ms'");
    }

    private function expectLockTimeout(\Closure $operation): void
    {
        try {
            $operation();
            $this->fail('Expected PostgreSQL lock timeout.');
        } catch (QueryException $exception) {
            $this->assertSame('55P03', (string) ($exception->errorInfo[0] ?? $exception->getCode()));
        }
    }

    private function cleanupConnections(Connection $a, Connection $b): void
    {
        DB::setDefaultConnection('pgsql');
        foreach ([$a, $b] as $connection) {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            } $connection->disconnect();
        } DB::purge('concurrency_a');
        DB::purge('concurrency_b');
    }
}
