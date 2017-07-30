<?php

namespace App\Http\Middleware;

use Closure;

class AuthInfo
{
    protected $message = ['message' =>[]];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //检查名字和电话号码
        $info = $request->all();

        if (!empty($info['name']) && is_all_letter($info['name']))
            array_push($this->message['message'], '姓名不能全为字母');

        if (!empty($info['phone']) && !check_phoneNum($info['phone']))
            array_push($this->message['message'], '手机号码有误');

        if (!empty($info['stu_code']))
            if (!is_numeric($info['stu_code']) || strlen($info['stu_code']) != 10)
                array_push($this->message['message'], '学号信息有误');

        if (!empty($this->message['message'])) {
            return response()->json(['status' => 0, 'message' => $this->message['message']], 400);
        }

        return $next($request);
    }
}
