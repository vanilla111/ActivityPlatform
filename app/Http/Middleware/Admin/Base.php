<?php

namespace App\Http\Middleware\Admin;

use Closure;
use JWTAuth;

class Base
{
    /**
     * 超级管理员后台基础中间件，验证身份及权限
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //解析token
        $token_info = JWTAuth::authenticate(JWTAuth::getToken());

        if ($token_info['account'] != "hywx_web")
            return response()->json([
                'status' => 0,
                'message' => '非法访问'
            ], 403);

        $request->attributes->add(compact('token_info'));

        return $next($request);
    }
}
