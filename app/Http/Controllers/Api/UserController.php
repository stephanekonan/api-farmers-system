<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\User\UserServiceInterface;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\Api\User\UserSummaryResource;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserServiceInterface $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'role',
            'is_active',
            'created_by',
            'search',
            'start_date',
            'end_date'
        ]);

        $users = $this->userService->getUsersByRole($filters['role'] ?? null);

        return response()->json(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());

            return response()->json(UserResource::make($user), 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de l\'utilisateur.',
                'errors' => method_exists($request, 'errors') ? $request->errors() : null,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json(UserResource::make($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        try {
            $updatedUser = $this->userService->update($user, $request->validated());

            return response()->json(UserResource::make($updatedUser));

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'utilisateur.',
                'errors' => method_exists($request, 'errors') ? $request->errors() : null,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        try {
            $this->userService->delete($user);

            return response()->json(['message' => 'Utilisateur supprimé avec succès.']);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getUsersByRole(string $role): JsonResponse
    {
        try {
            $roleEnum = RoleEnum::from($role);
            $users = $this->userService->getUsersByRole($roleEnum);

            return response()->json(UserSummaryResource::collection($users));

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des utilisateurs.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'admins' => User::where('role', \App\Enums\RoleEnum::ADMIN)->count(),
                'supervisors' => User::where('role', \App\Enums\RoleEnum::SUPERVISOR)->count(),
                'operators' => User::where('role', \App\Enums\RoleEnum::OPERATOR)->count(),
            ];

            return response()->json($stats);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
