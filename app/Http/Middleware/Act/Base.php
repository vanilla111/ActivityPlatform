<?php

namespace App\Http\Middleware\Act;

use Closure;
use JWTAuth;
use App\Models\ActDesign;

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
        //检查必须参数
        $act_key = $request->segment(3);

        //解析token
        $token_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $token_info['sub'];

        //检查是否有该活动
        if (!$res = ActDesign::where(['activity_id' => $act_key])->where('status', '>=', 0)->select()->first())
            return response()->json(['status' => 0, 'message' => '该活动不存在'], 404);

        //检查活动所有者是否与token一致
        $res = json_decode($res, true);
        if ($res['author_id'] != $author_id)
            return response()->json(['status' => 0, 'message' => '非法请求'], 401);

        //检查活动时效
        if (strtotime($res['end_time']) < time())
            return response()->json(['status' => 0, 'message' => '活动已结束，无法修改'], 403);

        return $next($request);
    }
}
