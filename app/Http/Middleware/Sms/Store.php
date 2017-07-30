<?php

namespace App\Http\Middleware\Sms;

use Closure;
use App\Models\AdminSmsTemp;

class Store
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
        //$variables = '{"name":"${full_name}","department":"\u7ea2\u5ca9\u7f51\u6821"}';
        $require = ['temp_name', 'admin_temp_id', 'content', 'variables'];
        $temp_info = $request->only($require);
        $error_mes = [];
        if (empty($temp_info['temp_name']))
            array_push($error_mes, '缺少模板名称');

        if (empty($temp_info['admin_temp_id']))
            array_push($error_mes, '缺少母模板id');

        if (!empty($error_mes))
            return response()->json([
                'status' => 0,
                'message' => $error_mes
            ], 400);

        $admin_temp_m = new AdminSmsTemp();
        $condition = [
            'admin_temp_id' => $temp_info['admin_temp_id']
        ];
        $need = ['sms_temp', 'dynamic_variables', 'sms_variables'];
        $admin_temp = $admin_temp_m->getSmsTemp($condition, $need);

        if (!$admin_temp)
            return response()->json(['status' => 0, 'message' => '该模版好像不存在，换一个看看吧'], 400);

        //前台传递的变量对
        $var = json_decode($temp_info['variables'], true);

        $sms_var = explode(',', $admin_temp['sms_variables']);
        if (count($var) != count($sms_var))
            return response()->json([
                'status' => 0,
                'message' => '变量数量不匹配'
            ], 400);
        foreach ($sms_var as $key => $value) {
            if (!isset($var[$value]))
                return response()->json([
                    'status' => 0,
                    'message' => '变量' . $value . '需要被赋值'
                ], 400);
        }

        foreach ($var as $k => $v) {
            $admin_temp['sms_temp'] = str_replace_first('***', $v, $admin_temp['sms_temp']);
        }

        $dynamic_var = unserialize($admin_temp['dynamic_variables']);
        //return $admin_temp['sms_temp'];
        //$admin_temp['sms_temp'] = '亲爱的${name}，恭喜您通过了考核，欢迎来到${department}一起学习工作。【红岩网校工作站】';
        if (!$dynamic_var || empty($dynamic_var)) {
            //若没有设置动态变量,跳过检查，视为静态短信模板
            $type = 0;
        } else {
            $flag = false;
            //若设置了动态变量，检查前台所给变量中是否使用了动态变量，即是否检查是否是动态短信
            $pattern = '/\${\w*}/';
            preg_match_all($pattern, $admin_temp['sms_temp'], $m);

            if (empty($m) || !$m)
                $flag = false;
            else {
                $dy_var = [];
                foreach ($m[0] as $k => $v) {
                    foreach ($dynamic_var as $i => $j) {
                        if (isset($dynamic_var[$i][$v])) {
                            $dy_var[$v] = substr($v, 2, -1);
                            $flag = true;
                            break;
                        }
                    }
                }
            }

            $type = $flag ? 1 : 0;
        }

        $request->attributes->add(compact('var'));
        $request->attributes->add(compact('type'));
        $request->attributes->add(compact('dy_var'));
        $request->attributes->add(compact('admin_temp'));

        return $next($request);
    }
}
