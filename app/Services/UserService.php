<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
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

    public function getUsersByRole(RoleEnum $role, ?User $requester = null): \Illuminate\Database\Eloquent\Collection
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
            throw ValidationException::withMessages([
                'role' => 'Les opérateurs ne peuvent pas gérer les utilisateurs.',
            ]);
        }

        if ($targetRole) {
            $targetRoleEnum = RoleEnum::from($targetRole);

            if ($requester->isSupervisor() && $targetRoleEnum === RoleEnum::ADMIN) {
                throw ValidationException::withMessages([
                    'role' => 'Les superviseurs ne peuvent pas créer ou modifier d\'administrateurs.',
                ]);
            }

            if ($requester->isSupervisor() && $targetUser && $targetUser->created_by !== $requester->id) {
                throw ValidationException::withMessages([
                    'user' => 'Vous ne pouvez modifier que vos propres opérateurs.',
                ]);
            }
        }

        if ($targetUser && $targetUser->isAdmin() && !$requester->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Seul un administrateur peut modifier un autre administrateur.',
            ]);
        }
    }
}
