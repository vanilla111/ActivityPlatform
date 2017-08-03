<?php

namespace App\Http\Controllers;

use App\Models\ActAdmin;
use Illuminate\Http\Request;

use App\Http\Requests;
use Hash;

class OrgController extends Controller
{

    public function __construct()
    {
        $this->middleware('child_account')->only(['index', 'store', 'show', 'update', 'destroy']);
    }

    /**
     * 显示该账户下所有的子账户
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $admin_info = $request->get('token_info');
        $pid = $admin_info['admin_id'];
        $res = ActAdmin::where('pid', $pid)->select('*')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 1,
            'messsage' => 'success',
            'data' => $res
        ], 200);
    }


    /**
     * 创建一个子帐号
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $admin_info = $request->get('token_info');
        $require = ['account', 'password', 'author_code', 'author_phone', 'admin_name'];
        $info = $request->only($require);
        if (empty($info['account']) || empty($info['password']))
            return response()->json([
                'status' => 0,
                'message' => '账号，密码必需'
            ], 400);

        $info['account'] = $admin_info['account'] . '_' . $info['account'];
        //判断是否有重复账号
        if (ActAdmin::where('account', $info['account'])->first())
            return response()->json([
                'status' => 0,
                'message' => '该账号已被注册'
            ], 400);

        $info['password'] = Hash::make($info['password']);
        $info['pid'] = $admin_info['admin_id'];
        $info['out_of_dept'] = $admin_info['out_of_dept'];

        $data = unset_empty($info);

        if (ActAdmin::create($data))
            return response()->json([
                'status' => 1,
                'message' => 'success',
                'data' => ['account' => $data['account']]
            ], 200);

        return response()->json([
            'status' => 0,
            'message' => '服务器发生了错误'
        ], 500);
    }

    /**
     * 显示该帐号信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $token_info = $request->get('token_info');
        $child_account = ActAdmin::where('admin_id', $id)->first();
        if (isset($child_account['pid']) && $child_account['pid'] != $token_info['admin_id'])
            return response()->json([
                'status' => 0,
                'message' => '非法访问'
            ], 403);

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $child_account
        ], 200);
    }

    /**
     * 更新当前帐号部分信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $token_info = $request->get('token_info');
        $child_account = ActAdmin::where('admin_id', $id)->first();
        if (isset($child_account['pid']) && $child_account['pid'] != $token_info['admin_id'])
            return response()->json([
                'status' => 0,
                'message' => '非法访问'
            ], 403);

        $allow = ['password', 'author_phone', 'author_code', 'admin_name'];
        $info = $request->only($allow);
        if (isset($info['password']))
            $info['passowrd'] = Hash::make($info['password']);
        $data = unset_empty($info);

        if (ActAdmin::where('admin_id', $id)->update($data))
            return response()->json([
                'status' => 1,
                'message' => 'success'
            ], 200);

        return response()->json([
            'status' => 0,
            'message' => '服务器发生错误'
        ],500);
    }

    /**
     * 删除帐号
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $token_info = $request->get('token_info');
        $child_account = ActAdmin::where('admin_id', $id)->first();
        if (isset($child_account['pid']) && $child_account['pid'] != $token_info['admin_id'])
            return response()->json([
                'status' => 0,
                'message' => '非法访问'
            ], 403);

        ActAdmin::delete($id);
    }
}
