<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => ' Not authorized to enter'
            ], 401);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => ' Your account is under review by the administration'
            ], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => ' Your account has been rejected by the administrationYour account has been rejected by the administration
'
            ], 403);
        }

        if ($user->status === 'approved') {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => ' Your account status is unknown'
        ], 403);
    }
}
