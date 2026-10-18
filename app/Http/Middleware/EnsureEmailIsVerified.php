<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Redirect authenticated users with an unverified email to the
     * OTP verification page. Verification and logout routes are exempt
     * to prevent redirect loops.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail() && ! $request->routeIs('verification.*', 'logout')) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
