<?php

namespace App\Http\Middleware\ApplyData;

use App\Models\FlowInfo;
use Closure;

class CheckAuth
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
        $act_key = $request->get('act_key');
        $flow_id = $request->get('flow_id');

        $flow_m = new FlowInfo();
        if (!$flow = $flow_m->getFlowInfo(['flow_id' => $flow_id], 'activity_key'))
            return response()->json([
                'status' => 0,
                'message' => '非法操作1'
            ], 403);

        if ($flow['activity_key'] != $act_key)
            return response()->json([
                'status' => 0,
                'message' => '非法操作2'
            ], 403);

        return $next($request);
    }
}
