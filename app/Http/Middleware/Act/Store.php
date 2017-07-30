<?php

namespace App\Http\Middleware\Act;

use Closure;

class Store
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

        //必填字段
        $require = ['activity_name', 'summary', 'start_time', 'end_time'];
        foreach ($require as $key) {
            if (empty($info[$key]))
                array_push($this->message, $key . '必须');
        }

        if (!empty($message))
            return response()->json(['status' => 0, 'message' => $message], 400);

        return $next($request);
    }
}
