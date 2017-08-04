<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    protected $table = 'sms_history';

    protected $fillable = ['content', 'who_send', 'msg', 'code', 'sub_code', 'sub_msg', 'model', 'request_id', 'other_info'];

    public function getContentAttribute($value)
    {
        return unserialize($value);
    }
}
