<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    protected $table = 'sms_history';

    protected $fillable = ['content', 'who_send', 'msg', 'code', 'sub_code', 'sub_msg', 'model', 'request_id', 'other_info'];

    public function getHistoryList($condition, $need, $page)
    {
        if (!isset($condition['code']) || $condition['code'] == 0)
            $this->where($condition);
        else
            $this->where('who_send', $condition['who_send'])
                ->where('code', '>', 0);

        return $this->select($need)->orderBy('created_at', 'desc')->paginate($page['per_page']);
    }
}
