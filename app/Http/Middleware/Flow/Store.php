<?php

namespace App\Http\Middleware\Flow;

use Closure;

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
        $info = $request->all();
        $error_mes = [];

        //必填字段
        $require = ['act_key', 'flow_name', 'location', 'type'];
        foreach ($require as $key) {
            if (empty($info[$key]))
                array_push($error_mes, $key . '参数必需');
        }

        if (!empty($error_mes))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        return $next($request);
    }
}
