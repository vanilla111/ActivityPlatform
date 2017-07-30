<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

class ActKey
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
        $act_key = $request->get('act_key');
        if (empty($act_key))
            return response()->json(['status' => 0, 'message' => '参数act_key必须'], 400);

        return $next($request);
    }
}
