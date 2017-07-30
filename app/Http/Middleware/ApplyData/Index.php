<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

class Index
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
        $condition = $request->all();
        $error_mes = [];
        $orderby_allow = [
            'updated_at' => 1,
            'created_at' => 1,
            'score' => 1,
            'stu_code' => 1,
            'grade' => 1
        ];

        if (!empty($condition['page']))
            if (!is_numeric($condition['page']))
                array_push($error_mes, 'page参数有误');

        if (!empty($condition['per_page']))
            if (!is_numeric($condition['per_page']) || $condition['per_page'] <=0)
                array_push($error_mes, 'pre_page参数有误');

        if (!empty($condition['sortby']))
            if (@ $orderby_allow[$condition['orderby']] != 1)
                array_push($error_mes, 'sortby参数有误');

        if (!empty($condition['sort'])) {
            $order = strtolower($condition['sort']);
            if ($order != 'asc' && $order != 'desc')
                array_push($error_mes, 'sort参数有误');
        }

        if (!empty($condition['flow_id'])) {
            $flow_id = explode(',', $condition['flow_id']);
            foreach ($flow_id as $value)
                if (!is_numeric($value)) {
                    array_push($error_mes, 'flow_id参数有误,应为正整数序列');
                    break;
                }
        }

        if (!empty($error_mes['message']))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        return $next($request);
    }
}
