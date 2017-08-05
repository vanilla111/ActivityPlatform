<?php

namespace App\Http\Middleware\Sms;

use App\Models\ActAdmin;
use Closure;
use JWTAuth;

class Index
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
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth['sub'];

        $admin_info = ActAdmin::where('admin_id', $author_id)->select('pid')->first();
        if (!$admin_info)
            return response()->json([
                'status' => 0,
                'message' => '服务器发生错误'
            ], 500);

        $author_id_arr = [$author_id, $admin_info['pid']];
        $request->attributes->add(compact('author_id_arr'));
        return $next($request);
    }
}
