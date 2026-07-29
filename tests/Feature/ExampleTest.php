<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_can_view_public_catalog_and_login_remains_available(): void
    {
        $catalog = $this->get('/');
        $login = $this->get('/login');

        $catalog->assertOk()
            ->assertSee('Nuestro catálogo')
            ->assertSee(route('login'));
        $login->assertOk();
    }
}
