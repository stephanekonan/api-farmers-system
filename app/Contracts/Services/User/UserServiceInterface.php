<?php

namespace App\Contracts\Services\User;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    public function create(array $data, ?User $creator = null): User;

    public function update(User $user, array $data, ?User $updater = null): User;

    public function delete(User $user, ?User $deleter = null): void;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function getUsersByRole(RoleEnum $role, ?User $requester = null): Collection;

    public function getSubordinates(User $supervisor): Collection;

    public function activate(User $user): void;

    public function deactivate(User $user): void;
}
