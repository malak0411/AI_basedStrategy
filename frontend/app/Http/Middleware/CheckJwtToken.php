<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckJwtToken
{
    public function handle(Request $request, Closure $next)
    {
        // هل يوجد jwt_token في الجلسة؟
        if (!session('jwt_token')) {
            return redirect()->route('login')
                ->with('error', 'يجب تسجيل الدخول أولاً');
        }

        return $next($request);
    }
}
