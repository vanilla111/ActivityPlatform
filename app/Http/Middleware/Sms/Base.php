<?php

namespace App\Http\Middleware\Sms;

use Closure;
use App\Models\Sms;
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
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth['sub'];
        $temp_id = $request->segment(4);

        $sms_m = new Sms();
        if (!$res = $sms_m->getSmsInfo($temp_id, '','*'))
            return response()->json([
                'status' => 0,
                'message' => '该模版不存在'
            ], 404);

        if ($res['author_id'] != $author_id)
            return response()->json([
                'status' => 0,
                'message' => '非法操作'
            ], 403);

        $sms_temp_info = $res;
        $request->attributes->add(compact('sms_temp_info'));
        $request->attributes->add(compact('author_id'));

        return $next($request);
    }
}
