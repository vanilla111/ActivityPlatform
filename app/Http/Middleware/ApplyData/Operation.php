<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

class Operation
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
        $info = $request->all();

        $require = ['enroll_id', 'step', 'action', 'act_key'];
        foreach ($require as $key) {
            if (!empty($info[$key]))
                return response()->json(['status' => 0, 'message' => $key . '参数必须'], 400);
        }
        if (!is_numeric($info['step']) || $info['step'] <= 0)
            return response()->json(['status' => 0, 'message' => 'step参数错误']);
//        if (!empty($info['upgrade']) && !empty($info['degrade']))
//            return response()->json(['status' => 0, 'message' => 'upgrade、degrade参数冲突'], 400);
//        if (!empty($info['upgrade']) && strtolower($info['upgrade']) != 'false')
//            return response()->json(['status' => 0, 'message' => 'upgrade参数错误'], 400);
//        if (!empty($info['degrade']) && strtolower($info['degrade']) != 'true')
//            return response()->json(['status' => 0, 'message' => 'degrade参数错误'], 400);

        return $next($request);
    }
}
