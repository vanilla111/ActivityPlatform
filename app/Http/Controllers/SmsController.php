<?php

namespace App\Http\Controllers;

use App\Models\SmsNum;
use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Sms;
use App\Models\AdminSmsTemp;
use App\Http\Requests;
use Flc\Alidayu\Client;
use Flc\Alidayu\App;
use Flc\Alidayu\Requests\AlibabaAliqinFcSmsNumSend;
use App\Models\SmsHistory;

class SmsController extends Controller
{
    public function __construct()
    {
        $this->middleware('sms.base')->only(['show', 'update', 'destroy']);
        $this->middleware('sms.index')->only(['index', 'getSendSmsHistory', 'sendTestSms']);
        $this->middleware('sms.store')->only(['store', 'update']);
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
            ->select(['temp_id', 'temp_name', 'content', 'was_test', 'variables', 'created_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
        ) {
            return response()->json(['status' => 0, 'message' => '获取短信模版列表失败'], 404);
        }

        foreach ($res as $key => $value) {
            //return $value;
            foreach ($value['variables'] as $k => $v) {
                //return $value;
                $value['content'] = str_replace_first('${' . $k . '}', $v, $value['content']);
            }
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
        $rec_num = $request->get('rec_num');
        $sms_temp_id =  $request->get('temp_id');
        $author_id_arr=  $request->get('author_id_arr');
        //短信接收人
        if (empty($rec_num) || !check_phoneNum($rec_num))
            return response()->json(['status' => 0, 'message' => '手机号码有误'], 400);

        //越权检查
        $sms_temp = Sms::find($sms_temp_id);
        if (!in_array($sms_temp['author_id'], $author_id_arr))
            return response()->json(['status' => 0, 'message' => '非法访问'], 403);

        //短信长度判断
        $content = $sms_temp['content'];
        foreach ($sms_temp['variables'] as $key => $value)
            $content = str_replace_first('${' . $key . '}', $value, $content);
        $num = ceil(mb_strlen($content) / 70);

        //短信余额查询
        if ($author_id_arr[1] <= 0) {
            $sms_num = SmsNum::find($author_id_arr[0]);
        } else {
            $sms_num = SmsNum::find($author_id_arr[1]);
        }

        if (!isset($sms_num) || $sms_num['sms_num'] - $num < 0)
            return response()->json([
                'status' => 0,
                'message' => '短信余额不足,请联系平台管理员充值'
            ], 400);

        //短信id , 签名等获取
        $admin_temp = AdminSmsTemp::find($sms_temp['admin_temp_id']);
        //发送短信
        $config = [
            'app_key' => env('ALIDAYU_APP_KEY'),
            'app_secret' => env('ALIDAYU_APP_SECRET')
        ];
        $client = new Client(new App($config));
        $req    = new AlibabaAliqinFcSmsNumSend;
        $req->setRecNum($rec_num)
            ->setSmsParam($sms_temp['variables'])
            ->setSmsFreeSignName($admin_temp['sms_free_sign_name'])
            ->setSmsTemplateCode($admin_temp['sms_id']);

        $res = $client->execute($req);

        //记录日志
        $result = (array)($res);
        if (isset($result['result'])) {
            $result_arr = json_decode(json_encode($result), true);
            if ($result_arr['result']['err_code'] == 0 && $result_arr['result']['success']) {
                //认为发送成功 记录成功日志  并将短信余额减少
                $history = [
                    'who_send' => $author_id_arr[0],
                    'code' => $result_arr['result']['err_code'],
                    'request_id' => $result['request_id'],
                    'msg' => $result_arr['result']['msg'],
                    'model' => $result_arr['result']['model'],
                    'other_info' => "用户模版自行测试数据"
                ];
                $sms_num->decrement('sms_num', $num);
                SmsHistory::create($history);
                //重新标记该短信模版
                $sms_temp->update(['was_test' => 1]);
                return response()->json(['status' => 1, 'message' => 'success'], 200);
            }
        } else {
            //认为发送失败
            $history = [
                'who_send' => $author_id_arr[0],
                'code' => $result['code'],
                'msg' => $result['msg'],
                'sub_code' => $result['sub_code'],
                'sub_msg' => $result['sub_msg'],
                'request_id' => $result['request_id'],
                'other_info' => "用户模版自行测试数据"
            ];
            SmsHistory::create($history);
            return response()->json(['status' => 0, 'message' => $history['sub_msg'], 'data' => $history], 400);
        }
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
        $type = $request->get('type');
        $dy_var = $request->get('dy_var');
        $admin_temp = $request->get('admin_temp');
        $admin_temp_id = $request->get('admin_temp_id');
        $temp_name = $request->get('temp_name');

        $data = [
            'temp_name' => $temp_name,
            'admin_temp_id' => $admin_temp_id,
            'variables' => serialize($dy_var),
            'content' => $admin_temp['sms_temp'],
            'was_test' => 0,
            'type' => $type,
        ];

        if (!Sms::where('temp_id', $temp_id)->update($data))
            return response()->json([
                'status' => 0,
                'message' => '修改失败'
            ], 500);

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 200);
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
