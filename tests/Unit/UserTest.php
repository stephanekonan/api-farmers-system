<?php

namespace Tests\Unit;

use App\Models\User;
use App\Enums\RoleEnum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ]);
    }

    public function test_user_fillable_attributes(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => RoleEnum::OPERATOR,
            'is_active' => true,
        ];

        $user = User::create($userData);

        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals(RoleEnum::OPERATOR, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_user_hidden_attributes(): void
    {
        $user = User::factory()->create(['password' => 'plaintext']);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    public function test_password_is_hashed(): void
    {
        $plaintextPassword = 'password123';
        $user = User::factory()->create(['password' => $plaintextPassword]);

        $this->assertNotEquals($plaintextPassword, $user->password);
        $this->assertTrue(Hash::check($plaintextPassword, $user->password));
    }

    public function test_role_casting(): void
    {
        $user = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->assertInstanceOf(RoleEnum::class, $user->role);
        $this->assertEquals(RoleEnum::ADMIN, $user->role);
    }

    public function test_is_active_casting(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    public function test_is_admin_method(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $supervisor = User::factory()->create(['role' => RoleEnum::SUPERVISOR]);
        $operator = User::factory()->create(['role' => RoleEnum::OPERATOR]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($supervisor->isAdmin());
        $this->assertFalse($operator->isAdmin());
    }

    public function test_is_supervisor_method(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $supervisor = User::factory()->create(['role' => RoleEnum::SUPERVISOR]);
        $operator = User::factory()->create(['role' => RoleEnum::OPERATOR]);

        $this->assertFalse($admin->isSupervisor());
        $this->assertTrue($supervisor->isSupervisor());
        $this->assertFalse($operator->isSupervisor());
    }

    public function test_is_operator_method(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $supervisor = User::factory()->create(['role' => RoleEnum::SUPERVISOR]);
        $operator = User::factory()->create(['role' => RoleEnum::OPERATOR]);

        $this->assertFalse($admin->isOperator());
        $this->assertFalse($supervisor->isOperator());
        $this->assertTrue($operator->isOperator());
    }

    public function test_can_manage_users_method(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $supervisor = User::factory()->create(['role' => RoleEnum::SUPERVISOR]);
        $operator = User::factory()->create(['role' => RoleEnum::OPERATOR]);

        $this->assertTrue($admin->canManageUsers());
        $this->assertTrue($supervisor->canManageUsers());
        $this->assertFalse($operator->canManageUsers());
    }

    public function test_token_abilities_for_admin(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $abilities = $admin->tokenAbilities();

        $this->assertIsArray($abilities);
        $this->assertEquals(['*'], $abilities);
    }

    public function test_token_abilities_for_supervisor(): void
    {
        $supervisor = User::factory()->create(['role' => RoleEnum::SUPERVISOR]);
        $abilities = $supervisor->tokenAbilities();

        $this->assertIsArray($abilities);
        $this->assertContains('users:read', $abilities);
        $this->assertContains('users:write', $abilities);
        $this->assertContains('products:read', $abilities);
        $this->assertContains('products:write', $abilities);
        $this->assertContains('categories:read', $abilities);
        $this->assertContains('categories:write', $abilities);
        $this->assertContains('farmers:read', $abilities);
        $this->assertContains('farmers:write', $abilities);
        $this->assertContains('transactions:read', $abilities);
        $this->assertContains('transactions:write', $abilities);
        $this->assertContains('repayments:read', $abilities);
        $this->assertContains('repayments:write', $abilities);
        $this->assertContains('debts:read', $abilities);
        $this->assertNotContains('debts:write', $abilities);
    }

    public function test_token_abilities_for_operator(): void
    {
        $operator = User::factory()->create(['role' => RoleEnum::OPERATOR]);
        $abilities = $operator->tokenAbilities();

        $this->assertIsArray($abilities);
        $this->assertContains('products:read', $abilities);
        $this->assertNotContains('products:write', $abilities);
        $this->assertContains('categories:read', $abilities);
        $this->assertNotContains('categories:write', $abilities);
        $this->assertContains('farmers:read', $abilities);
        $this->assertContains('farmers:write', $abilities);
        $this->assertContains('transactions:read', $abilities);
        $this->assertContains('transactions:write', $abilities);
        $this->assertContains('repayments:read', $abilities);
        $this->assertContains('repayments:write', $abilities);
        $this->assertContains('debts:read', $abilities);
        $this->assertNotContains('debts:write', $abilities);
        $this->assertNotContains('users:read', $abilities);
        $this->assertNotContains('users:write', $abilities);
    }

    public function test_creator_relationship(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create(['created_by' => $creator->id]);

        $this->assertInstanceOf(User::class, $user->creator);
        $this->assertEquals($creator->id, $user->creator->id);
    }

    public function test_subordinates_relationship(): void
    {
        $supervisor = User::factory()->create();
        $subordinate1 = User::factory()->create(['created_by' => $supervisor->id]);
        $subordinate2 = User::factory()->create(['created_by' => $supervisor->id]);

        $subordinates = $supervisor->subordinates;

        $this->assertCount(2, $subordinates);
        $this->assertContains($subordinate1->id, $subordinates->pluck('id'));
        $this->assertContains($subordinate2->id, $subordinates->pluck('id'));
    }

    public function test_transactions_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->transactions());
    }

    public function test_repayments_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->repayments());
    }
}
