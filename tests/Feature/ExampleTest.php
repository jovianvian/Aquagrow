<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('AquaGrow')
            ->assertSee('A calmer way to run every cultivation cycle.')
            ->assertSee('Illustrative product preview')
            ->assertSee('How it works')
            ->assertSee(route('login'))
            ->assertSee(route('register'));
    }
}
