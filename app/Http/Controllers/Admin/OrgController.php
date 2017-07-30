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
        $admin_list = ActAdmin::where('pid', 0)->get();

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $admin_list
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        $data = unset_empty($info);
        $data['password'] = Hash::make($data['password']);
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
        $admin = ActAdmin::where('admin_id', $id)->first();

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $admin
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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

        $data = unset_empty($info);
        if (ActAdmin::where('admin_id', $id)->update($data))
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
        ActAdmin::deleted($id);
    }
}
