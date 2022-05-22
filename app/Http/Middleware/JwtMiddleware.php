<?php

namespace App\Http\Middleware;

use App\Helpers\JwtHelpers;
use App\Helpers\ResponseFormatter;
use Closure;
use Illuminate\Http\Request;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $jwtToken = $request->header('Authorization');
        if (!$jwtToken) {
            return ResponseFormatter::error(null, "Missing Token!", 'Un Authorized', 401);
        }

        $jwtToken = explode("Bearer ", $jwtToken)[1];
        $jwt = new JwtHelpers(env("JWT_SECRET_APP"));

        $userVerified = $jwt->verifyToken($jwtToken);

        if (!$userVerified) {
            return ResponseFormatter::error(null, "Un Authorized!", 'Un Authorized', 401);
        }

        $request->attributes->add(['user' => $userVerified]);

        return $next($request);
    }
}
