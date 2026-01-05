<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function initiatePayment(Request $request, $reservationId)
    {
        $user = $request->user();

        $reservation = Reservation::where('id', $reservationId)
            ->where('tenant_id', $user->id)
            ->firstOrFail();

        if ($reservation->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Reservation must be approved'
            ], 422);
        }

        $existingPayment = Payment::where('reservation_id', $reservationId)
            ->where('status', 'completed')
            ->exists();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed'
            ], 409);
        }

        $days = $reservation->start_date->diffInDays($reservation->end_date);
        $totalPrice = $days * $reservation->apartment->price_per_night;

        $payment = Payment::create([
            'reservation_id' => $reservationId,
            'payment_id' => 'PAY_' . Str::random(16),
            'amount' => $totalPrice,
            'status' => 'pending',
            'method' => $request->input('method', 'cash')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated',
            'data' => [
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'reservation_id' => $reservationId
            ]
        ]);
    }

    public function completePayment(Request $request, $paymentId)
    {
        $user = $request->user();

        $payment = Payment::where('payment_id', $paymentId)
            ->with('reservation')
            ->firstOrFail();

        if ($payment->reservation->tenant_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($payment->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed'
            ], 409);
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_data' => [
                'reference' => Str::random(20),
                'completed_at' => now()->toDateTimeString()
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully',
            'data' => [
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at
            ]
        ]);
    }

    public function paymentStatus($paymentId)
    {
        $payment = Payment::where('payment_id', $paymentId)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
                'paid_at' => $payment->paid_at
            ]
        ]);
    }

    public function myPayments(Request $request)
    {
        $user = $request->user();

        $payments = Payment::whereHas('reservation', function ($query) use ($user) {
            $query->where('tenant_id', $user->id);
        })->with(['reservation.apartment'])
          ->latest()
          ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    public function simulatePayment(Request $request, $paymentId)
    {
        $payment = Payment::where('payment_id', $paymentId)->firstOrFail();

        $success = $request->input('success', true);

        if ($success) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'transaction_data' => [
                    'simulated' => true,
                    'reference' => 'SIM_' . Str::random(15),
                    'completed_at' => now()->toDateTimeString()
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment simulated successfully'
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'transaction_data' => [
                    'simulated' => true,
                    'error' => 'Simulated payment failure'
                ]
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment simulation failed'
            ]);
        }
    }
}
