<?php

namespace App\Http\Middleware\User;
use Illuminate\Support\Facades\Session;
use Closure;

class Base
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
        if (!$request->session()->has('user'))
            return response()->json([
                'status' => 0,
                'message' => '请先登录',
                'next_api' => '/api/user/login',
                'method' => 'post'
            ], 403);

        $user_id = $request->session()->get('user');
        $request->attributes->add(compact('user_id'));

        return $next($request);
    }
}
