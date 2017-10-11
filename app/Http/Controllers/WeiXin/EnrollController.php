<?php

namespace App\Http\Controllers\WeiXin;

use App\Models\DeptInfo;
use App\Models\UserData;
use App\Models\ActDesign;
use App\Models\ApplyData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * 网校微信端报名控制器（暂时）
 * Class EnrollController
 * @package App\Http\Controllers\WeiXin
 */
class EnrollController extends Controller
{
    private $getStuInfoByOpenidUrl = "https://hongyan.cqupt.edu.cn/MagicLoop/index.php?s=/addon/UserCenter/UserCenter/getStuInfoByOpenId&";

    private $bindStuInfoUrl = "https://wx.idsbllp.cn/MagicLoop/index.php?s=/addon/Bind/Bind/bind/openid/{openid}/token/gh_68f0a1ffc303";

    public function fixEnrollInfo(Request $request)
    {
//        $update_data = [
//            'act_name' => 'test',
//            'current_step' => 22
//        ];
//        return ApplyData::where(['activity_key' => 1000])->update($update_data);
        $start_act = 1023;
        $end_act = 1061;
        for ($i = $start_act; $i <= $end_act; $i++) {
            if ($i == 1037 || $i == 1038 || $i == 1039){
                continue;
            }
            $act = ActDesign::find($i);
            if (empty($act))
                continue;
            $condition = [
                'activity_key' => $act->activity_id
            ];
            $update_data = [
                'act_name' => $act->activity_name,
                'current_step' => $act->enroll_flow,
            ];
            ApplyData::where($condition)->update($update_data);
        }
        return 'success';
    }

