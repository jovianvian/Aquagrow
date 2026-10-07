<?php

namespace App\Console\Commands;

use App\Enums\FarmMembershipRole;
use App\Enums\PondOperationalStatus;
use App\Enums\PondType;
use App\Models\CultivationCycle;
use App\Models\Farm;
use App\Models\FarmMembership;
use App\Models\FishSpecies;
use App\Models\Location;
use App\Models\Pond;
use App\Models\User;
use App\Services\CultivationCycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProvisionAcceptanceFixture extends Command
{
    protected $signature = 'acceptance:provision
        {--database= : Exact isolated acceptance database name}
        {--marker= : Short fixture marker, for example SEP07}';

    protected $description = 'Provision an isolated, synthetic browser-acceptance fixture';

    public function handle(CultivationCycleService $cycles): int
    {
        $database = (string) $this->option('database');
        $marker = strtoupper((string) ($this->option('marker') ?: 'SEP07'));

        if (! $this->guardTarget($database, $marker)) {
            return self::FAILURE;
        }

        $password = $this->passwordForNewUsers($marker);
        if ($password === null) {
            return self::FAILURE;
        }

        return DB::transaction(function () use ($cycles, $marker, $password): int {
            $suffix = strtolower($marker);
            $ownerA = $this->user($marker, 'OWNER-A', $password);
            $operatorA = $this->user($marker, 'OPERATOR-A', $password);
            $ownerB = $this->user($marker, 'OWNER-B', $password);
            $farmA = $this->farm($marker, 'A');
            $farmB = $this->farm($marker, 'B');

            $this->membership($farmA, $ownerA, FarmMembershipRole::FARM_OWNER);
            $this->membership($farmA, $operatorA, FarmMembershipRole::POND_OPERATOR);
            $this->membership($farmB, $ownerB, FarmMembershipRole::FARM_OWNER);
            $this->assertUserOnlyOnFarm($ownerA, $farmA);
            $this->assertUserOnlyOnFarm($operatorA, $farmA);
            $this->assertUserOnlyOnFarm($ownerB, $farmB);

            $locationA = $this->location($farmA, $marker, 'A');
            $locationB = $this->location($farmB, $marker, 'B');
            $pondPlanned = $this->pond($farmA, $locationA, $marker, 'PLANNED');
            $pondActive = $this->pond($farmA, $locationA, $marker, 'ACTIVE');
            $pondCancelled = $this->pond($farmA, $locationA, $marker, 'CANCELLED');
            $pondInactiveSpecies = $this->pond($farmA, $locationA, $marker, 'HISTORICAL');
            $this->pond($farmB, $locationB, $marker, 'B-ONLY');

            $activeSpecies = $this->species($marker, 'ACTIVE', true);
            $historicalSpecies = $this->species($marker, 'HISTORICAL', true);

            $planned = $this->cycle($cycles, $ownerA, $farmA, $pondPlanned, $activeSpecies, "{$suffix}-PLANNED");
            $active = $this->cycle($cycles, $ownerA, $farmA, $pondActive, $activeSpecies, "{$suffix}-ACTIVE");
            $cancelled = $this->cycle($cycles, $ownerA, $farmA, $pondCancelled, $activeSpecies, "{$suffix}-CANCELLED");
            $historical = $this->cycle($cycles, $ownerA, $farmA, $pondInactiveSpecies, $historicalSpecies, "{$suffix}-HISTORICAL");

            $historicalSpecies->update(['is_active' => false]);

            $this->line('Acceptance fixture provisioned or reused. Passwords were not printed or stored by this command.');
            $this->table(['label', 'email', 'role', 'farm'], [
                ['OWNER-A', $ownerA->email, 'FARM_OWNER', $farmA->name],
                ['OPERATOR-A', $operatorA->email, 'POND_OPERATOR', $farmA->name],
                ['OWNER-B', $ownerB->email, 'FARM_OWNER', $farmB->name],
            ]);
            $this->table(['record', 'batch', 'url'], [
                ['PLANNED', $planned->batch_code, url("/cultivation-cycles/{$planned->id}")],
                ['ACTIVE', $active->batch_code, url("/cultivation-cycles/{$active->id}")],
                ['CANCELLED', $cancelled->batch_code, url("/cultivation-cycles/{$cancelled->id}")],
                ['INACTIVE SPECIES', $historical->batch_code, url("/cultivation-cycles/{$historical->id}")],
            ]);

            return self::SUCCESS;
        });
    }

    private function guardTarget(string $database, string $marker): bool
    {
        $connection = DB::connection();
        $configured = (string) $connection->getDatabaseName();
        $environment = (string) config('app.env');
        $driver = (string) $connection->getDriverName();

        if ($environment === 'production') {
            $this->error('Refusing acceptance fixture provisioning in production.');

            return false;
        }
        if (in_array($database, ['aquagrow', 'aquagrow_testing'], true)) {
            $this->error('Refusing the development and automated-testing databases.');

            return false;
        }
        if ($database === '' || $database !== $configured) {
            $this->error('The --database value must exactly match the active connection database.');

            return false;
        }
        if (! preg_match('/(^|[_-])acceptance([_-]|$)/i', $database)) {
            $this->error('Acceptance database name must contain an acceptance marker.');

            return false;
        }
        if ($driver !== 'pgsql') {
            $this->error('Acceptance fixture provisioning requires the PostgreSQL connection.');

            return false;
        }
        if (! preg_match('/^[A-Z0-9-]{3,16}$/', $marker)) {
            $this->error('Marker must contain only 3-16 uppercase letters, digits, or hyphens.');

            return false;
        }
        foreach (['users', 'farms', 'farm_memberships', 'locations', 'ponds', 'fish_species', 'cultivation_cycles'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Required table is missing: {$table}.");

                return false;
            }
        }

        foreach ([new User, new Farm, new FarmMembership, new Location, new Pond, new FishSpecies, new CultivationCycle] as $model) {
            $modelConnection = $model->getConnection();
            if ($modelConnection->getDriverName() !== $driver || $modelConnection->getDatabaseName() !== $configured) {
                $this->error('Refusing inconsistent model database connections.');

                return false;
            }
        }

        return true;
    }

    private function passwordForNewUsers(string $marker): ?string
    {
        $emails = collect(['OWNER-A', 'OPERATOR-A', 'OWNER-B'])
            ->map(fn (string $label): array => [$this->fixtureEmail($marker, $label), $this->legacyEmail($marker, $label)]);

        $needsPassword = $emails->contains(fn (array $identities): bool => ! User::query()->whereIn('email', array_filter($identities))->exists());
        if (! $needsPassword) {
            return '';
        }

        $password = $this->secret('Password for new synthetic users (not displayed)');
        if (! is_string($password) || strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return null;
        }

        return $password;
    }

    private function user(string $marker, string $label, string $password): User
    {
        $email = $this->fixtureEmail($marker, $label);
        $name = $this->fixtureName($marker, $label);
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $legacyEmail = $this->legacyEmail($marker, $label);
            if (($legacyEmail !== null && User::query()->where('email', $legacyEmail)->exists()) || ! $this->provesRenamedFixtureUser($user, $marker, $label) || $user->name !== $name) {
                throw new \RuntimeException('Refusing an existing user that is not owned by this fixture.');
            }

            return $user;
        }

        $legacyEmail = $this->legacyEmail($marker, $label);
        if ($legacyEmail !== null && ($legacyUser = User::query()->where('email', $legacyEmail)->first())) {
            if ($legacyUser->name !== "Acceptance {$marker} {$label}" || ! $this->provesLegacyFixtureUser($legacyUser, $marker, $label)) {
                throw new \RuntimeException('Refusing an existing user that is not owned by this fixture.');
            }

            $legacyUser->name = $name;
            $legacyUser->email = $email;
            $legacyUser->save();

            return $legacyUser;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->email_verified_at = Carbon::now();
        $user->save();

        return $user;
    }

    private function fixtureEmail(string $marker, string $label): string
    {
        if (strtoupper($marker) === 'SEP09') {
            return match ($label) {
                'OWNER-A' => 'owner1@example.test',
                'OPERATOR-A' => 'operator1@example.test',
                'OWNER-B' => 'owner2@example.test',
                default => throw new \InvalidArgumentException('Unknown fixture user label.'),
            };
        }

        return 'acceptance-'.strtolower($label).'-'.strtolower($marker).'@example.test';
    }

    private function fixtureName(string $marker, string $label): string
    {
        if (strtoupper($marker) === 'SEP09') {
            return match ($label) {
                'OWNER-A' => 'Owner 1',
                'OPERATOR-A' => 'Operator 1',
                'OWNER-B' => 'Owner 2',
                default => throw new \InvalidArgumentException('Unknown fixture user label.'),
            };
        }

        return "Acceptance {$marker} {$label}";
    }

    private function legacyEmail(string $marker, string $label): ?string
    {
        return strtoupper($marker) === 'SEP09'
            ? 'acceptance-'.strtolower($label).'-'.strtolower($marker).'@example.test'
            : null;
    }

    private function provesLegacyFixtureUser(User $user, string $marker, string $label): bool
    {
        return $user->farmMemberships()->whereHas('farm', function ($query) use ($marker, $label): void {
            $farmLabel = $label === 'OWNER-B' ? 'b' : 'a';
            $query->where('slug', 'acceptance-'.strtolower($marker).'-farm-'.$farmLabel);
        })->exists();
    }

    private function provesRenamedFixtureUser(User $user, string $marker, string $label): bool
    {
        return $this->legacyEmail($marker, $label) === null || $this->provesLegacyFixtureUser($user, $marker, $label);
    }

    private function farm(string $marker, string $label): Farm
    {
        $slug = 'acceptance-'.strtolower($marker).'-farm-'.strtolower($label);
        $name = "Acceptance {$marker} Farm {$label}";
        $farm = Farm::query()->where('slug', $slug)->first();
        if ($farm && ($farm->name !== $name || $farm->timezone !== 'Asia/Jakarta' || $farm->currency !== 'IDR')) {
            throw new \RuntimeException('Refusing an existing farm that is not owned by this fixture.');
        }
        $farm ??= Farm::query()->create(['name' => $name, 'slug' => $slug, 'timezone' => 'Asia/Jakarta', 'currency' => 'IDR']);
        $this->assertFixtureOnly($farm, $marker);

        return $farm;
    }

    private function membership(Farm $farm, User $user, FarmMembershipRole $role): void
    {
        $membership = FarmMembership::query()->firstOrNew(['farm_id' => $farm->id, 'user_id' => $user->id]);
        if ($membership->exists) {
            if ($membership->getRawOriginal('role') !== $role->value) {
                throw new \RuntimeException('Refusing to change an existing fixture membership role.');
            }

            return;
        }
        $membership->role = $role->value;
        $membership->save();
    }

    private function assertUserOnlyOnFarm(User $user, Farm $farm): void
    {
        if ($user->farmMemberships()->where('farm_id', '!=', $farm->id)->exists()) {
            throw new \RuntimeException('Refusing a fixture user with memberships outside its fixture farm.');
        }
    }

    private function location(Farm $farm, string $marker, string $label): Location
    {
        $slug = 'acceptance-'.strtolower($marker).'-'.strtolower($label);
        $name = "Acceptance {$marker} Location {$label}";
        $location = Location::query()->where(['farm_id' => $farm->id, 'slug' => $slug])->first();
        if ($location && $location->name !== $name) {
            throw new \RuntimeException('Refusing an existing location that is not owned by this fixture.');
        }

        return $location ?? Location::query()->create(['farm_id' => $farm->id, 'name' => $name, 'slug' => $slug]);
    }

    private function pond(Farm $farm, Location $location, string $marker, string $label): Pond
    {
        $code = 'AC-'.strtoupper($marker).'-'.strtoupper($label);
        $pond = Pond::query()->where(['farm_id' => $farm->id, 'code' => $code])->first();
        if ($pond && ($pond->location_id !== $location->id || $pond->name !== "Acceptance {$marker} Pond {$label}" || $pond->getRawOriginal('type') !== PondType::EARTHEN->value)) {
            throw new \RuntimeException('Refusing an existing pond that is not owned by this fixture.');
        }

        return $pond ?? Pond::query()->create(['farm_id' => $farm->id, 'location_id' => $location->id, 'name' => "Acceptance {$marker} Pond {$label}", 'code' => $code, 'type' => PondType::EARTHEN, 'operational_status' => PondOperationalStatus::AVAILABLE]);
    }

    private function species(string $marker, string $label, bool $active): FishSpecies
    {
        $code = 'AC-'.strtoupper($marker).'-'.strtoupper($label);
        $name = "Acceptance {$marker} Species {$label}";
        $species = FishSpecies::query()->where('code', $code)->first();
        if ($species && $species->common_name !== $name) {
            throw new \RuntimeException('Refusing an existing species that is not owned by this fixture.');
        }
        if ($species && $label !== 'HISTORICAL' && $species->is_active !== $active) {
            throw new \RuntimeException('Refusing to reactivate or overwrite an existing fixture species.');
        }

        return $species ?? FishSpecies::query()->create(['code' => $code, 'common_name' => $name, 'is_active' => $active]);
    }

    private function cycle(CultivationCycleService $service, User $owner, Farm $farm, Pond $pond, FishSpecies $species, string $batch): CultivationCycle
    {
        $batch = strtoupper($batch);
        $existing = CultivationCycle::query()->where('farm_id', $farm->id)->where('batch_code', $batch)->first();
        if ($existing) {
            if ($existing->pond_id !== $pond->id || $existing->fish_species_id !== $species->id) {
                throw new \RuntimeException('Refusing an existing cycle that is not owned by this fixture.');
            }

            return $existing;
        }

        $cycle = $service->createPlanned($owner, $farm, ['pond_id' => $pond->id, 'fish_species_id' => $species->id, 'batch_code' => $batch]);
        if (str_ends_with($batch, '-ACTIVE') || str_ends_with($batch, '-HISTORICAL')) {
            return $service->start($cycle, ['stocked_at' => now()->subHour()->toIso8601String(), 'initial_fish_count' => 1000, 'initial_average_weight_g' => '2.5']);
        }
        if (str_ends_with($batch, '-CANCELLED')) {
            $service->cancel($cycle);

            return $cycle->refresh();
        }

        return $cycle;
    }

    private function assertFixtureOnly(Farm $farm, string $marker): void
    {
        $allowedEmails = strtoupper($marker) === 'SEP09'
            ? ['owner1@example.test', 'operator1@example.test', 'owner2@example.test']
            : [];

        if ($farm->members()->where('email', 'not like', 'acceptance-%')->whereNotIn('email', $allowedEmails)->exists()) {
            throw new \RuntimeException('Refusing to modify a farm with non-fixture members.');
        }
    }
}
