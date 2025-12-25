<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to enter'
            ], 401);
        }

        if (!$this->isProfileComplete($user)) {
            return response()->json([
                'success' => false,
                'message' =>'You must complete your personal data first',
                'profile_incomplete' => true,
                'required_fields' => [
                    'first_name',
                    'last_name',
                    'date_of_birth'
                ]
            ], 403);
        }

        return $next($request);
    }


    private function isProfileComplete($user): bool
    {
        return $user->profile_completed_at !== null &&
               $user->first_name !== null &&
               $user->last_name !== null &&
               $user->date_of_birth !== null;
    }
}
