<?php

namespace App\Http\Middleware\Sms;

use Closure;
use App\Models\Sms;
use JWTAuth;

class Test
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
        $rec_num = $request->get('rec_num');
        if (empty($rec_num))
            return response()->json([
                'status' => 0,
                'message' => '请输入测试手机号码'
            ], 400);
        else
            if (!check_phoneNum($rec_num))
                return response()->json([
                    'status' => 0,
                    'message' => '请输入正确的手机号码'
                ], 400);

        $auth = JWTAuth::decode(JWTAuth::getToken());
        $auth_id = $auth['sub'];
        $info = $request->all();

        if (!empty($info['sms_temp_id'])) {
            $temp_id = $info['sms_temp_id'];
            $sms_m = new Sms();
            $need = '*';
            //找到该活动
            if (!$sms_info = $sms_m->getSmsInfo($temp_id, $need))
                return response()->json(['status' => 0, 'message' => '未找到选择的短信模版'], 404);
            if ($sms_info['author_id'] != $auth_id)
                return response()->json(['status' => 0, 'message' => '非法操作'], 403);
            //状态限制
            if ($sms_info['status'] <= 0)
                return response()->json(['status' => 0, 'message' => '选中短信模版已被删除'], 403);
            //如果不是使用的管理员提供的模版
            if (empty($sms_info['admin_temp_id'])) {
                //检查输入变量的键与模版中定义的变量
                $var = explode(',', $sms_info['SmsVariables']); //查找到的变量
                if (is_null($input_var = json_decode($info['SmsVariables'], true))){//传递的变量
                    return response()->json(['status' => 0, 'message' => '变量信息表须为json格式'], 400);
                }
                //检查是否含有未被定义的变量
                if (hasNotDefine($input_var, $var))
                    return response()->json(['status' => 0, 'message' => '含有未被事先定义的变量'], 400);
            } else {
                $admin_temp_id = $sms_info['admin_temp_id'];
                $request->attributes->add(compact('admin_temp_id'));
            }
        } elseif (!empty($info['SmsVariables']))
            return response()->json(['status' => 0, 'message' => '变量来源未知，缺少短信模版ID'], 400);

        $request->attributes->add(compact('sms_info'));

        return $next($request);
    }
}
