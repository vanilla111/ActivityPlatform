<?php

namespace App\Http\Middleware\ApplyData;

use App\Http\Requests\Request;
use App\Models\AdminSmsTemp;
use App\Models\FlowInfo;
use App\Models\Sms;
use App\Models\SmsNum;
use App\Models\SmsProvider;
use Closure;

class SendSms
{
    /**
     * 身份，流程，短信模版验证合法及有效性
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth = json_decode(get_detail_auth_info()->content(), true);
        $author_id = $auth['user']['admin_id'];
        $author_pid = $auth['user']['pid'];
        $info = $request->all();

        $require = ['flow_id', 'enroll_id'];
        foreach ($require as $key)
            if (empty($info[$key]))
                return response()->json(['status' => 0, 'message' => $key . '参数必须'], 400);

        $flow_id = explode(',', $info['flow_id']);
        $enroll_id = explode(',', $info['enroll_id']);

        //如果多个流程
        if (count($flow_id) > 1) {
            if (count($flow_id) > 5)
                return response()->json([
                    'status' => 0,
                    'message' => '流程数过多'
                ], 400);

            //检查这些流程的短信模版id是否一致
            $flow_arr = FlowInfo::whereIn('flow_id', $flow_id)->select('sms_temp_id')->get();
            if (count($flow_arr) <= 0)
                return response()->json([
                    'status' => 0,
                    'message' => '流程ID有误'
                ], 400);

            $temp_id = $flow_arr[0]['sms_temp_id'];
            foreach ($flow_arr as $key => $value) {
                if ($value['sms_temp_id'] != $temp_id)
                    return response()->json([
                        'status' => 0,
                        'message' => '短信模版不一致，无法发送'
                    ], 500);
            }

            //检查sms_author_id是否是该用户及其母账户的
            $sms =  Sms::where('temp_id', $temp_id)->first();
            $sms_author_id = $sms['author_id'];
            if ($sms_author_id != $author_id && $sms_author_id != $author_pid)
                return response()->json([
                    'status' => 0,
                    'message' => '非法访问'
                ], 400);
        } else {
            //如果只有一个流程，检查该流程是否属于该用户
            $flow  = FlowInfo::where('flow_id', $info['flow_id'])->select('sms_temp_id')->first();
            if (empty($flow))
                return response()->json([
                    'status' => 0,
                    'message' => '非法访问，或该流程没有设置短信模版'
                ], 400);
            $sms = Sms::where('temp_id', $flow['sms_temp_id'])->first();
            if ($sms['author_id'] != $author_id && $sms['author_id'] != $author_pid)
                return response()->json([
                    'status' => 0,
                    'message' => '非法访问'
                ], 400);
        }

        //检查短模版是否有效
        $res = $this->checkSmsTemp($request, $sms);
        if (!$res['status'])
            return response()->json([
                'status' => 0,
                'message' => $res['message']
            ], 400);

        //预计将发送的条数，检查短信余额是否充足
        if ($author_pid > 0 )
            $sms_num = SmsNum::where('admin_id', $author_pid)->first();
        else
            $sms_num = SmsNum::where('admin_id', $author_id)->first();

        if (empty($sms_num) || count($enroll_id) > $sms_num['sms_num'])
            return response()->json([
                'status' => 0,
                'message' => '剩余短信条数不足（如果短信内容超过76字，按两条或者更高计算)'
            ], 400);

        //准备发送短信必需的内容
        // 1. 短信模版 sms  & admin_temp
        // 2. 短信变量赋值
        // 3. 接收号码
        $request->attributes->add(compact('enroll_id'));
        $request->attributes->add(compact('sms'));
        $request->attributes->add(compact('author_id'));
        $request->attributes->add(compact('author_pid'));
        return $next($request);
    }


    /**
     * 检查短模版是否有效
     */
    protected function checkSmsTemp($request,Sms $sms)
    {
        if ($sms['status'] <= 0)
            return ['status' => false, 'message' => '短信模版已失效（可能已被删除）'];

        $admin_temp = AdminSmsTemp::where('admin_temp_id', $sms['admin_temp_id'])->first();
        if (empty($admin_temp) || $admin_temp['status'] <= 0)
            return ['status' => false, 'message' => '短信模版已失效（原始模版失效）'];

//        $sms_provider = SmsProvider::where('id', $admin_temp['sms_provider'])->first();
//        if (empty($sms_provider) || $sms_provider['status'] <= 0)
//            return ['status' => false, 'message' => '短信模版已失效（短信服务提供商失效）'];

        $request->attributes->add(compact('admin_temp'));
        //$request->attributes->add(compact('sms_provider'));

        return ['status' => true];
    }

    /**
     * 判断发送的短信是静态还是动态
     */
    protected function isDynamic(Sms $sms)
    {
        if ($sms['type'] == 1)
            return true;

        return false;
    }
}
