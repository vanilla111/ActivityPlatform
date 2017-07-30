<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests;
use App\Models\ActAdmin;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth.info', ['only' => ['update', 'store']]);
    }

    /**
     * 记住我功能实现
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkRememberStatus(Request $request)
    {
        $account = $request->cookies->get('account');
        if (empty($account))
            return response()->json([
                'status' => 0,
                'message' => 'no account information'
            ], 200);

        $info = ActAdmin::where('account', $account)->select('remember_token')->first();

        if (empty($info['remember_token']))
            return response()->json([
                'status' => 0,
                'message' => 'no remember token',
                'data' => ['account' => $account]
            ], 200);

        $payload = [
            'account' => $account,
            'password' => decrypt($info['remember_token'])
        ];

        try {
            if (!$token = JWTAuth::attempt($payload)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'token_not_provided',
                    'data' => ['account' => $account]
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 0,
                'error' => 'can not to create token',
                'data' => ['account' => $account]
            ], 500);
        }

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => ['account' => $account, 'token' => $token]
        ], 200);
    }

    /**
     * Auth 登录，返回token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toLogin(Request $request)
    {
        $remember_me = $request->get('remember_me');
        $payload = [
            'account' => $request->get('account'),
            'password' => $request->get('password'),
        ];

        try {
            if (!$token = JWTAuth::attempt($payload)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'token_not_provided'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 0,
                'error' => 'can not to create token'
            ], 500);
        }

        if ($remember_me == 1) {
            ActAdmin::where('account', $payload['account'])->update(['remember_token' => encrypt($payload['password'])]);
            return response()->json([
                'status' => 1,
                'message' => 'success',
                'data' => compact('token')
            ], 200)->withCookie('account', $payload['account'], 10080);
        } else {
            ActAdmin::where('account', $payload['account'])->update(['remember_token' => '']);
            return response()->json([
                'status' => 1,
                'message' => 'success',
                'data' => compact('token')
            ], 200);
        }
    }

    /**
     * 取消记住我API
     *
     */
    public function cancelRememberToken(Request $request)
    {
        $account = $request->get('account');
        $info = ActAdmin::where('account', $account)->first();
        if (!$info)
            return response()->json([
                'status' => 0,
                'message' => 'account not found'
            ], 200);

        $res = ActAdmin::where('account', $account)->update(['remember_token' => '']);
        if (!$res)
            return response()->json([
                'status' => 0,
                'message' => '失败，请重试'
            ], 200);

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 200);
    }

    /**
     * Auth注册,保存一个新用户的信息
     *
     * @param  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $allow = ['name', 'auth_code', 'password', 'phone'];
        $info = $request->only($allow);

        $admin = new ActAdmin();
        $auth_code = $info['auth_code'];
        $id = strtolower($info['password']);

        $admin_exists = $admin->where('author_code',$auth_code)->exists();
        if($admin_exists)
            return response()->json(['status' => 0, 'message' => '该学号已注册'], 400);
        else {
            //将信息与学生库核对，核对成功后插入本项目的数据库
            $stu_info = verify($info['auth_code'], $info['password']);
            if ($stu_info['status'] == 201)
                return response()->json(['status' => 0, 'message' => '学号与密码不匹配'], 400);

            $admin->admin_name = $info['name'];
            $admin->account = $info['auth_code'];
            $admin->password = Hash::make($id); //bcrypt
            $admin->author_code = $auth_code;
            $admin->author_phone = $info['phone'];
            $admin->id = $id;

            if($admin->save())
                return response()->json(['status' => 1, 'message' => '注册成功', 'login_url' => ''], 201);
            else
                return response()->json(['status' => 0, 'message' => '注册失败'], 500);
        }
    }

    /**
     * 获取用户详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth_obj = get_detail_auth_info();
        $auth = json_decode($auth_obj->content(), true);

        if ($auth['user']['admin_id'] != $id)
            return response()->json(['status' => 0, 'message' => '非法操作'], 401);

        unset($auth['user']['id']);
        return response()->json(['status' => 1, 'message' => 'success', 'data' => $auth], 200);
    }

    /**
     * 更新一个用户的信息，只允许更新姓名和电话
     *
     * @param  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $auth_obj = get_detail_auth_info();
        $auth = json_decode($auth_obj->content(), true);

        if ($auth['user']['admin_id'] != $id)
            return response()->json(['status' => 0, 'message' => '非法操作'], 401);

        $allow = ['name', 'phone'];
        $info = $request->only($allow);
        if (!empty($info['name']))
            $message['admin_name'] = $info['name'];
        if (!empty($info['phone']))
            $message['author_phone'] = $info['phone'];

        $auth_model = new ActAdmin();
        if (!$auth_model->updateAuthInfo(['account' => $id], $message))
            return response()->json(['status' => 0, 'message' => '个人信息修改失败'], 400);

        return response()->json(['status' => 1, 'message' => '修改成功'], 201);
    }
}
