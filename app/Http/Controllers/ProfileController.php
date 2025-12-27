<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'date_of_birth' => 'required|date|before:-18 years',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'id_card_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();

            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'date_of_birth' => $validated['date_of_birth'],
                'profile_completed_at' => now()
            ]);

            if ($request->hasFile('profile_picture')) {
                $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');
                $user->update(['profile_picture' => $profilePath]);
            }

            if ($request->hasFile('id_card_picture')) {
                $idCardPath = $request->file('id_card_picture')->store('id_cards', 'public');
                $user->update(['id_card_picture' => $idCardPath]);
            }

            return response()->json([
                'success' => true,
                'message' => 'The profile has been completed successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone_number' => $user->phone_number,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'date_of_birth' => $user->date_of_birth,
                        'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
                        'id_card_picture' => $user->id_card_picture ? url('storage/' . $user->id_card_picture) : null,
                        'user_type' => $user->user_type,
                        'status' => $user->status,
                        'is_profile_complete' => true,
                        'is_approved' => $user->status === 'approved'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while completing the profile'
            ], 500);
        }
    }
}
