<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Jwt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        $token = null;

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = trim($matches[1]);
        }

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $payload = Jwt::decodeAndVerify($token);
        $userId = is_array($payload) ? ($payload['sub'] ?? null) : null;

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Invalid token.'], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

