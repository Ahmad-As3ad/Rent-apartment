<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $reservation = Reservation::create([
            'apartment_id' => $validated['apartment_id'],
            'tenant_id' => $request->user()->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation request created',
            'data' => $reservation
        ]);
    }
    public function approve(Request $request, $id)
    {
        $owner = $request->user();

        return DB::transaction(function () use ($id, $owner) {
            $reservation = Reservation::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($reservation->apartment->owner_id !== $owner->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if (!in_array($reservation->status, ['pending', 'modified_pending'])) {
                return response()->json(['success' => false, 'message' => 'Invalid reservation status'], 422);
            }

            $overlap = Reservation::overlap(
                $reservation->apartment_id,
                $reservation->start_date,
                $reservation->end_date
            )->exists();

            if ($overlap) {
                return response()->json(['success' => false, 'message' => 'Overlapping reservation exists'], 409);
            }

            $reservation->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservation approved',
                'data' => $reservation
            ]);
        });
    }
    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::where('id', $id)
            ->where('tenant_id', $request->user()->id)
            ->firstOrFail();

        if ($reservation->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only approved reservations can be canceled'], 422);
        }

        $reservation->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Reservation canceled']);
    }
    public function modify(Request $request, $id)
    {
        $reservation = Reservation::where('id', $id)
            ->where('tenant_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'new_start_date' => 'required|date|after:now',
            'new_end_date'   => 'required|date|after:new_start_date',
            'notes'          => 'nullable|string|max:500',
        ]);

        if ($reservation->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only approved reservations can be modified'], 422);
        }

        if ($reservation->status === 'modified_pending') {
            return response()->json(['success' => false, 'message' => 'A modification request is already pending'], 409);
        }

        $reservation->update([
            'status'              => 'modified_pending',
            'new_start_date'      => $validated['new_start_date'],
            'new_end_date'        => $validated['new_end_date'],
            'modified_requested_at' => now(),
            'notes'               => $validated['notes'] ?? $reservation->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Modification request submitted and pending owner approval',
            'data'    => ['reservation' => $reservation],
        ]);
    }
    public function myReservations(Request $request)
    {
        $query = Reservation::where('tenant_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->where('start_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('end_date', '<=', $request->to_date);
        }

        $reservations = $query->orderBy('start_date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }
    public function apartmentReservations(Request $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        if ($apartment->owner_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = Reservation::where('apartment_id', $id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->where('start_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('end_date', '<=', $request->to_date);
        }

        $reservations = $query->orderBy('start_date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }


public function reject(Request $request, $id)
{
    $owner = $request->user();

    return DB::transaction(function () use ($id, $owner, $request) {
        $reservation = Reservation::where('id', $id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($reservation->apartment->owner_id !== $owner->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($reservation->status, ['pending','modified_pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not in rejectable state'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reservation->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
            'notes'       => $validated['reason'] ?? $reservation->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation rejected successfully',
            'data'    => ['reservation' => $reservation],
        ]);
    });
}

}
