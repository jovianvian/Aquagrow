<?php

namespace Tests\Feature;

use Tests\TestCase;

class AcceptanceFixtureCommandTest extends TestCase
{
    public function test_fixture_rejects_development_database(): void
    {
        $this->artisan('acceptance:provision', ['--database' => 'aquagrow', '--marker' => 'TEST01'])
            ->expectsOutputToContain('development')
            ->assertExitCode(1);
    }

    public function test_fixture_rejects_automated_testing_database(): void
    {
        $this->artisan('acceptance:provision', ['--database' => 'aquagrow_testing', '--marker' => 'TEST01'])
            ->expectsOutputToContain('development')
            ->assertExitCode(1);
    }

    public function test_fixture_requires_the_active_connection_to_be_the_acceptance_database(): void
    {
        $this->artisan('acceptance:provision', ['--database' => 'aquagrow_manual', '--marker' => 'TEST01'])
            ->expectsOutputToContain('exactly match')
            ->assertExitCode(1);
    }

    public function test_fixture_rejects_production_environment(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('acceptance:provision', ['--database' => 'aquagrow_acceptance', '--marker' => 'TEST01'])
            ->expectsOutputToContain('production')
            ->assertExitCode(1);
    }
}
