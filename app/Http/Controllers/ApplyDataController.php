<?php

namespace App\Http\Controllers;

use App\Jobs\SendSms;
use App\Models\FlowInfo;
use Illuminate\Http\Request;
use App\Models\ApplyData;
use App\Models\ActDesign;
use App\Models\UserData;
use App\Jobs\ChangeStep;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;
use JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

class ApplyDataController extends Controller
{
    public function __construct()
    {
//        $this->middleware('data.actkey')->only(['index']);
        $this->middleware('data.enrollid')->only(['show', 'update', 'destroy']);
        $this->middleware('data.flowid')->only(['store', 'operation', 'isSendSmsAndUpgrade', 'uploadExcelFile', 'oneKeyUpdate']);
        $this->middleware('data.base')->only(['index', 'store', 'show', 'destroy', 'uploadExcelFile', 'oneKeyUpdate']);
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
        $allow = ['page', 'per_page', 'sortby', 'sort', 'act_key',
            'flow_id', 'college', 'gender', 'name', 'stu_code', 'was_send_sms'];
        $info = $request->only($allow);

        //有关参数初始化
        $info['per_page'] = $info['per_page'] ? : 20;
        $info['sortby']   = $info['sortby'] ? : 'updated_at';
        $info['sort']     = $info['sort'] ? : 'desc';
        $info['current_step'] = $info['flow_id'] ? explode(',', $info['flow_id']) : $request->get('enroll_flow');
        $info['was_send_sms'] = $info['was_send_sms'] ? 1 : 0;
        unset($info['flow_id']);

        $need = ['enroll_id', 'user_id', 'stu_code', 'full_name',
            'contact', 'college', 'gender', 'score', 'evaluation', 'was_send_sms'];

        //查询
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
        $allow = ['college', 'stu_code', 'password', 'contact', 'flow_id', 'full_name', 'grade', 'gender'];
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

//        //检查本地是否有此学生，如果没有，则添加一条
//        $user_m = new UserData();
//        if (!$user = $user_m->getUserInfo(['stu_code' => $info['stu_code']], 'user_id')){
//            $user_info = [
//                'full_name' => $userInfo['name'],
//                'grade'     => $userInfo['grade'],
//                'gender'    => $userInfo['gender'],
//                'college'   => $info['college'],
//                'contact'   => $info['contact'],
//                'stu_code'  => $info['stu_code'],
//                'password'  => Hash::make($info['password'])
//            ];
//            if (!$new_user = $user_m->storeUserInfo($user_info))
//                return response()->json([
//                    'status'  => 0,
//                    'message' => '添加失败'
//                ], 403);
//        }

        //添加一条申请信息
        $apply_info = [
            'user_id'      => -1,
            'activity_key' => $act_key,
            'current_step' => $info['flow_id'],
            'stu_code'     => $info['stu_code'],
            'contact'      => $info['contact'],
            'college'      => $info['college'],
            'act_name'     => $request->get('act_name'),
            //如果直接添加的话
            'full_name'    => $info['full_name'],
            'grade'        => substr($info['stu_code'], 0, 4),
            'gender'       => $info['gender']
        ];
        if (!empty($userInfo)) {
            $apply_info['full_name'] = $userInfo['full_name'];
            $apply_info['grade'] = $userInfo['grade'];
            $apply_info['gender'] = $userInfo['gender'];
        }

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
        //着重更新分数和评价字段
        $allow = ['full_name', 'stu_code', 'gender','college', 'contact', 'score', 'evaluation'];
        $info = $request->only($allow);

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
     * 一键更新申请信息的分数信息
     */
    public function oneKeyUpdate(Request $request)
    {
        $act_key = $request->get('act_key');
        $flow_id = $request->get('flow_id');
        $score_data = json_decode($request->get('scoreData'), true);
        foreach ($score_data as $key => $value) {
            $score_data[$key]['activity_key'] = $act_key;
            $score_data[$key]['current_step'] = $flow_id;
        }

        $apply_data_m = new ApplyData();
        if (!$res = $apply_data_m->updateBatch($score_data))
            return response()->json(['status' => 0, 'message' => '数据为空或者更新失败'], 400);

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $res
        ], 200);
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
            $this->dispatch(new ChangeStep($value, $act_key, $new_flow['flow_id']));
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
        $need = ['enroll_id', 'contact'];
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
        //return $static_var;
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
                new SendSms($value['contact'], $vars, $sms_free_sign_name, $sms_id, $author_id, $author_pid, $content, $value->enroll_id)
            );
        }
        return response()->json(['status' => 1, 'message' => '发送任务已进入队列，如有失败请重新尝试'], 200);
    }

    public function getExcelFile(Request $request)
    {
        $allow = ['sortby', 'sort', 'act_key',
            'flow_id', 'college', 'gender', 'name', 'stu_code', 'was_send_sms'];
        $info = $request->only($allow);

        //有关参数初始化
        $info['sortby']   = $info['sortby'] ? : 'updated_at';
        $info['sort']     = $info['sort'] ? : 'desc';
        $info['current_step'] = $info['flow_id'] ? explode(',', $info['flow_id']) : $request->get('enroll_flow');
        $info['was_send_sms'] = $info['was_send_sms'] ? 1 : 0;
        unset($info['flow_id']);

        $need = ['stu_code', 'full_name', 'contact', 'college', 'gender', 'score', 'evaluation'];

        //查询
        $new_info = unset_empty($info);
        $data_m = new ApplyData();
        $res = $data_m->getApplyDataForExcel($new_info, $need);
        if (!$res)
            return response()->json([
                'status'  => 0,
                'message' => '获取信息列表失败'
            ], 403);

        $cell_data = [
            [getConditionString($new_info)],
            ['根据 ' . $info['sortby'] . ' ' . ($info['sort'] == 'desc' ? '降序' : '升序') . '排序'],
            ['文件生成时间:' . date("Y-m-d H:i", time())],
            ['', '学号', '姓名', '电话', '学院', '性别', '分数', '评价']
        ];

        foreach ($res as $key => $value) {
            array_push($cell_data, array(
               '', $value->stu_code, $value->full_name, $value->contact, $value->college, $value->gender, $value->score, $value->evaluation
            ));
        }

        return Excel::create('申请信息',function($excel) use ($cell_data){
            $excel->sheet('info', function($sheet) use ($cell_data){
                $sheet->rows($cell_data);
            });
        })->export('xls');
    }

    public function uploadExcelFile(Request $request)
    {
        $act_key = $request->get('act_key');
        $flow_id = $request->get('flow_id');

        Excel::load($request->file('excel'), function($reader) use($act_key, $flow_id){
            //文件默认按照姓名学号联系方式的格式存储
            $data = $reader->noHeading()->all();
            $insert_data = [];
            for ($i = 0; $i < count($data); $i++) {
                array_push($insert_data, array(
                    'user_id' => -1,
                    'activity_key' => $act_key,
                    'current_step' => $flow_id,
                    'full_name' => $data[$i][0],
                    'stu_code' => intval($data[$i][1]),
                    'contact' => intval($data[$i][2])
                    ));
            }
            DB::table("apply_data")->insert($insert_data);
        });

        return response()->json(['status' => 1, 'message' => '导入成功'], 200);
    }

}
