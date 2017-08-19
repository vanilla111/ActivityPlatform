<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    //
    protected $table = 'sms_temp_info';

    protected $primaryKey = 'temp_id';

    protected $fillable = ['author_id', 'temp_name', 'admin_temp_id', 'content', 'variables', 'status', 'type', 'was_test'];

    protected $hidden = ['status'];

    public function getSmsList($auth_id)
    {
        return $this->where(['author_id' => $auth_id])
            ->where('status', '>', 0)
            ->select('temp_id')
            ->get();
    }

    public function getSmsInfo($temp_id, $author_id, $need)
    {
        return $this->where('status', '>', 0)
            ->where(['temp_id' => $temp_id])
            ->select($need)
            ->first();
    }

    public function getSms($temp_id)
    {

    }

    public function storeSmsTemp($data)
    {
        return $this->create($data);
    }

    //获取的变量时的访问器
    public function getVariablesAttribute($value) {
        return unserialize($value);
    }
}
