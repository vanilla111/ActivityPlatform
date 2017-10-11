<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    protected $table = 'sms_history';

    protected $fillable = ['who_send', 'msg', 'code', 'sub_code', 'sub_msg', 'model', 'request_id', 'act_key', 'flow_id',
        'name', 'stu_code', 'contact', 'content', 'other_info'];

    public function getHistoryList($condition, $need, $page)
    {
        $res = $this;
        if (!isset($condition['code']) || $condition['code'] == 0)
            $res = $this->where($condition);
        else
            $res->where('who_send', $condition['who_send'])
                ->where('code', '>', 0);

        return $res->select($need)->orderBy('created_at', 'desc')->paginate($page['per_page']);
    }
}
