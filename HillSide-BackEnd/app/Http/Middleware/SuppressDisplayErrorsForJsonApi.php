<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parandalon që njoftimet PHP (Notice) të përzihen me JSON për rrugët /api.
 */
class SuppressDisplayErrorsForJsonApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('ini_set')) {
            @ini_set('display_errors', '0');
        }

        return $next($request);
    }
}
