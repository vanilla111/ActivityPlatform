<?php

namespace App\Http\Middleware\User;

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

        if (!empty($info['name']))
            if (is_all_letter($info['name']))
                array_push($error_mes, '姓名中须有中文');

        if (!empty($info['contact']))
            if (!check_phoneNum($info['contact']))
                array_push($error_mes, '电话号码有误');

        if (!empty($info['gender']))
            if (!is_numeric($info['gender']) || $info['gender'] < 0 || $info['gender'] > 1)
                array_push($error_mes, '性别代号有误');

        if (!empty($info['college']))
            if (!is_numeric($info['college']) || $info['college'] <= 0)
                array_push($error_mes, '学院代号有误');

        if (!empty($error_mes))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);
        return $next($request);
    }
}
