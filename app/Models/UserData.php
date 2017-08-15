<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    //
    protected $table = 'user_data';

    protected $primaryKey = 'user_id';

    protected $fillable = ['full_name', 'gender', 'stu_code', 'wx_openid', 'wx_nickname', 'wx_avatar',
        'college', 'contact', 'password', 'grade', 'have_org'];

    protected $hidden = ['password'];

    public function storeUserInfo($message)
    {
        return $this->create($message);
    }

    public function getUserInfo($condition, $need)
    {
        return $this->where($condition)->select($need)->first();
    }

    public function updateUserInfo($condition, $message)
    {
        return $this->where($condition)->update($message);
    }
}
