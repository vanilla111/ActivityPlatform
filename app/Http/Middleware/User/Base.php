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
        $user_id = $request->session()->has('user') ? $request->session()->get('user') : $request->get('user_id');
        $stu_code = $request->session()->has('stu_code') ? $request->session()->get('stu_code') : $request->get('stu_code');
        if (empty($user_id) && empty($stu_code))
            return response()->json([
                'status' => 0,
                'message' => '请先登录',
                'next_api' => '/api/user/login',
                'method' => 'post'
            ], 403);

        $request->attributes->add(compact('user_id'));
        $request->attributes->add(compact('stu_code'));
        return $next($request);
    }
}
