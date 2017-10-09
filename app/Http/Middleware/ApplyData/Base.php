<?php

namespace App\Http\Middleware\ApplyData;

use Closure;
use App\Models\ActDesign;
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
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];
        $act_key = $request->get('act_key');

        if (!$res = (new ActDesign())->getActInfo(['activity_id' => $act_key], ['author_id', 'activity_name', 'enroll_flow']))
            return response()->json(['status' => 0, 'message' => '无此活动'], 404);
        else {
            $act_name = $res['activity_name'];
            $enroll_flow = $res['enroll_flow'];
            if ($res['author_id'] != $author_id)
                return response()->json(['status' => 0, 'message' => '非法请求'], 403);
        }

        $request->attributes->add(compact('enroll_flow'));
        $request->attributes->add(compact('act_name'));
        $request->attributes->add(compact('author_id'));

        return $next($request);
    }
}
