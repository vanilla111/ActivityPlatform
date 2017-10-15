<?php

namespace App\Http\Middleware\ApplyData;

use Closure;

class SendSmsVariables
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
        $sms = $request->get('sms');
        //解析短信短信变量
        if ($sms['type'] == 1) {
            $var = $sms['variables'];
            $pattern = '/\${\w*}/';
            $dynamic_var = [];
            $static_var = [];
            foreach ($var as $key => $value) {
                if (preg_match_all($pattern, $value, $m)) {
                    $dynamic_var[$key] = substr($m[0][0], 2, -1);
                } else
                    $static_var[$key] = $value;
            }
            $request->attributes->add(compact('dynamic_var'));
        } else {
            $static_var = $sms['variables'];
        }

        $request->attributes->add(compact('static_var'));
        return $next($request);
    }
}
