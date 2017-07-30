<?php

namespace App\Http\Middleware\Flow;

use Closure;
use App\Models\Sms;
use JWTAuth;

class Variables
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
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $auth_id = $auth['sub'];
        $request->attributes->add(compact('auth_id'));

        if (!empty($info['flow_name']))
            if (utf8_strlen($info['flow_name']) > 40)
                array_push($error_mes, '流程名长度不能超过40个字');

        if (!empty($info['type']))
            if (!is_numeric($info['type']) || $info['type'] < 0)
                array_push($error_mes, 'type参数错误,请设置为非负整数');

        if (isset($info['sms_temp_id']))
            if (!is_numeric($info['sms_temp_id']))
                array_push($error_mes, 'sms_temp_id参数有误');

        if (!empty($error_mes))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        //对比数据库检查sms_temp_id
        $sms_m = new Sms();
        $sms_temp = $sms_m->getSmsInfo($info['sms_temp_id'], $auth_id, '*');
        if (!$sms_temp)
            return response()->json([
                'status' => 0,
                'message' => '无该短信模板或其已失效'
            ], 400);

        return $next($request);
    }
}
