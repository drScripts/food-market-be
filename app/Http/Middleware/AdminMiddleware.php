<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
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
        $user_login = $request->attributes->get('user');

        $user = User::find($user_login);

        if (!$user || $user->roles != 'admin') {
            return ResponseFormatter::error(null, 'Restricted Area', 'error', 403);
        }

        return $next($request);
    }
}
