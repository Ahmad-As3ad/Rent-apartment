<?php

namespace App\Http\Controllers;

use App\Models\ApartmentReview;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ApartmentReviewController extends Controller
{
    public function review(Request $request, $id)
{
    $reservation = Reservation::where('id', $id)
        ->where('tenant_id', $request->user()->id)
        ->firstOrFail();

    if ($reservation->status !== 'approved' || $reservation->end_date > now()) {
        return response()->json([
            'success' => false,
            'message' => 'You can only review after the reservation is completed'
        ], 422);
    }

    if (ApartmentReview::where('reservation_id', $reservation->id)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Reservation already reviewed'
        ], 409);
    }

    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:1000',
    ]);

    $review = ApartmentReview::create([
        'reservation_id' => $reservation->id,
        'apartment_id'   => $reservation->apartment_id,
        'tenant_id'      => $request->user()->id,
        'rating'         => $validated['rating'],
        'comment'        => $validated['comment'] ?? null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Review submitted successfully',
        'data'    => $review
    ]);
}

}
