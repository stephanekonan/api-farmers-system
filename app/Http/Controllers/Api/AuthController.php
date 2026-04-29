<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            deviceName: $request->input('device_name', 'unknown'),
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'role' => $result['user']->role->value,
            ],
        ]);
    }


    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }


    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Tous les appareils ont été déconnectés.']);
    }


    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
        ]);
    }


    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $result = $this->authService->issueToken(
            user: $user,
            deviceName: $request->input('device_name', 'refresh'),
            ip: $request->ip(),
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
        ]);
    }


    public function sessions(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'device_name' => $t->device_name,
                'ip_address' => $t->ip_address,
                'last_used_at' => $t->last_used_at,
                'created_at' => $t->created_at,
                'is_current' => $t->id === $request->user()->currentAccessToken()->id,
            ]);

        return response()->json(['sessions' => $tokens]);
    }


    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()
            ->tokens()
            ->where('id', $tokenId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Session introuvable.'], 404);
        }

        return response()->json(['message' => 'Session révoquée.']);
    }
}