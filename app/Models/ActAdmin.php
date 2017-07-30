<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ActAdmin extends Authenticatable
{
    //
    protected  $table = 'activity_admin';

    protected  $primaryKey = 'admin_id';

    protected  $fillable = ['admin_name', 'password', 'id', 'author_code', 'author_phone', 'account'];

    protected  $hidden = ['password'];

    public function getAuthInfo($condition, $need)
    {
        return $this->where($condition)->select($need)->first();
    }

    public function storeAuthInfo($condition, $message)
    {
        return $this->where($condition)->update($message);
    }

    public function updateAuthInfo($condition, $message)
    {
        return $this->where($condition)->update($message);
    }
}
