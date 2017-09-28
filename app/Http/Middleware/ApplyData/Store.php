<?php

namespace App\Http\Middleware\ApplyData;

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
        $require = ['stu_code', 'full_name', 'contact'];
        foreach ($require as $key) {
            if (empty($info[$key]))
                array_push($error_mes, $key.'参数必需');
        }

        if (!empty($error_mes))
            return response()->json(['status' => 0, 'message' => $error_mes], 400);

        return $next($request);
    }
}
