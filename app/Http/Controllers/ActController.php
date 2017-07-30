<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActDesign;
use App\Models\FlowInfo;
use App\Http\Requests;
use JWTAuth;
use DB;

class ActController extends Controller
{
    public function __construct()
    {
        $this->middleware('act.base')->only(['update', 'startAct', 'endAct']);
        $this->middleware('act.store')->only('store');
        $this->middleware('act.variables')->only(['update', 'store']);
    }

    /**
     * 显示一个用户所创建的活动列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];
        $allow = ['page', 'per_page', 'sortby', 'sort', 'act_key', 'act_name', 'offset'];
        $info = $request->only($allow);

        $sortby = @ $info['sortby'] ? : 'created_at';
        $sort = @ $info['sort'] ? : 'desc';
        $per_page = @ $info['per_page'] ? : 10;

        //初始化查询条件
        $need = ['activity_id', 'activity_name', 'start_time', 'created_at', 'updated_at', 'status'];
        $condition['eq']['author_id'] = $author_id;
        $condition['sort']['sortby'] = $sortby;
        $condition['sort']['sort'] = $sort;
        $condition['per_page'] = $per_page;
        if (!empty($info['act_key']))
            $condition['eq']['activity_id'] = $info['act_key'];
        if (!empty($info['act_name']))
            $condition['vague']['activity_name'] = $info['act_name'];

        //获取活动列表
        $act = new ActDesign();
        if (!$res = $act->getActList($condition, $need))
            return response()->json(['status' => 0 , 'message' => '无法获取活动列表'], 400);

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $res], 200);
    }

    /**
     * 新建活动，保存该活动的信息
     *
     * @param  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];

        $allow = ['activity_name', 'summary', 'start_time', 'end_time',
            'time_description', 'max_num', 'location'];
        $act_info = $request->only($allow);
        $act_info['author_id'] = $author_id;
        //初始化活动人数限制
        $act_info['num_limit'] = isset($act_info['max_num']) ? $act_info['max_num'] : 0;
        unset($act_info['max_num']);

        //活动和流程初始化
        DB::beginTransaction();
        //创建活动
        $act_model = new ActDesign();
        $act = $act_model->storeAct($act_info);
        if (!$act)
            return response()->json([
                'status' => 0,
                'message' => '活动创建失败',
                'submit_data' => $act_info
            ], 400);

        //初始化报名流程
        $Flow = new FlowInfo();
        $act_arr = json_decode($act, true);
        $act_id = $act_arr['activity_id'];
        $flow_info = [
            'activity_key' => $act_id,
            'flow_name' => '报名阶段',
            'location' => '线上',
            'type' => 0,
        ];
        if(!$flow = $Flow->storeNewFlow($flow_info)) {
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => '报名阶段初始化失败',
                'submit_data' => $act_info
            ], 400);
        }

        if (!$act_model->updateActInfo(['activity_id' => $act['activity_id']], ['enroll_flow' => $flow['flow_id']])) {
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => '报名阶段初始化失败',
                'submit_data' => $act_info
            ], 400);
        }
        DB::commit();

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 200);
    }

    /**
     * 显示一个活动的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $act_key)
    {
        //解析token
        $token_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $token_info['sub'];

        //检查是否有该活动
        $act = new ActDesign();
        $condition = ['activity_id' => $act_key];
        $need = ['author_id', 'num_limit', 'current_num', 'current_flow', 'activity_name',
            'time_description', 'summary', 'start_time', 'end_time', 'created_at'];
        if (!$res = $act->getActInfo($condition, $need))
            return response()->json(['status' => 0, 'message' => '获取详细信息失败'], 404);

        //检查活动所有者是否与token一致
        if ($res['author_id'] != $author_id)
            return response()->json(['status' => 0, 'message' => '非法请求'], 401);

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $res], 200);
    }

    /**
     * 更新一个活动的信息
     *
     * @param  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $act_key)
    {
        $act = new ActDesign();
        $res = $act->getActInfo(['activity_id' => $act_key], '*');

        if (strtotime($res['start_time']) < time())
            //return response()->json(['status' => 0, 'message' => '由于活动已经开始，仅可修改活动的最大人数限制']);
            $allow = ['max_num', 'time_description', 'summary'];
        else
            $allow = ['activity_name', 'summary', 'start_time', 'end_time', 'max_num', 'location', 'time_description'];
        $info = $request->only($allow);
        $info['num_limit'] = $info['max_num'];
        unset($info['max_num']);

        if ($info['num_limit'] > 0)
            if ($res['current_num'] >= $info['num_limit'])
                return response()->json(['status' => 0, 'message' => '已报名人数超过了参数max_num的值，无法修改']);

        $update_info = unset_empty($info);

        if (!$act->updateActInfo(['activity_id' => $act_key],$update_info))
            return response()->json(['status' => 0, 'message' => '修改信息失败'], 400);

        return response()->json(['status' => 0, 'message' => '信息修改成功'], 201);
    }

    public function startAct($act_key)
    {
        //将一个活动的status 设置为 1'
        $act = new ActDesign();
        $update_info['status'] = 1;
        if (!$act->updateActInfo(['activity_id' => $act_key] ,$update_info))
            return response()->json(['status' => 0, 'message' => '活动开启失败'], 403);

        return response()->json(['status' => 1, 'message' => '活动开启成功'], 200);
    }

    public function endAct($act_key)
    {
        //将一个活动的status 设置为 0
        $act = new ActDesign();
        $update_info['status'] = 0;
        if (!$act->updateActInfo(['activity_id' => $act_key] ,$update_info))
            return response()->json(['status' => 0, 'message' => '活动关闭失败'], 403);

        return response()->json(['status' => 1, 'message' => '活动关闭成功'], 200);
    }

    /**
     * 更改一个活动的状态
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($act_key)
    {
        //将一个活动的status 设置为 -1
        $act = new ActDesign();
        $update_info['status'] = -1;
        if (!$act->updateActInfo(['activity_id' => $act_key] ,$update_info))
            return response()->json(['status' => 0, 'message' => '活动删除失败'], 403);

        //级联更改流程状态
        return response()->json(['status' => 1, 'message' => '活动删除成功'], 200);
    }


    /**
     * 暂时弃用
     * @param $act_info
     * @return mixed
     */
    private function getActKey($act_info)
    {
        //生成一个活动关键字，暂时定为与表的activity_id 相同
        return $act_info;
    }

    /**
     * 暂时弃用
     * @param $act_info
     * @return int
     */
    private function getActSecret($act_info)
    {
        //生成活动的密钥，暂时使用 time() 代替
        return time();
    }
}
