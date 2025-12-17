<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If no user, return unauthorized
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Check if profile is complete
        if (!$user->isProfileComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your profile first',
                'profile_incomplete' => true,
                'required_fields' => [
                    'first_name',
                    'last_name',
                    'profile_picture',
                    'date_of_birth',
                    'id_card_picture'
                ]
            ], 403);
        }

        return $next($request);
    }
}
