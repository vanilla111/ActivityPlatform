<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSmsTemp extends Model
{
    protected $table = 'admin_sms_temp';

    protected $primaryKey = 'admin_temp_id';

    protected $fillable = ['sms_temp', 'admin_id', 'temp_name', 'type', 'sms_free_sign_name', 'sms_id', 'sms_variables',
        'dynamic_variables', 'content', 'sms_provider', 'sms_type'];

    public function getSmsList($condition, $need)
    {
        $sms_provider = SmsProvider::where(['status' => 1])->first();
        if (empty($sms_provider))
            return false;
        else
            $condition['sms_provider'] = $sms_provider['id'];

        return $this->where('status', '>', 0)
            ->where($condition)
            ->select($need)
            ->orderBy("created_at", 'desc')
            ->get();
    }

    public function getSmsTemp($condition, $need)
    {
        return $this->where('status', '>', 0)
            ->where($condition)
            ->select($need)
            ->first();
    }

    public function getDynamicVariablesAttribute($value)
    {
        return unserialize($value);
    }
}
