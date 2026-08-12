<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_guest_is_redirected_from_the_authenticated_homepage()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
