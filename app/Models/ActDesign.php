<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActDesign extends Model
{
    //
    protected $table = 'activity_design';

    protected $primaryKey = 'activity_id';

    protected $fillable = ['author_id', 'num_limit', 'activity_name', 'summary', 'time_description',
        'location', 'enroll_flow','status', 'start_time', 'end_time'];

    public function getActInfo($condition = [], $need )
    {
        return $this->where($condition)->select($need)->first();
    }

    public function getActInfoList($list, $need )
    {
        return $this->whereIn('activity_id', $list)->select($need)->get();
    }

    public function getActInfoWithoutStatus($condition = [], $need)
    {
        return $this->where($condition)->select($need)->first();
    }

    public function getActList($condition = [], $need = [])
    {
        $sql = $this->sqlBuilder($condition);
        return $sql->select($need)->paginate($condition['per_page']);
    }

    public function storeAct($message = [])
    {
        return $this->create($message);
    }

    public function updateActInfo($condition = [], $message = [])
    {
        return $this->where($condition)->update($message);
    }

    public function sqlBuilder($condition)
    {
//        $condition = [
//          'eq' => [
//              'author_id' => 'test',
//              'activity_key' => 'test'
//            ],
//          'sort' => [
//              'sortby' => 'test',
//              'sort' => 'desc'
//            ],
//            'vague' => [
//                'activity_name' => 'test'
//            ],
//            'per_page' => 'test',
//          ];
        //状态限制
        $res = $this->where('status', '>=', '0');
        //等价条件查询
        $res = $res->where($condition['eq']);
        //模糊查询
        if (!empty($condition['vague']))
            foreach ($condition['vague'] as $key => $value) {
                $res = $res->where($key, 'like', '%'.$value.'%');
            }
        //排序条件
        $res = $res->orderBy($condition['sort']['sortby'],
            $condition['sort']['sort']);

        return $res;
    }

    public function hasManyFlow()
    {
        return parent::hasMany('App\Models\FlowInfo', 'activity_key', 'activity_id');
    }
}
