<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Change from assertStatus(200) to assertRedirect() or assertStatus(302)
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
