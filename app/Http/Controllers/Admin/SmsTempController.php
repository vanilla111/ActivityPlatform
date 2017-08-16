<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminSmsTemp;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SmsTempController extends Controller
{
    public function __construct()
    {
        $this->middleware("admin.base")->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => AdminSmsTemp::where('sms_provider', 1)->get()
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
     * 存储一个短信模版
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        $data = [
//            'name' => [
//                '${full_name}' => '姓名',
//            ],
//            'content' => '',
//            'next' => ''
//        ];
//        $update = ['dynamic_variables' => $data];
//        return json_encode($update);
//        AdminSmsTemp::where('admin_temp_id', 4)->update($update);
//        return ['success'];
//        $arr = ['name' => '${full_name}', 'department' => '红岩网校'];
//        return json_encode($arr);

        //存储一个短信模版，按阿里大于的模式存放
        //需要的参数: 提供商 默认为 1， tempName, smsTemp, smsType 默认为 normal
        //smsFreeSignName, smsID, smsVariables, dyVariables
        $token_info = $request->get('token_info');
        $require = ['tempName', 'smsTemp', 'smsType', 'smsFreeSignName', 'smsID', 'smsVars', 'dyVars'];
        $sms_info = $request->only($require);
        $sms_info['smsType'] = $request->has('smsType') ? $sms_info['smsType'] : 'normal';
        if (empty($sms_info['dyVars']))
            $dyVars = null;
        else
            $dyVars = @ serialize(json_decode($sms_info['dyVars'], true));
        $data = [
            'temp_name' => $sms_info['tempName'],
            'sms_temp' => $sms_info['smsTemp'],
            'sms_free_sign_name' => $sms_info['smsFreeSignName'],
            'sms_id' => $sms_info['smsID'],
            'sms_variables' => $sms_info['smsVars'],
            'dynamic_variables' => $dyVars,
            'sms_provider' => 1,
            'sms_type' => $sms_info['smsType'],
            'admin_id' => $token_info['admin_id'],
            'type' => 1
        ];
        AdminSmsTemp::create($data);

        return response()->json([
            'status' => 1,
            'message' => 'success',
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => AdminSmsTemp::where('admin_temp_id', $id)->first()
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
