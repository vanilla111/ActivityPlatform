<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsNum extends Model
{
    protected $table = 'admin_sms_num';

    protected $fillable = ['admin_id', 'sms_num'];

    public $timestamps = false;

    public function admin()
    {
        return $this->belongsTo('App/Models/ActAdmin', 'admin_id', 'admin_id');
    }
}
