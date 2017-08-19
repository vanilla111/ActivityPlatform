<?php

namespace App\Http\Middleware\User;

use Closure;
use App\Models\UserData;
use App\Models\ActDesign;
use App\Models\ApplyData;

class Enroll
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
        $user_id = $request->session()->get('user');
        $enroll_info = $request->all();

        if (empty($enroll_info['act_key']))
            return response()->json(['status' => 0, 'message' => 'act_key必须'], 400);

        //报名人是否存在
        if (!$user = UserData::where('user_id', '=', $user_id)->first())
            return response()->json([
                'status' => 0,
                'message' => '用户异常，请重新登录',
                'next_api' => '/api/user/login',
                'method' => 'post'
            ], 403);

        //$user_info = json_decode($user, true);
        if (is_null($user['contact']))
            return response()->json([
                'status' => 0,
                'message' => '个人联系方式未填写，无法完成报名',
                'next_api' => '/api/user/{stu_code}',
                'method' => 'put'
            ], 403);

        //多活动同时报名,先检查个活动是否开启了报名、时间对否、人数是否超过限制
        $act_key = explode(',' , $enroll_info['act_key']);
        $act_m = new ActDesign();
        $flag = 1;
        $error_mes = [];
        foreach ($act_key as $value) {
            $condition = [
                'activity_id' => $value
            ];
            $need = ['activity_name', 'start_time', 'enroll_flow',
                'end_time', 'status', 'current_num', 'num_limit'];
            if (!$act = $act_m->getActInfo($condition, $need)) {
                $flag = 0;
                $error_mes[$value] = [];
                array_push($error_mes[$value], 'not found');
            } else {
                $error_mes[$act['activity_name']] = [];
                //检查时间
                if (strtotime($act['end_time']) < time()) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动已结束');
                }
                //检查活动的状态
                if ($act['status'] <= 0) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动还未开启报名');
                }
                //检查活动报名人数是否已达到上限
                if ($act['num_limit'] > 0 && $act['current_num'] >= $act['num_limit']) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动报名人数已达到上限');
                }

                $act_info[$value] = [$act['activity_name'], $act['enroll_flow']];
            }
        }

        //如果错误信息不为空， 则直接返回
        if ($flag != 1)
            return response()->json([
                'status' => 0,
                'message' => $error_mes,
            ], 403);

        //如果已经报名，则更新报名信息，分别存储已报名合未报名
        $data_m = new ApplyData();
        $i = 0; //辅助变量
        foreach ($act_key as $key => $value) {
            $condition = [
                'activity_key' => $value,
                'user_id' => $user_id
            ];
            if ($data_m->applyDataExists($condition)) {
                $already_enroll[$i] = $act_key[$key];
                unset($act_key[$key]);
                $i++;
            }
        }

        //将信息存入request
        $request->attributes->add(compact('user'));
        $request->attributes->add(compact('act_key'));
        $request->attributes->add(compact('act_info'));
        $request->attributes->add(compact('user_id'));
        $request->attributes->add(compact('already_enroll'));

        return $next($request);
    }
}
