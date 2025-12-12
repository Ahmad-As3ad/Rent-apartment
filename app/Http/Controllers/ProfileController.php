<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{

    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'date_of_birth' => 'required|date|before:-18 years',
                'id_card_picture' => 'required|image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();

            if ($request->hasFile('profile_picture')) {
                $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');
                $validated['profile_picture'] = $profilePath;

                if ($user->profile_picture) {
                    Storage::delete('public/' . $user->profile_picture);
                }
            }

            if ($request->hasFile('id_card_picture')) {
                $idCardPath = $request->file('id_card_picture')->store('id_cards', 'public');
                $validated['id_card_picture'] = $idCardPath;

                if ($user->id_card_picture) {
                    Storage::delete('public/' . $user->id_card_picture);
                }
            }

            $validated['profile_completed_at'] = now();

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
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
                        'is_approved' => $user->status === 'approved'
                    ]
                ]
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating profile'
            ], 500);
        }
    }
}
