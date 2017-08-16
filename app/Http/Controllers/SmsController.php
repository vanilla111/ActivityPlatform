<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Sms;
use App\Models\AdminSmsTemp;
use App\Http\Requests;

class SmsController extends Controller
{
    public function __construct()
    {
        $this->middleware('sms.base')->only(['show', 'update', 'destroy']);
        $this->middleware('sms.index')->only('index');
        $this->middleware('sms.store')->only(['store']);
        $this->middleware('sms.test')->only(['sendTestSms']);
    }

    /**
     * 为活动管理员显示所有的短信模版
     * 流程设计时应该调用这个方法
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $author_id_arr = $request->get('author_id_arr');

        if (!$res = Sms::whereIn('author_id', $author_id_arr)
            ->where('status', '>', '0')
            ->select(['temp_id', 'temp_name'])
            ->orderBy('updated_at', 'desc')
            ->first()
        ) {
            return response()->json(['status' => 0, 'message' => '获取短信模版列表失败'], 404);
        }

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $res], 200);
    }

    /**
     * 为活动管理员显示所有的由网校提供的短信模版
     */
    public function getAdminSmsTemp(Request $request)
    {
        $admin_temp_m = new AdminSmsTemp();
        $condition = [
            'type' => $request->get('type') ? : 1
        ];
        $need = ['admin_temp_id', 'temp_name', 'sms_temp', 'sms_variables', 'dynamic_variables'];
        if (!$res = $admin_temp_m->getSmsList($condition, $need))
            return response()->json([
                'status' => 0,
                'message' => '获取短信模版失败'
            ], 403);

        foreach ($res as $key) {
            @ $key['sms_variables'] = explode(',', $key['sms_variables']);
            @ $key['dynamic_variables'] = unserialize($key['dynamic_variables']);
            foreach ($key['sms_variables'] as $k) {
                $key['sms_temp'] = str_replace_first('***', '${' . $k . '}', $key['sms_temp']);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $res
        ], 200);
    }

    /**
     * 创建一个短信模版\
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $type = $request->get('type');
        $dy_var = $request->get('dy_var');
        $admin_temp = $request->get('admin_temp');
        $admin_temp_id = $request->get('admin_temp_id');
        $temp_name = $request->get('temp_name');
        $temp_info_m = new Sms();
        $auth = JWTAuth::decode(JWTAuth::getToken());

        $data = [
            'author_id' => $auth['sub'],
            'temp_name' => $temp_name,
            'admin_temp_id' => $admin_temp_id,
            'variables' => serialize($dy_var),
            'content' => $admin_temp['sms_temp'],
            'type' => $type,
        ];

        if (!$temp_info_m->storeSmsTemp($data))
            return response()->json([
                'status' => 0,
                'message' => '存放失败'
            ], 500);

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 200);

    }

    /**
     * 为用户提供,短信模版发送测试
     */
    public function sendTestSms(Request $request)
    {
        $res_num = $request->get('rec_num');
        $sms_temp_info = $request->get('sms_info');
    }

    /**
     * 展示一个短信模版的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $temp_id)
    {
        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $request->get('sms_temp_info')
        ], 200);
    }

    /**
     * 更新一个短信模版
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $temp_id)
    {
        //
    }

    /**
     * 删除一个短信模版
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($temp_id)
    {
        //
    }
}
