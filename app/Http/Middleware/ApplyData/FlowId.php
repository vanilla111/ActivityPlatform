<?php

namespace App\Http\Middleware\ApplyData;

use App\Models\FlowInfo;
use Closure;

class FlowId
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
        $flow_id = $request->get('flow_id');
        if (empty($flow_id))
            return response()->json(['status' => 0, 'message' => '参数flow_id必须'], 400);

        $flow_m = new FlowInfo();
        $flow_info = $flow_m->getFlowInfo(['flow_id' => $flow_id], '*');
        if (!$flow_info)
            return response()->json([
                'status' => 0,
                'message' => 'flow_id参数错误'
            ], 400);
        $act_key = $flow_info['activity_key'];
        $request->attributes->add(compact('act_key'));
        $request->attributes->add(compact('flow_info'));

        return $next($request);
    }
}
