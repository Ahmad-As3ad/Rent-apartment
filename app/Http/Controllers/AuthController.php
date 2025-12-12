<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string|digits:10|starts_with:09|unique:users',
                'user_type' => 'required|in:owner,tenant'
            ]);

            $user = User::create([
                'phone_number' => $validated['phone_number'],
                'user_type' => $validated['user_type'],
                'phone_verified_at' => now(),
                'status' => 'pending'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone_number' => $user->phone_number,
                        'user_type' => $user->user_type,
                        'status' => $user->status,
                        'is_profile_complete' => false,
                        'is_approved' => false
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration'
            ], 500);
        }
    }


    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string|digits:10|starts_with:09'
            ]);

            $user = User::where('phone_number', $validated['phone_number'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number not registered'
                ], 404);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone_number' => $user->phone_number,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
                        'date_of_birth' => $user->date_of_birth,
                        'user_type' => $user->user_type,
                        'status' => $user->status,
                        'is_profile_complete' => $user->profile_completed_at !== null,
                        'is_approved' => $user->status === 'approved'
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login'
            ], 500);
        }
    }


    public function logout(Request $request)
    {
        try {

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
                    'date_of_birth' => $user->date_of_birth,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                    'is_profile_complete' => $user->profile_completed_at !== null,
                    'is_approved' => $user->status === 'approved'
                ]
            ]
        ]);
    }
}
