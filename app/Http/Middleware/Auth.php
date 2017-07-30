<?php

namespace App\Http\Middleware;

use Closure;

class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        config(['jwt.user' => 'App\Models\ActAdmin']);
        config(['auth.providers.users.model' => \App\Models\ActAdmin::class]);
        return $next($request);
    }
}
