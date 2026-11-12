<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_beta_portal_links_to_the_valiant_shade_home_page(): void
    {
        $response = $this->get('/');

        $response->assertSee('BETA');
        $response->assertSee('href="/valiant-shade"', false);
    }
}
