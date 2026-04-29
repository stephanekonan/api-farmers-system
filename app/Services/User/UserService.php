<?php

namespace App\Services\User;

use App\Contracts\Services\UserServiceInterface;
use App\Enums\RoleEnum;
use App\Exceptions\User\AuthorizationException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function create(array $data, ?User $creator = null): User
    {
        $this->validateRoleHierarchy($creator, $data['role']);

        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => RoleEnum::from($data['role']),
            'created_by' => $creator?->id,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(User $user, array $data, ?User $updater = null): User
    {
        if (isset($data['role'])) {
            $this->validateRoleHierarchy($updater, $data['role'], $user);
        }

        $updateData = [];

        foreach (['username', 'pseudo', 'email', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $updateData['role'] = RoleEnum::from($data['role']);
        }

        $user->update($updateData);

        return $user->fresh();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function getSubordinates(User $supervisor): Collection
    {
        return $supervisor->subordinates()->get();
    }

    public function activate(User $user): void
    {
        $user->update(['is_active' => true]);
    }

    public function deactivate(User $user): void
    {
        $user->update(['is_active' => false]);
    }

    public function delete(User $user, ?User $deleter = null): void
    {
        $this->validateRoleHierarchy($deleter, null, $user);

        if ($user->subordinates()->exists()) {
            throw ValidationException::withMessages([
                'user' => 'Impossible de supprimer un utilisateur qui a des subordonnés.',
            ]);
        }

        $user->delete();
    }

    public function getUsersByRole(RoleEnum $role, ?User $requester = null): Collection
    {
        $query = User::where('role', $role);

        if ($requester && !$requester->isAdmin()) {
            $query->where('created_by', $requester->id);
        }

        return $query->get();
    }

    protected function validateRoleHierarchy(?User $requester, ?string $targetRole, ?User $targetUser = null): void
    {
        if (!$requester) {
            return;
        }

        if ($requester->isOperator()) {
            throw AuthorizationException::cannotManageUsers();
        }

        if ($targetRole) {
            $targetRoleEnum = RoleEnum::from($targetRole);

            if ($requester->isSupervisor() && $targetRoleEnum === RoleEnum::ADMIN) {
                throw AuthorizationException::cannotManageAdmins();
            }

            if ($requester->isSupervisor() && $targetUser && $targetUser->created_by !== $requester->id) {
                throw AuthorizationException::cannotModifyOtherOperators();
            }
        }

        if ($targetUser && $targetUser->isAdmin() && !$requester->isAdmin()) {
            throw AuthorizationException::cannotModifyAdmins();
        }
    }
}
