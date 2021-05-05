<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsBanned
{
    use ApiResponser;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->is_banned == 1) {
            return $this->error('Banned.', 401);
        }

        else {
            return $next($request);
        }
    }
}
