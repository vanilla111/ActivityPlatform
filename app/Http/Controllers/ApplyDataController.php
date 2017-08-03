<?php

namespace App\Http\Controllers;

use App\Models\ActAdmin;
use App\Models\FlowInfo;
use App\Models\Sms;
use Illuminate\Http\Request;
use App\Models\ApplyData;
use App\Models\ActDesign;
use App\Models\UserData;
use App\Jobs\ChangeStep;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use JWTAuth;
use Illuminate\Support\Facades\Log;

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
        if (!ActDesign::where('activity_id', '=', $info['act_key'])->increment('current_num', 1)) {
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

        if (strtolower($info['action']) == 'upgrade')
            return $this->upgrade($operation_info);
        elseif (strtolower($info['action']) == 'degrade')
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
        foreach ($flow_list as $value) {
            if ($operation_info['flow_id'] == $value)
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
        //
    }

    /**
     * 准备发送短信的必要信息
     * 加入队列为选中的个人信息发送短信
     * 如果有后续流程，默认不升阶
     * 被关联流程全员发送
     */
    public function sendSMS(Request $request)
    {
        $allow = ['enroll_id', 'step', 'act_key', 'all', 'use_correlation'];
        $info = $request->only($allow);

        //模型初始化
        $data_m = new ApplyData();
        $act_m = new ActDesign();
        $flow_m = new FlowInfo();
        $sms_m = new Sms();

        //查询相关需要的信息
        $act_condition = [
            'activity_key' => $info['act_key'],
        ];
        $act_need = ['flow_structure', 'author_id'];
        if (!$act_res = $act_m->getActInfo($act_condition, $act_need))
            return response()->json([
                'status' => 0,
                'message' => '服务器繁忙，请稍后尝试'
            ], 404);
        $flow_structure = $act_res['flow_structure'];
        $flow_list = explode(',', $flow_structure);
        $flow_id = $flow_list[$info['step'] - 1];
        //找到该流程
        $flow_condition = [
            'flow_id' => $flow_id,
        ];
        $flow_need = ['correlation', 'activity_key', 'sms_temp_id', 'SmsVariables'];
        if (!$flow_res = $flow_m->getFlowInfo($flow_condition, $flow_need))
            return response()->json([
                'status' => 0,
                'message' => '服务器繁忙，请稍后尝试'
            ], 404);

        //如果关联，查找所有被关联活动的未发送短信的手机号码
        if ($info['use_correlation'] == 'true') {
            //是否有关联
            if (empty($flow_res['correlation']))
                return response()->json([
                    'status' => 0,
                    'message' => '该流程没有被设置关联，请不要传入use_correlation参数'
                ], 400);
            //如果有关联，则将其余活动的所有电话号码集中
            $act_secret = explode(',', $flow_res['correlation']);

            foreach ($act_secret as $secret) {
                //自定义查询
                $other_act = $act_m->getActInfo(['activity_secret' => $secret], ['flow_structure', 'activity_key']);
                $other_act = json_decode($other_act, true);
                if (empty($other_act)) {
                    //数据库记录，记录短信发送历史
                    $sms_history['failed_act'][$secret]['act'] = '该活动不存在或已删除';
                    continue;
                }
                $other_structure = explode(',', $other_act['flow_structure']);
                if (!$other_step = array_search($flow_id, $other_structure)) {
                    $sms_history['failed_act'][$secret]['correlation'] = '该流程关联可能已经被被关联者解除';
                    continue;
                } else {
                    $other_step += 1;
                }
                $other_key = $other_act['activity_key'];
                $condition = [
                    'current_step' => $other_step,
                    'activity_key' => $other_key,
                    'was_send_sms' => 0
                ];
                $need = ['contact'];

                if (!$other_contact = $data_m->getApplyData($condition, $need)) {
                    $sms_history['failed_act'][$secret]['apply_data'] = '用户申请信息获取失败';
                    continue;
                }

                //得到secret对应活动的该流程的尚未发送短信的联系电话
                $all_contact[$other_key] = [];
                foreach ($other_contact as $value) {
                    array_push($all_contact[$other_key], $value['contact']);
                }
            }
        }

        if ($info['all'] == 'true') {
            $condition = [
                'current_step' => $info['step'],
                'activity_key' => $info['act_key'],
                'was_send_sms' => 0
            ];
            $need = ['contact'];
            if (!$contact = $data_m->getApplyData($condition, $need))
                return response()->json([
                    'status' => 0,
                    'message' => '服务器繁忙，请稍后尝试'
                ], 403);
            $all_contact[$info['act_key']] = [];
            foreach ($contact as $value) {
                array_push($all_contact[$info['act_key']], $value['contact']);
            }
        } else {
            if (!$idList = explode(',', $info['enroll_id']))
                return response()->json([
                    'status' => 0,
                    'message' => '请使用半角逗号分割id'
                ], 400);
            //自定义查询
            $contact = ApplyData::whereIn('enroll_id', $idList)
                ->where('current_step', '=', $info['step'])
                ->where('activity_key', '=', $info['act_key'])
                ->where('was_send_sms', '=',0)
                ->where('status', '>', 0)
                ->select('contact')
                ->get();
            if (!$contact)
                return response()->json([
                    'status' => 0,
                    'message' => '服务器繁忙，请稍候尝试'
                ], 400);
            $all_contact[$info['act_key']] = [];
            foreach ($contact as $value) {
                array_push($all_contact[$info['act_key']], $value['contact']);
            }
        }

        //对每个活动的号码去重
        $counter[] = '';  //计数君,记录每个活动的电话号码数量
        $i = 0; //辅助变量
        foreach ($all_contact as $key => $value) {
            $all_contact[$key] = unique_array($value);
            $key_arr[$i] = $key;
            $counter[$key]['num'] = count($all_contact[$key]);
            $i++;
        }

        //将所有号码求并集，再去重
        $send_contact = [];
        foreach ($all_contact as $key => $value) {
            foreach ($value as $v)
                array_push($send_contact, $v);
        }
        $send_contact = unique_array($send_contact);
        //return $send_contact;

        //如果需要发送的短信总数为零
        $sum = count($send_contact);  //总数君,预计要发送的条数
        if ($sum <= 0)
            return response()->json([
                'status' => 0,
                'message' => '没有需要发送短信的号码'
            ], 400);

        //根据已经估算出的需要发送短信的总数
        $sms_need = ['author_id', 'admin_temp_id', 'provider', 'appKey', 'secret', 'SmsFreeSignName', 'SmsID', 'content'];
        if (! $sms_temp = $sms_m->getSmsInfo($flow_res['sms_temp_id'], $sms_need)) {
            return response()->json([
                'status' => 0,
                'message' => '短信服务初始化失败'
            ], 400);
        }
        $sms_temp = json_decode($sms_temp, true);
        //如果一个用户使用网校提供的短信服务，判断是否有充足的余额
        if (!empty($sms_temp['admin_temp_id'])) {
            //查找用户表，获取剩余短信条数
            $content_length = mb_strlen($sms_temp['content']);
            if ($content_length > 67)
                $temp_sum = $sum * 2;
            else
                $temp_sum = $sum;
            $auth_m = new ActAdmin();
            $auth_info = $auth_m->getAuthInfo(['admin_id' => $sms_temp['author_id']], 'sms_num');
            if ($auth_info['sms_num'] < $sum)
                return response()->json([
                    'status' => 0,
                    'message' => '预计将发送' . $temp_sum . '条短信，但您的可发送短信余额为' . $auth_info['sms_num'] . '条，请先充值'
                ], 400);
        }
        unset($counter[0]);

        //如果有关联，两两求交集，计算各个活动所需发送短信人数所占总人数的百分比
        if ($info['use_correlation'] == 'true') {
            switch (count($counter)) {
                case 1 : {
                    break;
                }
                case 2 : { //两个活动求交集
                    $intersection_01 = array_intersect($all_contact[$key_arr[0]], $all_contact[$key_arr[1]]);   //两个活动的交集电话号码
                    $temp_count = count($intersection_01);
                    $counter[$key_arr[0]] = $counter[$key_arr[0]]['num'] - $temp_count / 2;
                    $counter[$key_arr[1]] = $counter[$key_arr[1]]['num'] - $temp_count / 2;
                    break;
                }
                case 3 : { //三个活动求交集,两两求交集
                    $all_intersection = array_intersect($all_contact[$key_arr[0]], $all_contact[$key_arr[1]], $all_contact[$key_arr[2]]);
                    $all_count = count($all_intersection);
                    $n = count($counter);
                    for ($i = 0; $i < $n; $i++) {
                        for ($j = $i + 1; $j < $n; $j++) {
                            $intersection_ij = array_intersect($all_contact[$key_arr[$i]], $all_contact[$key_arr[$j]]);
                            $intersection_ija = array_intersect($all_intersection, $intersection_ij);
                            $count_ij = count($intersection_ij);
                            $count_ija = count($intersection_ija);
                            $counter[$key_arr[$i]] = $counter[$key_arr[$i]]['num'] - ($count_ij - $count_ija) / 2;
                            $counter[$key_arr[$j]] = $counter[$key_arr[$j]]['num'] - ($count_ij - $count_ija) / 2;
                        }
                        $counter[$key_arr[$i]] = $counter[$key_arr[$i]]['num'] - $all_count / 3;
                    }
                    break;
                }
                default : {
                    return response()->json([
                        'status' => 0,
                        'message' => '未知错误，请稍后尝试'
                    ], 400);
                }
            }
        }

        //构造发送历史记录
        $sms_history = [
            'who_send' => 'enroll_system_' . $act_res['author_id'] . '_' . $info['act_key'],
            'num' => $sum,
            'smsNum' => $temp_sum,
            'act_info' => serialize($counter),
            'failed_act' => serialize($sms_history['failed_act']),
            'content' => serialize($sms_temp),
        ];
//        return $sms_temp;
//        return $send_contact;
//return $sms_history;
//        return $counter;
        return response()->json(['status' => 0, 'message' => '稍后完善 ^_^'], 400);
        //加入队列
    }

    public function getCSV()
    {
        //
    }

}
