<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

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

        if (!empty($info['full_name'])) {
            if (is_all_letter($info['full_name']))
                array_push($error_mes, '名字必须含有中文');
            if (utf8_strlen($info['full_name']) > 16 || utf8_strlen($info['full_name']) < 2)
                array_push($error_mes, '参数name长度有误');
        }

        if (!empty($info['stu_code']) && !empty($info['password'])) {
            $temp = verify($info['stu_code'], $info['password']);
            if ($temp['status'] != 200)
                array_push($error_mes, '身份信息有误，请检查学号是否与身份证后六位相匹配');
            else {
                $user_info = $temp['data'];
                $request->attributes->add(compact('user_info'));
            }
        }

        if (!empty($info['contact']))
            if (!check_phoneNum($info['contact']))
                array_push($error_mes, '联系方式有误');

        if (!empty($info['gender']))
            if ($info['gender'] != '男' || $info['gender'] != "女")
                array_push($error_mes, '性别参数有误');

//        if (!empty($info['college']))
//            if (!is_numeric($info['college']) || $info['college'] <= 0)
//                array_push($error_mes, '学院代号有误');

        if (isset($info['grade']))
            if (!is_numeric($info['grade']))
                array_push($error_mes, 'grade参数有误');

        if (!empty($info['flow_id']))
            if (!is_numeric($info['flow_id']) || $info['flow_id'] <= 0)
                array_push($error_mes, 'step参数有误');

        if (isset($info['score']))
            if (!is_numeric($info['score']) || $info['score'] > 100 || $info['score'] < 0)
                array_push($error_mes, '分数为百分制');

        if (!empty($error_mes))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        return $next($request);
    }
}
