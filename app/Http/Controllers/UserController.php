<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserData;
use App\Models\ActDesign;
use App\Models\ApplyData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('user.base')->only(['update', 'show', 'Enroll', 'getUserApplyData']);
        $this->middleware('user.enroll')->only('Enroll');
        $this->middleware('user.variables')->only(['update']);
    }

    public function toLogin(Request $request)
    {
        $allow = ['stu_code', 'password'];
        $user_info = $request->only($allow);

        if (empty($user_info['stu_code']) || empty($user_info['password']))
            return response()->json(['status' => 0, 'message' => '学号密码不能为空']);

        //先查询本地数据库有无学生信息
        $user_m = new UserData();
        if($user = $user_m->getUserInfo(['stu_code' => $user_info['stu_code']], ['password', 'user_id'])) {
            if(Hash::check($user_info['password'], $user['password'])) {
                $request->session()->put('user', $user['user_id']);
                $request->session()->save();
                return response()->json(['status' => 1, 'message' => '登录成功', 'data' => $user_info['stu_code']], 200);
            } else
                return response()->json(['status' => 0, 'message' => '学号或密码不匹配'], 400);
        }

        //如果没有，核对信息后插入
        $stu_info = verify($user_info['stu_code'], $user_info['password']);

        if ($stu_info['status'] != 200)
            return response()->json(['status' => 0, 'message' => '学号与密码不匹配'], 400);

        //类似注册，将学生信息插入数据库
        $user_info['password'] = Hash::make($user_info['password']);
        $user_info['gender'] = $stu_info['data']['gender'];
        $user_info['grade'] = $stu_info['data']['grade'];
        $user_info['full_name'] = $stu_info['data']['name'];
        $user_info['college'] = $stu_info['data']['college'];
        //return $stu_info;
        if(!UserData::create($user_info))
            return response()->json(['status' => 0, 'message' => '登录失败，请重新尝试登录'], 400);

        $request->session()->put('user', $user['user_id']);
        $request->session()->save();
        return response()->json(['status' => 1, 'message' => '登录成功', 'data' => $user_info['stu_code']], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * 显示一个用户的详细信息
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $stu_code)
    {
        $user_m = new UserData();
        $condition = [
            'stu_code' => $stu_code,
            'user_id' => $request->session()->get('user')
        ];
        $need = ['stu_code', 'full_name', 'gender', 'college', 'contact'];
        if (!$user_info = $user_m->getUserInfo($condition, $need))
            return response()->json(['status' => 0, 'message' => '获取详细信息失败'], 403);

        return response()->json(['status' => 1, 'message' => 'success', 'data' => $user_info], 200);
    }

    /**
     * 信息补完计划...
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $stu_code)
    {
        $allow = ['name', 'contact', 'college'];
        $info = $request->only($allow);

        $info['full_name'] = $info['name'];
        unset($info['name']);
        $update_info = unset_empty($info);

        $user_m = new UserData();
        $condition = [
            'stu_code' => $stu_code,
            'user_id' => $request->session()->get('user')
        ];
        if (!$user_m->updateUserInfo($condition, $update_info))
            return response()->json(['status' => 0, 'message' => '修改信息失败'], 403);

        return response()->json(['status' => 1, 'message' => 'success'], 201);
    }

    public function Enroll(Request $request)
    {
        $user_info = $request->get('user');   //用户信息
        $act_key = $request->get('act_key');  //未报名活动key
        $user_id = $request->get('user_id');  //用户ID
        $act_info = $request->get('act_info');
        $already_enroll = $request->get('already_enroll');  //已报名活动key，为其更新报名信息

        //return $user_info;
        //return $act_key;
        //return $act_name;
        //return $already_enroll;

        DB::beginTransaction();
        //将报名信息插入
        $apply_data = [ //初始化报名信息
            'user_id' => $user_id,
            'full_name' => $user_info['full_name'],
            'gender' => $user_info['gender'],
            'college' => $user_info['college'],
            'contact' => $user_info['contact'],
            'stu_code' => $user_info['stu_code'],
            'grade' => $user_info['grade']
        ];

        foreach ($act_key as $value) {
            $apply_data['activity_key'] = $value;
            $apply_data['act_name'] = $act_info[$value][0];
            $apply_data['current_step'] = $act_info[$value][1];
//            $act = ActDesign::where('activity_id', '=', $value)->select(['author_id', 'num_limit'])->first();
//            $apply_data['act_author'] = $act['author_id'];
            //存入表中
            if(!ApplyData::create($apply_data)) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => '报名失败'
                ], 403);
            }
            //更新人数报名人数
            if (!ActDesign::where(['activity_id' => $value])->increment('current_num', 1)) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => '报名人数更新失败'
                ], 403);
            }
        }

        if (!empty($already_enroll))
            foreach ($already_enroll as $value ) {
                $apply_data['activity_key'] = $value;
                $condition = [
                    'activity_key' => $value,
                    'user_id' => $user_id
                ];
                if (!ApplyData::where($condition)->update($apply_data)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 0,
                        'message' => '报名失败'
                    ], 403);
                }
            }
        DB::commit();

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 201);
    }

    public function getUserApplyData(Request $request)
    {
        //
        $allow = ['page', 'per_page', 'sortby', 'sort', 'name'];
        $info = $request->only($allow);
        $user_id = $request->get('user_id');
        $stu_code = $request->get('stu_code');
        $need = ['stu_code'];
        if (!empty($user_id)) {
            $user = (new UserData())->getUserInfo(['user_id' => $user_id], $need);
            $info['user_id'] = $user['user_id'];
        }
        else {
            $user = (new UserData())->getUserInfo(['stu_code' => $stu_code], $need);
            $info['stu_code'] = $user['stu_code'];
        }

        //有关参数初始化
        $info['per_page'] = $info['per_page'] ? : 10;
        $info['sortby']   = $info['sortby'] ? : 'created_at';
        $info['sort']     = $info['sort'] ? : 'desc';

        $need = ['activity_key', 'act_name', 'current_step', 'status', 'score', 'evaluation', 'created_at'];

        $data_m = new ApplyData();
        if (!$data = $data_m->getApplyDataHistory($info, $need))
            return response()->json([
                'status' => 0,
                'message' => '申请历史获取失败'
            ], 400);

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => $data,
        ], 200);
    }
}
