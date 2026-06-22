<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
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



        $user = User::where(
            'email',
            $data['email']
        )->first();



        if (!$user || !Hash::check(
            $data['password'],
            $user->password
        )) {
                        Log::warning('Failed login attempt', [
                'email' => $data['email'],
                'ip' => $request->ip(),
                'device_name' => $data['device_name'] ?? 'unknown',
            ]);


            throw ValidationException::withMessages([

                'email' => [
                    'Invalid email or password.'
                ]

            ]);

        }

        if (!$user->is_active) {
            Log::warning('Login attempt on disabled account', [
                'email' => $data['email'],
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([

                'email' => [
                    'Your account has been disabled.'
                ]

            ]);

        }



        // Create Sanctum token for mobile app
        $token = $user
            ->createToken(
                $data['device_name']
            )
            ->plainTextToken;



        return response()->json([

            'message' => 'Login successful',

            'token' => $token,

            'user' => new UserResource($user)

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

            'user' => new UserResource(
                $request->user()
            )

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

            'message' => 'Logged out successfully'

        ], 200);

    }

}
