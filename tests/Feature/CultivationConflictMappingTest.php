<?php

namespace Tests\Feature;

use App\Services\CultivationCycleDatabaseConflictMapper;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CultivationConflictMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_database_exception_is_not_mapped(): void
    {
        $exception = new QueryException('pgsql', 'select 1', [], new \PDOException('unknown failure'));
        $this->assertNull(app(CultivationCycleDatabaseConflictMapper::class)->map($exception));
    }

    public function test_known_unique_constraint_maps_without_database_details(): void
    {
        $exception = new QueryException('pgsql', 'insert secret sql', ['secret-binding'], new \PDOException('duplicate key constraint cultivation_cycles_farm_id_batch_code_unique'));
        $exception->errorInfo = ['23505', '23505', 'duplicate key constraint cultivation_cycles_farm_id_batch_code_unique'];
        $mapped = app(CultivationCycleDatabaseConflictMapper::class)->map($exception);
        $this->assertNotNull($mapped);
        $this->assertSame('This batch code is already used in this farm.', $mapped->getMessage());
        $this->assertStringNotContainsString('23505', $mapped->getMessage());
        $this->assertStringNotContainsString('secret', $mapped->getMessage());
    }

    public function test_open_cycle_constraint_maps_to_safe_conflict(): void
    {
        $exception = new QueryException('pgsql', 'insert secret sql', [], new \PDOException('duplicate key constraint cultivation_cycles_one_open_per_pond'));
        $exception->errorInfo = ['23505', '23505', 'duplicate key constraint cultivation_cycles_one_open_per_pond'];
        $mapped = app(CultivationCycleDatabaseConflictMapper::class)->map($exception);
        $this->assertSame('This pond already has a planned or active cycle.', $mapped?->getMessage());
    }
}
