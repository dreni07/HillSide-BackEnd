<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MetaOAuthController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        $baseUrl = config('services.meta.oauth_url');

        $redirectUrl = $baseUrl . '?state=' . urlencode($token);

        return redirect()->away($redirectUrl);
    }
}