    public function getUserInfo(Request $request)
    {
        $user_info = $request->session()->get("weixin.user");
        //准备校级组织报名获得列表,投机写法
//        $depts  = DeptInfo::where('dept_id', '<=', 36)->get();
//        $dept_info = [];
//        foreach ($depts as $key => $value) {
//            $arr = explode("|", $value->dept_name);
//            if (!isset($dept_info[$arr[0]]))
//                $dept_info[$arr[0]] = [];
//            array_push($dept_info[$arr[0]], $arr[1]);
//        }
//        return $dept_info;

        $account_arr = ['红岩网校工作站', '校学生会', '科技联合会', '青年志愿者协会', '社团联合会', '大学生艺术团', '团委办公室', '团委组织部', '团委宣传部'];
        $admin_id = [9, 3, 4, 6, 7, 8, 10, 11, 12];
        $act_info = [];
        $act_m = new ActDesign();
        for ($i = 0; $i < count($admin_id); $i++) {
            $condition['author_id'] = $admin_id[$i];
            $condition['status'] = 1;
            $need = ['activity_id', 'activity_name'];
            $act_info[$account_arr[$i]] = $act_m->getActInfoArray($condition, $need);
        }
        //首先查看本地数据库中是否有相应的信息
        $user_data_m = new UserData();
        $need = ['user_id',  'contact', 'stu_code', 'wx_nickname', 'wx_avatar', 'full_name'];
        $stu_info = $user_data_m->getUserInfo(['wx_openid' => $user_info['openid']], $need);
        if ($stu_info)
            return response()->json([
                'status' => 1,
                'message' => 'success',
                'data' => ['stu_info' => $stu_info, 'act_info' => $act_info]
            ], 200);

        //用openid请求学生的详细信息
        $res = $this->send(($this->getStuInfoByOpenidUrl . "openId=" . $user_info['openid']));
        $stu_info = $res['data'];
        //$res['status'] = 400;
        if (empty($res) || (isset($res['status']) && $res['status'] != 200))
            return response()->json([
                'status' => 0,
                'message' => '暂未绑定',
                'redirectUrl' =>  str_replace_first("{openid}", $user_info['openid'], $this->bindStuInfoUrl) .
                    '&redirect=../activity/wx/index'
            ], 200);

        $attributes = ['stu_code' => $stu_info['usernumber']];
        $values = [
            'stu_code' => $stu_info['usernumber'],
            'full_name' => $stu_info['realname'],
            'password' => Hash::make($stu_info['idnum']),
            'wx_openid' => $user_info['openid'],
            'wx_nickname' => $user_info['nickname'],
            'wx_avatar' => $user_info['headimgurl'],
            'gender' => $stu_info['gender'],
            'college' => $stu_info['collage'],
            'grade' => substr($stu_info['usernumber'], 0, 4)
        ];

        $user = UserData::updateOrCreate($attributes, $values);

        //unset($values['password']);
        $request->session()->set('user', $user['user_id']);
        $request->session()->save();

        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => ['stu_info' => $user, 'act_info' => $act_info]
        ], 200);
    }

    public function enroll(Request $request)
    {
        $require = ['act_key', 'contact'];
        $enroll_info = $request->only($require);
        $enroll_info['user'] = $request->session()->get('weixin.user');

        if (empty($enroll_info['user']))
            return response()->json(['status' => 0, 'message' => '非法访问'], 400);
        if (empty($enroll_info['act_key']))
            return response()->json(['status' => 0, 'message' => 'act_key必需'], 400);
        if (!isset($enroll_info['contact']))
            return response()->json(['status' => 0, 'message' => 'contact必需'], 400);
        else if (!check_phoneNum($enroll_info['contact']))
            return response()->json(['status' => 0, 'message' => 'contact格式有误'], 400);
        if (!$user_info = UserData::where('wx_openid', $enroll_info['user']['openid'])->first())
            return response()->json(['status' => 0, 'message' => '未找到该用户'], 400);

        $user_id = $user_info['user_id'];

        //如果联系方式不一致，更新用户的联系方式
        if (empty($user_info->contact) || $enroll_info['contact'] != $user_info->contact) {
            $user_info->contact = $enroll_info['contact'];
            $user_info->save();
        }

        //多活动同时报名,先检查个活动是否开启了报名、时间对否、人数是否超过限制
        $act_key = explode(',' , $enroll_info['act_key']);
        $act_m = new ActDesign();
        $flag = 1;
        $error_mes = [];
        $act_info = [];
        foreach ($act_key as $value) {
            $condition = [
                'activity_id' => $value
            ];
            $need = ['activity_name', 'start_time', 'enroll_flow',
                'end_time', 'status', 'current_num', 'num_limit'];
            if (!$act = $act_m->getActInfo($condition, $need)) {
                $flag = 0;
                $error_mes[$value] = [];
                array_push($error_mes[$value], 'not found');
            } else {
                $error_mes[$act['activity_name']] = [];
                //检查时间
                if (strtotime($act['end_time']) < time()) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动已结束');
                }
                //检查活动的状态
                if ($act['status'] <= 0) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动还未开启报名');
                }
                //检查活动报名人数是否已达到上限
                if ($act['num_limit'] > 0 && $act['current_num'] >= $act['num_limit']) {
                    $flag = 0;
                    array_push($error_mes[$act['activity_name']], '该活动报名人数已达到上限');
                }

                $act_info[$value] = array($act['activity_name'], $act['enroll_flow']);
            }
        }

        //如果错误信息不为空， 则直接返回
        if ($flag != 1)
            return response()->json([
                'status' => 0,
                'message' => $error_mes,
            ], 403);

        //如果已经报名，则更新报名信息，分别存储已报名合未报名
        $data_m = new ApplyData();
        $i = 0; //辅助变量
        foreach ($act_key as $key => $value) {
            $condition = [
                'activity_key' => $value,
                'user_id' => $user_id
            ];
            if ($data_m->applyDataExists($condition)) {
                $already_enroll[$i] = $value;
                unset($act_key[$key]);
                $i++;
            }
        }

        DB::beginTransaction();
        //将报名信息插入
        $apply_data = [ //初始化报名信息
            'user_id' => $user_id,
            'full_name' => $user_info['full_name'],
            'gender' => $user_info['gender'],
            'college' => $user_info['college'],
            'contact' => $user_info['contact'],
            'stu_code' => $user_info['stu_code'],
            'grade' => $user_info['grade'],
            'status' => 1
        ];

        foreach ($act_key as $value) {
            $apply_data['activity_key'] = $value;
            $apply_data['act_name'] = $act_info[$value][0];
            $apply_data['current_step'] = $act_info[$value][1];
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

        if (!empty($already_enroll)) {
            $update_data['contact'] = $apply_data['contact'];
            foreach ($already_enroll as $value) {
                $condition = [
                    'activity_key' => $value,
                    'user_id' => $user_id
                ];
                if (!ApplyData::where($condition)->update($update_data)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 0,
                        'message' => '报名失败'
                    ], 403);
                }
            }
        }
        DB::commit();

        return response()->json([
            'status' => 1,
            'message' => 'success'
        ], 200);
    }

    private function send($url, $get = false)
    {
//        return [
//            'realname' => "wws",
//            'idnum' => "046952",
//            'usernumber' => '2015211515',
//            'gender' => '女',
//            'college' => '计算机',
//        ];
        $ch = curl_init();
        // 不需要返回header
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 返回JSON字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // POST格式发送
        if (!$get) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
        // 设置URL
        curl_setopt($ch, CURLOPT_URL, $url);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    private function build_query(array $data)
    {
        return http_build_query($data, '', '&', PHP_QUERY_RFC1738);
    }
}
