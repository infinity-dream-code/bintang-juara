<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserHasRole
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->roles->isEmpty()) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
