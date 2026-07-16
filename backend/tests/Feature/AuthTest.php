<?php

namespace Tests\Feature;

use App\Domains\Identity\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        // Sanctum only starts a session for requests it recognizes as
        // first-party frontend traffic (matched against SANCTUM_STATEFUL_DOMAINS).
        $this->withHeader('Origin', 'http://localhost:5188');
    }

    public function test_active_user_can_sign_in_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'active@example.test',
            'password_hash' => Hash::make('CorrectHorse1!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'active@example.test',
            'password' => 'CorrectHorse1!',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.email', 'active@example.test');
    }

    public function test_sign_in_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'active@example.test',
            'password_hash' => Hash::make('CorrectHorse1!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'active@example.test',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_sign_in_fails_for_inactive_user(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.test',
            'password_hash' => Hash::make('CorrectHorse1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'CorrectHorse1!',
        ]);

        $response->assertStatus(401);
    }

    public function test_sign_in_fails_for_unknown_account_with_generic_message(): void
    {
        $knownResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.test',
            'password' => 'whatever123',
        ]);

        User::factory()->create([
            'email' => 'someone@example.test',
            'password_hash' => Hash::make('CorrectHorse1!'),
        ]);

        $wrongPasswordResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'someone@example.test',
            'password' => 'wrong-password',
        ]);

        $this->assertSame(
            $knownResponse->json('error.message'),
            $wrongPasswordResponse->json('error.message'),
        );
    }

    public function test_unauthenticated_request_to_me_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_authenticated_user_can_read_session_and_sign_out(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('CorrectHorse1!'),
        ]);

        $this->actingAs($user, 'web');

        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.user.id', (string) $user->id);

        $logoutResponse = $this->postJson('/api/v1/auth/logout');
        $logoutResponse->assertOk();
        $logoutResponse->assertJsonPath('data.loggedOut', true);
    }
}
