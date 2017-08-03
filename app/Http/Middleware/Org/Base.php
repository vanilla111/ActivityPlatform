<?php

namespace App\Http\Middleware\Org;

use App\Models\ActAdmin;
use Closure;
use JWTAuth;

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
        //解析token
        $token_info = JWTAuth::authenticate(JWTAuth::getToken());

        if ($token_info['pid'] != 0)
            return response()->json([
                'status' => 0,
                'message' => '非法访问'
            ], 403);

        $request->attributes->add(compact('token_info'));
        return $next($request);
    }
}
