<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API routes, never redirect - return null to send 401/403 instead
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }
        
        // For web routes, try to redirect to login if it exists
        try {
            return route('login');
        } catch (\Exception $e) {
            // If login route doesn't exist, return null to send 401/403
            return null;
        }
    }
}
