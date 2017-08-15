<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Models\SmsHistory;
use App\Models\SmsNum;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Flc\Alidayu\Client;
use Flc\Alidayu\App;
use Flc\Alidayu\Requests\AlibabaAliqinFcSmsNumSend;

class SendSms extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

    private $SMS_CONFIG = ['app_key' => '23470529', 'app_secret' => '772387435d3db7f60a7ab9d6cbbf5f49'];

    private $authorId;
    private $authorPid;
    private $variables;
    private $phoneNum;
    private $smsFreeSignName;
    private $smsId;
    private $content;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $vars, $smsSignName, $smsId, $author_id, $author_pid, $content = '')
    {
        $this->phoneNum = $phone;
        $this->variables = $vars;
        $this->smsFreeSignName = $smsSignName;
        $this->smsId = $smsId;
        $this->authorId = $author_id;
        $this->authorPid = $author_pid;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->authorPid <= 0)
            $sms_num = SmsNum::where('admin_id', $this->authorId)->first();
        else
            $sms_num = SmsNum::where('admin_id', $this->authorPid)->first();

        if ($sms_num['sms_num'] - 1 < 0)
            return ;

        //发送短信
        $client = new Client(new App($this->SMS_CONFIG));
        $req    = new AlibabaAliqinFcSmsNumSend;

        $req->setRecNum($this->phoneNum)
            ->setSmsParam($this->variables)
            ->setSmsFreeSignName($this->smsFreeSignName)
            ->setSmsTemplateCode($this->smsId);

        $res = $client->execute($req);

        //记录日志
        $history = [];
        $result = (array)($res);
        if (isset($result['result'])) {
            $result_arr = json_decode(json_encode($result), true);
            if ($result_arr['result']['err_code'] == 0 && $result_arr['result']['success']) {
                //认为发送成功 记录成功日志  并将短信余额减小
                $history = [
                    'who_send' => $this->authorId,
                    'code' => $result_arr['result']['err_code'],
                    'request_id' => $result['request_id'],
                    'msg' => $result_arr['result']['msg'],
                    'model' => $result_arr['result']['model'],
                ];
                $sms_num->decrement('sms_num');
            }
        } else {
            //认为发送失败
            $history = [
                'who_send' => $this->authorId,
                'code' => $result['code'],
                'msg' => $result['msg'],
                'sub_code' => $result['sub_code'],
                'sub_msg' => $result['sub_msg'],
                'request_id' => $result['request_id']
            ];

        }
        SmsHistory::create($history);
    }
}