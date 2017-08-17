<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActAdmin;
use App\Models\Sms;
use App\Models\SmsNum;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class OrgController extends Controller
{
    private $admin_ip = "act.admin.ip:";

    public function __construct()
    {
        $this->middleware('admin.base')->only(['index', 'store', 'show', 'update', 'destroy', 'chargeSms']);
    }

    /**
     * 超级管理员管理组织或社团的帐号
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin_list = ActAdmin::where('pid', 0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $admin_list
        ], 200);
    }


    /**
     * 短信充值
     * @param $request
     * @return json
     */
    public function chargeSms(Request $request)
    {
        //限制接口调用时间间隔 3s
        $client_ip = $request->getClientIp();
        $key = $this->admin_ip . $client_ip;
        if (Redis::exists($key))
            return response()->json(['status' => 0, 'message' => '该接口限制3s调用一次'], 400);
        else {
            Redis::set($key, 1);
            //设置3秒过期时间
            Redis::expire($key, 3);
        }
        $require = ['admin_id', 'sms_num'];
        $info = $request->only($require);
        if (!is_numeric($info['sms_num']))
            return response()->json(['status' => 0, 'message' => 'sms_num需为整数'], 400);
        $admin = ActAdmin::where('admin_id', $info['admin_id'])
            ->where('pid', '<=', 0)
            ->first();
        if (!$admin)
            return response()->json(['status' => 0, 'message' => '只可为管理员账号充值'], 400);

        $admin_sms_num = SmsNum::where('admin_id', $info['admin_id'])->select('*')->first();

        if ($admin_sms_num)
            $admin_sms_num->increment('sms_num', $info['sms_num']);
        else {
            $data = ['admin_id' => $info['admin_id'], 'sms_num' => $info['sms_num']];
            SmsNum::create($data);
        }

        return response()->json(['status' => 1, 'message' => 'success'], 200);
    }

    /**
     * 保存一个新的帐号
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $require = ['account', 'password', 'admin_name','author_code', 'author_phone', 'out_of_dept'];
        $info = $request->only($require);
        if (empty($info['account']) || empty($info['password']))
            return response()->json([
                'status' => 0,
                'message' => 'account, password必需'
            ], 400);
        $info['pid'] = 0;
        $data = unset_empty($info);
        $data['password'] = Hash::make($data['password']);
        if (ActAdmin::where('account', $info['account'])->first())
            return response()->json([
                'status' => 0,
                'message' => '该账号已存在'
            ], 400);
        if (ActAdmin::create($data))
            return response()->json([
                'status' => 1,
                'message' => 'success'
            ], 200);


        return response()->json([
            'status' => 0,
            'message' => '服务器发生错误，请稍后尝试'
        ], 400);
    }

    /**
     * 显示一个帐号的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $admin = ActAdmin::where('admin_id', $id)->where('pid', '>=', 0)->first();

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $admin
        ], 200);
    }

    /**
     * 更改一个帐号的信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $require = ['account', 'password', 'admin_name','author_code', 'author_phone', 'out_of_dept'];
        $info = $request->only($require);

        if (isset($info['password']))
            $info['password'] = Hash::make($info['password']);
        if (isset($info['account'])) {
            $user_info = ActAdmin::where('account', $info['account'])->first();
            if (!empty($user_info)) {
                if ($user_info['admin_id'] != $id)
                    return response()->json([
                        'status' => 0,
                        'message' => '改账号已存在'
                    ], 400);
            }
        }


        $data = unset_empty($info);
        if (ActAdmin::where('admin_id', $id)->where('pid', '>=', 0)->update($data))
            return response()->json([
                'status' => 1,
                'message' => 'success'
            ], 200);

        return response()->json([
            'status' => 0,
            'message' => '服务器发生错误，请稍后尝试'
        ], 500);
    }

    /**
     * 删除一个帐号
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        ActAdmin::where('admin_id', $id)->delete();
    }
}
