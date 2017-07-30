<?php

namespace App\Http\Middleware\Act;

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

        $sortby_allow = [
            'activity_name' => 1,
            'start_time' => 1,
            'created_at' => 1,
            'updated_at' => 1
        ];
        $error_mes = [];
        if (!empty($condition['page']))
            if (!is_numeric($condition['page']))
                array_push($error_mes, 'page参数错误');

        if (!empty($condition['per_page']))
            if (!is_numeric($condition['per_page']) || $condition['per_page'] <=0)
                array_push($error_mes, 'per_page参数错误');

        if (!empty($condition['sortby']))
            if (@ $sortby_allow[$condition['sortby']] != 1)
                array_push($error_mes, 'sortby参数有误');

        if (!empty($condition['order'])) {
            $order = strtolower($condition['order']);
            if ($order != 'asc' && $order != 'desc')
                array_push($error_mes, 'order参数有误');
        }

        if (!empty($condition['offset']))
            if (!is_numeric($condition['offset']) || $condition['offset'] <= 0)
                array_push($error_mes, 'offset参数有误');

        if (!empty($error_mes['message']))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        return $next($request);
    }
}
