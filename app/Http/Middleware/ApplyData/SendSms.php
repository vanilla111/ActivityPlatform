<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

class SendSms
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
        $error_mes = [];

        $require = ['step', 'act_key'];
        foreach ($require as $key)
            if (empty($info[$key]))
                return response()->json(['status' => 0, 'message' => $key . '参数必须'], 400);

        if (!is_numeric($info['step']) || $info['step'] <= 0)
            array_push($error_mes, 'step参数应为正整数');

        if (empty($info['enroll_id']) && empty($info['all']))
            array_push($error_mes, '请选择要发送短信的对象');

        if (!empty($info['all']))
            if (strtolower($info['all']) != 'true')
                array_push($error_mes, 'all参数有误');

        if (!empty($info['use_correlation']))
            if (strtolower($info['use_correlation']) != 'true')
                array_push($error_mes, 'use_correlation参数有误');

        if (!empty($error_mes))
            return response()->json([
                'status' => 0,
                'message' => $error_mes
            ], 400);

        return $next($request);
    }
}
