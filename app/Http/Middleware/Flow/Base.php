<?php

namespace App\Http\Middleware\Flow;

use Closure;
use App\Models\FlowInfo;
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
        $flow_id = $request->segment(4);
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $auth_id = $auth['sub'];
        $act_key = $request->get('act_key');

        $flow_m = new FlowInfo();
        if (!$flow_info = $flow_m->getFlowInfo(['flow_id' => $flow_id], ['activity_key']))
            return response()->json(['status' => 0, 'message' => '该流程不存在'], 404);

        if (!empty($act_key))
            if ($act_key != $flow_info['activity_key'])
                return response()->json(['status' => 0, 'message' => '该流程不属于act_key所代表的活动'], 403);

        //先找到这个活动
        $act_m = new ActDesign();
        $need = ['author_id', 'status', 'end_time'];
        if (!$act = $act_m->getActInfo(['activity_id' => $flow_info['activity_key']], $need))
            return response()->json(['status' => 0, 'message' => '该流程所属活动不存在'], 404);
        //再确认是否是对应用户操作
        if ($act['author_id'] != $auth_id)
            return response()->json(['status' => 0, 'message' => '非法操作'], 403);
        //检查活动状态
        if ($act['status'] < 0)
            return response()->json(['status' => 0, 'message' => '该流程所属活动已删除'], 403);
        //检查活动是否已经结束
        if (strtotime($act['end_time']) < time())
            return response()->json(['status' => 0, 'message' => '活动已结束，无法修改流程'], 403);

        return $next($request);
    }
}
