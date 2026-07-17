<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for managing student authentication and tokens.
 */
class AuthController extends Controller
{
    /**
     * Mobile Registration
     *
     * Registers a mobile user using the same public account types as web signup.
     *
     * @bodyParam name string required Full name. Example: Amina Yusuf
     * @bodyParam email string required Email address. Example: amina@example.com
     * @bodyParam password string required Password. Example: password123
     * @bodyParam password_confirmation string required Matching password. Example: password123
     * @bodyParam account_type string required One of student, guardian, school, community. Example: student
     * @bodyParam device_name string required Device name. Example: Tecno Spark 10
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $accountType = $data['account_type'];

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'account_type' => $accountType,
            'trial_ends_at' => $accountType === 'guardian' ? null : now()->addHours(48),
        ]);

        event(new Registered($user));

        $token = $user
            ->createToken($data['device_name'])
            ->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'has_completed_onboarding' => $user->has_completed_onboarding ?? false,
            ],
        ], 201);
    }

    /**
     * Mobile Login
     *
     * Authenticates student and returns Sanctum token.
     *
     * @bodyParam email string required Student email. Example: student@futureacademy.com
     * @bodyParam password string required Password. Example: password123
     * @bodyParam device_name string required Device name. Example: Tecno Spark 10
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            Log::warning('Failed login attempt', [
                'email' => $data['email'],
                'ip' => $request->ip(),
                'device_name' => $data['device_name'] ?? 'unknown',
            ]);

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (! $user->is_active) {
            Log::warning('Login attempt on disabled account', [
                'email' => $data['email'],
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Your account has been disabled.'],
            ]);
        }

        $token = $user
            ->createToken($data['device_name'])
            ->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'has_completed_onboarding' => $user->has_completed_onboarding ?? false,
            ],
        ], 200);
    }

    /**
     * Get Current User Profile
     *
     * Returns authenticated student profile.
     *
     * @authenticated
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * Logout
     *
     * Delete current mobile access token.
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $request
            ->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}
