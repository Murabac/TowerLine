<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertGuest();
        $response->assertSee(__('app.demo.quick_login'), false);
        $response->assertSee(__('app.demo.admin'), false);
        $response->assertSee(__('app.demo.section_head'), false);
        $response->assertSee(__('app.demo.coordinator_maroodi'), false);
        $response->assertSee(__('app.demo.inspector_maroodi'), false);
        $response->assertSee(__('app.demo.director'), false);
        $response->assertSee(__('app.demo.dg'), false);
        $response->assertDontSee('inspector.west@mocit.local', false);
        $response->assertDontSee('viewer.telesom@mocit.local', false);
    }

    public function test_operator_viewers_cannot_log_in_while_the_role_is_disabled(): void
    {
        $operator = \App\Models\Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);
        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $operator->id,
        ]);

        $this->from('/login')->post('/login', [
            'email' => $viewer->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_demo_quick_login_signs_in_an_admin(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->post('/login', [
            'email' => 'admin@mocit.local',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame('admin', auth()->user()->role);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('map', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
