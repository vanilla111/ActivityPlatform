<?php

namespace App\Http\Controllers;

use App\Models\Sms;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\ActDesign;
use App\Models\FlowInfo;
use App\Models\ApplyData;
use JWTAuth;
use Illuminate\Support\Facades\DB;

class FlowController extends Controller
{
    public function __construct()
    {
        $this->middleware('flow.base')->only(['update', 'destroy']);
        $this->middleware('flow.store')->only(['store']);
        $this->middleware('flow.variables')->only(['store', 'update']);
    }

    /**
     * 显示流程列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $auth_id = $auth['sub'];
        $act_key = $request->get('act_key');
        $condition = [
            'author_id' => $auth_id,
            'activity_id' => $act_key
        ];
        $need = ['activity_id'];

        $act_m = new ActDesign();
        if (!$act = $act_m->getActInfo($condition, $need)) {
            return response()->json(['status' => 0, 'message' => '未找到该活动'], 400);
        }

        $condition = ['activity_key' => $act_key];
        $list_need = ['flow_name', 'flow_id'];
        $flow_m = new FlowInfo();
        if (!$res = $flow_m->getFlowList($condition, $list_need))
            return response()->json(['status' => 0, 'message' => '获取流程列表失败'], 400);

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $res], 200);
    }

    /**
     * 创建一个新的流程
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $allow = ['flow_name', 'act_key', 'location', 'type',
            'start_time', 'end_time', 'time_description', 'sms_temp_id'];
        $flow_info = $request->only($allow);
        $auth_id = $request->get('auth_id');

        //先找到这个活动
        $act_m = new ActDesign();
        if (!$act = $act_m->getActInfo(['activity_id' => $flow_info['act_key']], '*'))
            return response()->json(['status' => 0, 'message' => '该活动不存在'], 404);
        //再确认是否是对应用户操作
        if ($act['author_id'] != $auth_id)
            return response()->json(['status' => 0, 'message' => '非法操作'], 403);
        //检查活动状态
        if ($act['status'] < 0)
            return response()->json(['status' => 0, 'message' => '该活动不存在'], 404);
        //检查活动是否已经结束
        if (strtotime($act['end_time']) < time())
            return response()->json(['status' => 0, 'message' => '活动已结束，无法继续添加流程'], 403);

        $data = [
            'activity_key' => $flow_info['act_key'],
            'flow_name' => $flow_info['flow_name'],
            'location' => $flow_info['location'],
            'type' => $flow_info['type'],
            'sms_temp_id' => $flow_info['sms_temp_id'],
            'time_description' => $flow_info['time_description'],
        ];

        $flow_m = new FlowInfo();
        if (!$flow_m->storeNewFlow($data))
            return response()->json(['status' => 0, 'message' => '创建失败'], 500);

        return response()->json(['status' => 1, 'message' => 'success'], 201);
    }

    /**
     * 展示一个流程的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($flow_id)
    {
        $flow_m = new FlowInfo();
        $auth = JWTAuth::decode(JWTAuth::getToken());
        $auth_id = $auth['sub'];
        $condition = [
            'flow_id' => $flow_id,
        ];
        $need = ['flow_name', 'location', 'type', 'start_time', 'end_time', 'time_description',
            'sms_temp_id', 'created_at'];
        if (!$res = $flow_m->getFlowInfo($condition, $need))
            return response()->json(['status' => 0, 'message' => '未找到该流程'], 403);

        if (!empty($res['sms_temp_id'])) {
            $sms_m = new Sms();
            $need = ['content', 'variables'];
            $sms_temp = $sms_m->getSmsInfo($res['sms_temp_id'], $auth_id, $need);
            $res['sms_temp'] = $sms_temp['content'];
            $res['sms_variables'] = $sms_temp['variables'];
        }

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $res], 200);
    }

    /**
     * 更新一个流程
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $flow_id)
    {

        $allow = ['flow_name', 'location', 'type', 'start_time', 'end_time', 'time_description',
            'sms_temp_id'];
        $info = $request->only($allow);
        $update_info = unset_empty($info);

        $flow_m = new FlowInfo();
        if (!$flow_m->updateFlowInfo(['flow_id' => $flow_id], $update_info))
            return response()->json(['status' => 0, 'message' => '修改失败'], 403);

        return response()->json(['status' => 1, 'message' => '修改成功'], 201);
    }

    /**
     * 删除一个流程，删除条件极为重要
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($flow_id)
    {
        // 删除规则:
        // 1.该流程不能有申请信息
        $apply_data_m = new ApplyData();
        if ($res = $apply_data_m->hasApplyData(['current_step' => $flow_id]))
            return response()->json([
                'status' => 0,
                'message' => '该流程尚有人员信息，删除失败'
            ], 400);

        return $res;
        if ($res['type'] <= 0 )
            return response()->json([
                'status' => 0,
                'message' => '该流程为报名流程，无法删除'
            ], 400);

        $update = ['status' => -1];
        $flow_m = new FlowInfo();
        $flow_m->updateFlowInfo(['flow_id' => $flow_id], $update);

        return response()->json(['status' => 1, 'message' => 'success'], 204);

    }

}
