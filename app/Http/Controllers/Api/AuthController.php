<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Services\AuthService;
use App\Http\Resources\Api\Auth\LoginResource;
use App\Http\Resources\Api\Auth\UserResource;
use App\Http\Resources\Api\Auth\SessionResource;
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

        return response()->json(LoginResource::make($result));
    }


    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user(), $request->user()->currentAccessToken()->id);

        return response()->json(['message' => 'Déconnexion réussie.']);
    }


    public function logoutAll(Request $request): JsonResponse
    {
        $count = $this->authService->logoutAll($request->user());

        return response()->json(['message' => "{$count} appareil(s) déconnecté(s)."]);
    }


    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(UserResource::make($request->user()));
    }


    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken(
            user: $request->user(),
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
        $sessions = $this->authService->getActiveSessions($request->user());
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $tokens = collect($sessions)->map(function ($session) use ($currentTokenId) {
            $session['is_current'] = $session['id'] === $currentTokenId;
            return $session;
        });

        return response()->json(['sessions' => SessionResource::collection($tokens)]);
    }


    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $success = $this->authService->revokeSession($request->user(), $tokenId);

        if (!$success) {
            return response()->json(['message' => 'Session introuvable.'], 404);
        }

        return response()->json(['message' => 'Session révoquée.']);
    }
}