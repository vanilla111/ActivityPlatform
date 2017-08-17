<?php

namespace App\Http\Controllers;

use App\Jobs\SendSms;
use App\Models\ActAdmin;
use App\Models\FlowInfo;
use App\Models\Sms;
use App\Models\SmsHistory;
use Illuminate\Http\Request;
use App\Models\ApplyData;
use App\Models\ActDesign;
use App\Models\UserData;
use App\Jobs\ChangeStep;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use JWTAuth;
use Flc\Alidayu\Support;

class ApplyDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('data.actkey')->only(['index']);
        $this->middleware('data.enrollid')->only(['show', 'update', 'destroy']);
        $this->middleware('data.flowid')->only(['store', 'update', 'operation', 'isSendSmsAndUpgrade']);
        $this->middleware('data.base')->only(['index', 'store', 'show', 'update', 'destroy']);
        $this->middleware('data.index')->only(['index']);
        $this->middleware('data.store')->only(['store']);
        $this->middleware('data.checkauth')->only(['store']);
        $this->middleware('data.variables')->only(['store', 'update']);
        $this->middleware('data.sendSms')->only('sendSMS');
        $this->middleware('data.sendSmsVariables')->only('sendSMS');
    }

    /**
     * 根据条件显示一个活动的申请信息
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $allow = ['page', 'per_page', 'sortby', 'sort', 'act_key', 'flow_id', 'college', 'gender', 'name', 'stu_code', 'was_send_sms'];
        $info = $request->only($allow);

        //有关参数初始化
        $info['per_page'] = $info['per_page'] ? : 20;
        $info['sortby']   = $info['sortby'] ? : 'updated_at';
        $info['sort']     = $info['sort'] ? : 'desc';
        $info['current_step'] = $info['flow_id'] ? explode(',', $info['flow_id']) : $request->get('current_flow');
        $info['was_send_sms'] = $info['was_send_sms'] ? 1 : 0;
        unset($info['flow_id']);

        $need = ['enroll_id', 'user_id', 'stu_code', 'full_name',
            'contact', 'college', 'gender', 'score', 'evaluation'];

        //查询
        //return [is_array($info['current_step'])];
        $new_info = unset_empty($info);
        $data_m = new ApplyData();
        $res = $data_m->getApplyDataList($new_info, $need);
        if (!$res)
            return response()->json([
                'status'  => 0,
                'message' => '获取信息列表失败'
            ], 403);

        return response()->json([
            'status'  => 1,
            'message' => 'success',
            'date'    => $res
        ], 200);
    }

    /**
     * 保存一条申请信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //允许接受的参数
        $allow = ['college', 'stu_code', 'password', 'contact', 'flow_id'];
        $info = $request->only($allow);
        $userInfo = $request->get('user_info');
        $act_key = $request->get('act_key');

        //如果已经报名则返回,返回其所处状态
        $condition = [
            'stu_code'     => $info['stu_code'],
            'activity_key' => $act_key
        ];
        $need = [
            'current_step'
        ];
        $data_m = new ApplyData();
        $applyData = $data_m->getApplyData($condition, $need);
        $applyData = json_decode($applyData, true);
        if (!empty($applyData))
            return response()->json([
                'status'  => 0,
                'message' => '该同学已报名，无须添加',
                'data'    => $applyData
            ], 400);

        //检查本地是否有此学生，如果没有，则添加一条
        $user_m = new UserData();
        if (!$user = $user_m->getUserInfo(['stu_code' => $info['stu_code']], 'user_id')){
            $user_info = [
                'full_name' => $userInfo['name'],
                'grade'     => $userInfo['grade'],
                'gender'    => $userInfo['gender'],
                'college'   => $info['college'],
                'contact'   => $info['contact'],
                'stu_code'  => $info['stu_code'],
                'password'  => Hash::make($info['password'])
            ];
            if (!$new_user = $user_m->storeUserInfo($user_info))
                return response()->json([
                    'status'  => 0,
                    'message' => '添加失败'
                ], 403);
        }

        //添加一条申请信息
        $apply_info = [
            'user_id'      => $user['user_id'] ? : $new_user['user_id'],
            'activity_key' => $act_key,
            'current_step' => $info['flow_id'],
            'full_name'    => $userInfo['name'],
            'grade'        => $userInfo['grade'],
            'gender'       => $userInfo['gender'],
            'stu_code'     => $info['stu_code'],
            'contact'      => $info['contact'],
            'college'      => $info['college'],
            'act_name'     => $request->get('act_name')
        ];
        DB::beginTransaction();
        if (!$data_m->storeData($apply_info)) {
            return response()->json([
                'status'  => 0,
                'message' => '添加失败'
            ], 400);
        }
        if (!ActDesign::where('activity_id', '=', $act_key)->increment('current_num', 1)) {
            DB::rollBack();
            return response()->json([
                'status'  => 0,
                'message' => '添加失败'
            ], 400);
        }
        DB::commit();

        return response()->json([
            'status'  => 1,
            'message' => '添加成功'
        ], 201);
    }

    /**
     * 显示一个申请信息的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $enroll_id)
    {
        $apply_info = $request->get('apply_info')[0];

//        @ $apply_info['score']      = unserialize($apply_info['score']);
//        @ $apply_info['evaluation'] = unserialize($apply_info['evaluation']);

        return response()->json([
            'status'  => 1,
            'message' => 'success',
            'data'    => $apply_info
        ], 200);
    }

    /**
     * 更新一条申请信息，着重更新分数和评价字段
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $enroll_id)
    {
        $temp = $request->get('apply_info');
        $act_name = $request->get('act_name');
        $flow_name = $request->get('flow_info')['flow_name'];
        $apply_info = $temp[0];

        //着重更新分数和评价字段
        $allow = ['college', 'contact', 'score', 'evaluation'];
        $info = $request->only($allow);

        $score = array();
        $evaluation = array();

        //如果已经有前面阶段的评分或评价，先反序列化
        if (!empty($apply_info['score']))
            @ $score = unserialize($apply_info['score']);
        if (!empty($apply_info['evaluation']))
            @ $evaluation = unserialize($apply_info['evaluation']);

        $sign = $act_name. '[' . $flow_name . ']';
        $score[$apply_info['current_step']] = [$sign => $info['score']];
        $evaluation[$apply_info['current_step']] = [$sign => $info['evaluation']];
        $info['score'] = serialize($score);
        $info['evaluation'] = serialize($evaluation);

        //去除字段为空的键值对
        $update_info = unset_empty($info);

        $data_m = new ApplyData();
        if (! $data_m->updateData(['enroll_id' => $enroll_id], $update_info))
            return response()->json([
                'status'  => 0,
                'message' => '修改信息失败'
            ], 400);

        return response()->json([
            'status'  => 1,
            'message' => 'success'
        ], 201);
    }

    /**
     * 删除一条申请信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $enroll_id)
    {
        //删除申请信息
        DB::beginTransaction();
        if (!ApplyData::where('enroll_id', '=', $enroll_id)->update(['status' => -1])) {
            return response()->json(['status' => 0, 'message' => '删除失败'], 400);
        }
        if (!ActDesign::where('activity_id', '=', $request->get('act_key'))->decrement('current_num', 1)) {
            DB::rollback();
            return response()->json(['status' => 0, 'message' => '删除失败'], 400);
        }
        DB::commit();

        return response()->json(['status' => 1, 'message' => 'success'], 204);
    }

    /**
     * 升级、降级、短信操作
     */
    public function operation(Request $request)
    {
        //检查每个申请的ID是否属于本人操作，并且判断是属于同一个阶段的
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];

        $allow = ['enroll_id', 'flow_id', 'action'];
        $info = $request->only($allow);

        $operation_info = [
            'enroll_id' => $info['enroll_id'],
            'flow_id' => $info['flow_id'],
            'act_key' => $request->get('act_key'),
            'author_id' => $author_id
        ];

        if (strtolower($info['action']) == 'up')
            return $this->upgrade($operation_info);
        elseif (strtolower($info['action']) == 'de')
            return $this->degrade($operation_info);
        else
            return response()->json(['status' => 0, 'message' => 'action 参数错误']);
    }

    /**
     * 将发送过信息的申请信息升阶到下一个流程
     * @param Request $request
     */
    public function isSendSmsAndUpgrade(Request $request)
    {
        $act_key = $request->get('act_key');
        $flow_id = $request->get('flow_id');

        //检查每个申请的ID是否属于本人操作，并且判断是属于同一个阶段的
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];

        $act = ActDesign::where('activity_id', '=', $act_key)
            ->select(['author_id'])
            ->first();

        //越权检查
        if ($author_id != $act['author_id']) {
            return response()->json(['status' => 0, 'message' => '非法操作'], 400);
        }
        //找出现已阶段流程
        $new_flow = FlowInfo::where('flow_id', '>', $flow_id)
            ->where('status', '>=', 0)
            ->where('activity_key', $act_key)
            ->select('flow_id')
            ->first();

        if (!$new_flow)
            return response()->json([
                'status' => 0,
                'message' => '未找到下一个流程'
            ], 400);

        $update_data = [
            'current_step' => $new_flow['flow_id'],
            'was_send_sms' => 0
        ];
        $condition = [
            'current_step' => $flow_id,
            'was_send_sms' => 1
        ];
        if ((new ApplyData())->updateData($condition, $update_data))
            return response()->json([
                'status' => 0,
                'message' => 'success'
            ], 200);


        return response()->json([
            'status' => 1,
            'message' => '服务器遇到错误'
        ], 500);
    }

    protected function upgrade($operation_info)
    {
        $act = ActDesign::where('activity_id', '=', $operation_info['act_key'])
            ->select(['author_id'])
            ->first();

        //越权检查
        if ($operation_info['author_id'] != $act['author_id']) {
            return response()->json(['status' => 0, 'message' => '非法操作'], 400);
        }
        //检查是否存在下一阶段
        $flow_list = (new FlowInfo())->getFlowList(['activity_key' => $operation_info['act_key']], ['flow_id']);
        $i = count($flow_list);
        foreach ($flow_list as  $value) {
            if ($operation_info['flow_id'] == $value['flow_id'])
                break;
            $i--;
        }
        if ($i == count($flow_list)) {
            return response()->json(['status' => 0, 'message' => '已是最后一个流程'], 400);
        }

        //更改申请信息所处流程号

        $act_key = $operation_info['act_key'];
        $new_flow = $flow_list[$i - 1];
        $enroll_id = explode(',', $operation_info['enroll_id']);
        foreach ($enroll_id as $value) {
            $this->dispatch(new ChangeStep($value, $act_key, $new_flow));
        }

        return response()->json(['status' => 1, 'message' => '申请已进入队列，如有失败请求，请重新尝试'], 202);
    }

    protected function degrade($operation_info)
    {
        return response()->json(['status' => 0, 'message' => '暂不提供该功能'], 200);
    }

    /**
     * 准备发送短信的必要信息
     * 加入队列为选中的个人信息发送短信
     * 如果有后续流程，默认不升阶
     * 被关联流程全员发送
     */
    public function sendSMS(Request $request)
    {
        $admin_temp = $request->get('admin_temp');
        $sms = $request->get('sms');
        $flow_id = explode(',', $request->get('flow_id'));
        $enroll_id = $request->get('enroll_id');
        $static_var = $request->get('static_var');
        $dynamic_var = $request->get('dynamic_var');
        $author_id = $request->get('author_id');
        $author_pid = $request->get('author_pid');

        $apply_data_m = new ApplyData();
        //从数据库获取数据
        $need = ['contact'];
        if (!empty($dynamic_var)) {
            foreach ($dynamic_var as $value)
                array_push($need, $value);
        }
        $condition = [
            'enroll_id' => $enroll_id,
            'flow_id' => $flow_id,
        ];
        $apply_data_arr = $apply_data_m->getApplyDataToSendSms($condition, $need);

        if (!$apply_data_arr || empty($apply_data_arr))
            return response()->json([
                'status' => 0,
                'message' => '发送失败，可能需要发送的短信数为0'
            ], 400);

        //加入队列, 若然有字数限制则将注释去除
        $content = $sms['content'];
        $sms_id = $admin_temp['sms_id'];
        $sms_free_sign_name = $admin_temp['sms_free_sign_name'];
        $vars = [];
        foreach ($apply_data_arr as $key => $value) {
            if (!empty($dynamic_var))
                foreach ($dynamic_var as $k => $v) {
                    $vars[$k] = $value[$v];
                    //$content = str_replace_first('${' . $k . '}', $vars[$k], $content);
                }
            foreach ($static_var as $k => $v) {
                $vars[$k] = $v;
            }

            $this->dispatch(
                new SendSms($value['contact'], $vars, $sms_free_sign_name, $sms_id, $author_id, $author_pid, $content)
            );
        }
        return response()->json(['status' => 1, 'message' => '发送任务已进入队列，如有失败请重新尝试'], 200);
    }

    public function getHistory()
    {
        $res = SmsHistory::select('*')->orderBy('created_at', 'desc')->get();
        return $res;
    }

    public function getCSV()
    {
        //
    }

}
