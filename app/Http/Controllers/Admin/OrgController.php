<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActAdmin;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class OrgController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.base')->only(['index', 'store', 'show', 'update', 'destroy']);
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
