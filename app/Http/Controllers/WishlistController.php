<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Apartment;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function add(Request $request, $apartmentId)
    {
        $user = $request->user();

        $exists = Wishlist::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Already in wishlist'
            ], 409);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'apartment_id' => $apartmentId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist'
        ]);
    }

    public function remove(Request $request, $apartmentId)
    {
        $user = $request->user();

        Wishlist::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from wishlist'
        ]);
    }

    public function myWishlist(Request $request)
    {
        $user = $request->user();

        $apartments = Wishlist::where('user_id', $user->id)
            ->with(['apartment.primaryImage', 'apartment.owner'])
            ->paginate(10);

        $apartments->getCollection()->transform(function ($item) {
            return [
                'wishlist_id' => $item->id,
                'added_at' => $item->created_at,
                'apartment' => [
                    'id' => $item->apartment->id,
                    'title' => $item->apartment->title,
                    'price_per_night' => $item->apartment->price_per_night,
                    'city' => $item->apartment->city,
                    'is_available' => $item->apartment->is_available,
                    'primary_image' => $item->apartment->primaryImage->image_url ?? null,
                    'owner_name' => $item->apartment->owner->full_name ?? null
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $apartments
        ]);
    }

    public function bookFromWishlist(Request $request, $wishlistId)
    {
        $user = $request->user();

        $wishlist = Wishlist::where('id', $wishlistId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $reservation = \App\Models\Reservation::create([
            'apartment_id' => $wishlist->apartment_id,
            'tenant_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation created from wishlist',
            'data' => $reservation
        ]);
    }

    public function checkInWishlist(Request $request, $apartmentId)
    {
        $user = $request->user();

        $exists = Wishlist::where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->exists();

        return response()->json([
            'success' => true,
            'in_wishlist' => $exists
        ]);
    }
}
