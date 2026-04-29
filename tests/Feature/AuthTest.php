<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_with_valid_credentials(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'username',
                    'email',
                    'role',
                    'is_active',
                    'created_at',
                ],
                'token',
            ]);

        $this->assertAuthenticated();
        $this->assertEquals($user->id, $response->json('user.id'));
        $this->assertNotNull($response->json('token'));
    }

    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        $this->assertGuest();
    }

    public function test_login_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        $this->assertGuest();
    }

    public function test_login_with_inactive_user(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Account is inactive',
            ]);

        $this->assertGuest();
    }

    public function test_login_with_missing_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_missing_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_authenticated_user(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        $this->assertGuest();
    }

    public function test_logout_without_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_all_authenticated_user(): void
    {
        $user = $this->authenticateUser();

        // Create multiple tokens for the user
        $token1 = $user->createToken('test1')->plainTextToken;
        $token2 = $user->createToken('test2')->plainTextToken;

        $this->assertEquals(2, $user->tokens()->count());

        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All sessions logged out successfully',
            ]);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_logout_all_without_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(401);
    }

    public function test_get_current_authenticated_user(): void
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'username',
                'email',
                'role',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ]);
    }

    public function test_get_current_user_without_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_refresh_token(): void
    {
        $user = $this->authenticateUser();
        $originalToken = $user->currentAccessToken()->token;

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'username',
                    'email',
                    'role',
                    'is_active',
                ],
                'token',
            ]);

        $newToken = $response->json('token');
        $this->assertNotEquals($originalToken, $newToken);
        $this->assertNotNull($newToken);
    }

    public function test_refresh_without_authentication(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_get_user_sessions(): void
    {
        $user = $this->authenticateUser();

        // Create additional tokens
        $user->createToken('Web Session');
        $user->createToken('Mobile Session');

        $response = $this->getJson('/api/auth/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'abilities',
                    'last_used_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $sessions = $response->json();
        $this->assertGreaterThanOrEqual(1, count($sessions));
    }

    public function test_get_sessions_without_authentication(): void
    {
        $response = $this->getJson('/api/auth/sessions');

        $response->assertStatus(401);
    }

    public function test_revoke_session(): void
    {
        $user = $this->authenticateUser();

        // Create a specific token to revoke
        $token = $user->createToken('Test Session');
        $tokenId = $token->accessToken->id;

        $response = $this->deleteJson("/api/auth/revoke-session/{$tokenId}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session revoked successfully',
            ]);

        $this->assertNull($user->tokens()->find($tokenId));
    }

    public function test_revoke_nonexistent_session(): void
    {
        $user = $this->authenticateUser();

        $response = $this->deleteJson('/api/auth/revoke-session/999999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Session not found',
            ]);
    }

    public function test_revoke_session_without_authentication(): void
    {
        $response = $this->deleteJson('/api/auth/revoke-session/1');

        $response->assertStatus(401);
    }

    public function test_login_creates_token_with_correct_abilities(): void
    {
        $password = 'password123';
        $admin = User::factory()->create([
            'password' => Hash::make($password),
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => $password,
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');

        $this->assertNotNull($token);

        $tokenModel = $admin->tokens()->where('token', hash('sha256', $token))->first();
        $this->assertNotNull($tokenModel);
        $this->assertEquals(['*'], $tokenModel->abilities);
    }

    public function test_login_rate_limiting(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
            'is_active' => true,
        ]);

        $response = null;
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        $this->assertNotNull($response);
        
        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too many login attempts. Please try again later.',
            ]);
    }
}
