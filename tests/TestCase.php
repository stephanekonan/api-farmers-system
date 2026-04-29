<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Enums\RoleEnum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => RoleEnum::ADMIN,
            'is_active' => true,
        ], $attributes));
    }

    protected function createSupervisorUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => RoleEnum::SUPERVISOR,
            'is_active' => true,
        ], $attributes));
    }

    protected function createOperatorUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => RoleEnum::OPERATOR,
            'is_active' => true,
        ], $attributes));
    }

    protected function authenticateUser(User $user = null): User
    {
        $user = $user ?: $this->createOperatorUser();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    protected function authenticateAdmin(): User
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'sanctum');
        return $admin;
    }

    protected function authenticateSupervisor(): User
    {
        $supervisor = $this->createSupervisorUser();
        $this->actingAs($supervisor, 'sanctum');
        return $supervisor;
    }

    protected function authenticateOperator(): User
    {
        $operator = $this->createOperatorUser();
        $this->actingAs($operator, 'sanctum');
        return $operator;
    }
}
