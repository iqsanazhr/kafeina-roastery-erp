<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CashierPinAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CashierPinAuthService $pinAuthService
    ) {}

    /**
     * User login with email and password -> returns Sanctum bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with(['role', 'branch'])->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pengguna dinonaktifkan.',
            ], 403);
        }

        $token = $user->createToken('kafeina-auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Quick cashier authentication via 6-digit numeric PIN with 5-strike lockout protection.
     */
    public function verifyCashierPin(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pin' => 'required|string|size:6',
        ]);

        $user = User::with(['role', 'branch'])->findOrFail($request->user_id);

        try {
            $isValid = $this->pinAuthService->verifyPin($user, $request->pin, $request->ip());

            if (! $isValid) {
                $failedAttempts = $this->pinAuthService->getFailedAttempts($user);
                $remainingAttempts = max(0, CashierPinAuthService::MAX_ATTEMPTS - $failedAttempts);

                return response()->json([
                    'status' => 'error',
                    'message' => 'PIN salah.',
                    'failed_attempts' => $failedAttempts,
                    'remaining_attempts' => $remainingAttempts,
                ], 401);
            }

            // Create temporary POS token for rapid switching
            $token = $user->createToken('kafeina-pos-pin-session')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Autentikasi PIN kasir berhasil.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'locked_out' => true,
                'remaining_seconds' => $this->pinAuthService->getRemainingLockoutSeconds($user),
            ], 429);
        }
    }

    /**
     * Set or update cashier PIN (6 digits).
     */
    public function setCashierPin(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pin' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        $user = User::findOrFail($request->user_id);
        $this->pinAuthService->setPin($user, $request->pin);

        return response()->json([
            'status' => 'success',
            'message' => "PIN 6-digit berhasil diatur untuk pengguna [{$user->name}].",
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()->load(['role', 'branch']),
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Token berhasil dicabut dan sesi telah keluar.',
        ]);
    }
}
