<?php

namespace App\Http\Middleware\Act;

use Closure;

class Variables
{
    protected $message = [];

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

        //不能全为字母和数字的字段
        if (!empty($info['activity_name']))
            if (is_all_letter($info['activity_name']))
                array_push($this->message, 'activity_name参数不能全为数字或字母');
        if (!empty($info['summary']))
            if (is_all_letter($info['summary']))
                array_push($this->message, 'summary参数不能全为数字或字母');


        //名称中不能有特殊字符，为以后经过认证的帐号添加活动签名功能
//        if (!empty($info['activity_name']))
//            if(preg_match("/[`#*【】]|\]|\[|\/|\\|\"|\|/", $info['activity_name']))
//                array_push($this->message, '参数act_name不能含有特殊字符');

        //活动日期格式及其合理性检查
        $flag = 0;
        if (!empty($info['start_time'])) {
            if (!is_date($info['start_time']))
                array_push($this->message, '活动起始日期格式有误');
            elseif (strtotime($info['start_time']) < time())
                array_push($this->message, '活动起始日期应晚于当前时间');
            elseif (strtotime($info['start_time']) > time() + 2 * 30 * 24 * 60 * 60)
                array_push($this->message, '活动起始时间过晚');
            else
                $flag++;
        }
        if (!empty($info['end_time'])) {
            if (!is_date($info['end_time']))
                array_push($this->message, '活动截至日期格式有误');
            elseif (strtotime($info['end_time']) < time())
                array_push($this->message, '活动截至日期应晚于当前时间');
            elseif ($flag == 1 && strtotime($info['end_time']) > strtotime($info['start_time']) + 3 * 30 * 24 * 60 *60)
                array_push($this->message, '活动周期过长');
            else
                $flag++;
        }

        //对时间的有效性判断
        if ($flag == 2)
            if(strtotime($info['start_time']) > strtotime($info['end_time']))
                array_push($this->message, '活动时间错误，起始时间应早于截至时间');

        //人数限制不能为负数，默认0表示不限制
        if (!empty($info['max_num']))
            if (!is_numeric($info['max_num']) || $info['max_num'] < 0)
                array_push($this->message, 'max_num参数有误,请输入非负整数');

        if (!empty($this->message))
            return response()->json(['status' => 0, 'message' => $this->message], 400);

        return $next($request);
    }
}
