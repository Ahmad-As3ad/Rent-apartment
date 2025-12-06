<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendOTPRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Models\Verification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    public function sendOTP(SendOTPRequest $request)
    {
        $validated = $request->validated();
        $phoneNumber = $validated['phone_number'];

        $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        Verification::where('phone_number', $phoneNumber)->delete();

        Verification::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(15)
        ]);

        Log::info("OTP لـ {$phoneNumber}: {$otp}");

        return response()->json([
            'success' => true,
            'message' => ' The code has been sent successfully'
        ]);
    }

    public function verifyOTP(VerifyOTPRequest $request)
    {
        $validated = $request->validated();
        $phoneNumber = $validated['phone_number'];
        $otpCode = $validated['otp_code'];

        $otpRecord = Verification::where('phone_number', $phoneNumber)
            ->where('otp_code', $otpCode)
            ->first();

        if (!$otpRecord || $otpRecord->expires_at < now() || $otpRecord->verified_at) {
            return response()->json([
                'success' => false,
                'message' => ' The verification code is invalid or expired'
            ], 401);
        }

        $otpRecord->update(['verified_at' => now()]);

        $user = User::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'phone_verified_at' => now(),
                'user_type' => $request->user_type ?? 'tenant'
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => ' You have been logged in successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_approved' => $user->isApproved()
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => ' You have successfully logged out'
        ]);
    }
}
