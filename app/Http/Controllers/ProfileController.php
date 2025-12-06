<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
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
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_approved' => $user->isApproved()
                ]
            ]
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validatedData = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $validatedData['profile_picture'] = $profilePath;

            if ($user->profile_picture) {
                Storage::delete('public/' . $user->profile_picture);
            }
        }

        if ($request->hasFile('id_card_picture')) {
            $idCardPath = $request->file('id_card_picture')->store('id_cards', 'public');
            $validatedData['id_card_picture'] = $idCardPath;

            if ($user->id_card_picture) {
                Storage::delete('public/' . $user->id_card_picture);
            }
        }

        $validatedData['profile_completed_at'] = now();
        $user->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
                    'date_of_birth' => $user->date_of_birth,
                    'id_card_picture' => $user->id_card_picture ? url('storage/' . $user->id_card_picture) : null,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                    'is_profile_complete' => true,
                    'is_approved' => $user->isApproved()
                ]
            ]
        ]);
    }
}
