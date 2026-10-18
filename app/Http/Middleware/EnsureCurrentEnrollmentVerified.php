<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentEnrollmentVerified
{
    /**
     * Restrict enrollment-gated student services until the student's
     * enrollment has been verified for the current academic term.
     *
     * Legacy students (no verification records) keep their existing
     * access so current workflows are not interrupted.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStudent() && ! $user->hasVerifiedEnrollmentForCurrentTerm()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Your enrollment verification is still pending. '
                    .'Student services requiring enrollment will be available once '
                    .config('academic.current_year').' '.config('academic.current_semester')
                    .' semester enrollment is verified by OSAS.');
        }

        return $next($request);
    }
}
