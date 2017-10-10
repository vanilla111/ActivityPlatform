<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApplyData extends Model
{
    //
    protected $table = 'apply_data';

    protected $primaryKey = 'enroll_id';

    public $fillable = ['user_id', 'activity_key', 'current_step', 'full_name', 'stu_code', 'act_name',
        'gender', 'grade', 'contact', 'college', 'score', 'evaluation', 'act_author'];

    public function belongsToAct()
    {
        return $this->belongsTo('App\Models\Activity_design', 'activity_key', 'activity_key');
    }

    public function getApplyData($condition = [], $need)
    {
        return $this->where('status', '>=', '0')->where($condition)->select($need)->get();
    }

    public function hasApplyData($condition = [])
    {
        return $this->where('status', '>=', '0')->where($condition)->first();
    }

    public function getApplyDataList($info, $need)
    {
        $condition = $this->conditionBuilder($info);
        $sql = $this->sqlBuilder($condition);
        return $sql->select($need)->paginate($info['per_page']);
    }

    public function getApplyDataForExcel($info, $need)
    {
        $condition = $this->conditionBuilder($info);
        $sql = $this->sqlBuilder($condition);
        return $sql->select($need)->get();
    }

    public function getApplyDataToSendSms($condition, $need)
    {
        return $this->whereIn('enroll_id', $condition['enroll_id'])
            ->whereIn('current_step', $condition['flow_id'])
            ->where('status', 1)
            ->where('was_send_sms', 0)
            ->distinct('contact')
            ->select($need)
            ->get();
    }

    /**
     * 一次性更新多个数据
     * @param array $update_data
     * @return bool
     */
    public function updateBatch(array $update_data)
    {
//        $update_data = [
//          [
//              'enroll_id' => 1,
//              'activity_key' => 1000,
//              'current_step' => 2,
//              'score' => 89
//          ],
//            [
//                'enroll_id' => 2,
//                'activity_key' => 1000,
//                'current_step' => 2,
//                'score' => 99
//            ]
//        ];
        if(empty($update_data))
            return false;

        $updateColumn = array_keys($update_data[0]);

        //去除不能更新的部分
        $referenceColumn = [];
        foreach ($updateColumn as $k => $v) {
            if ($v == 'enroll_id' || $v == 'activity_key' || $v == 'current_step') {
                unset($updateColumn[$k]);
                array_push($referenceColumn, $v);
            }
        }

        $q = "UPDATE" . " " . $this->table . " SET ";

        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn . " = CASE WHEN ";
            foreach ($update_data as $data) {
                foreach ($referenceColumn as $k => $v) {
                    $q .= $v . "=" . $data[$v] . " AND ";
                }
                $q = substr($q, 0, strlen($q) - 4);
                $q .= "THEN " . $data[$uColumn] . " WHEN ";
            }
            $q = substr($q, 0, strlen($q) - 5);
            $q .= " ELSE " . $uColumn . " END, ";
        }
        $q = rtrim($q, ", ");

        return DB::update($q);

    }

    public function storeListData(array $insert_data)
    {
        return $this->insert($insert_data);
    }

    public function applyDataExists($condition = [])
    {
        return $this->where($condition)->where('status', '>=', 0)->exists();
    }

    public function storeData($message)
    {
        return $this->create($message);
    }

    public function updateData($condition, $message)
    {
        return $this->where($condition)->update($message);
    }

    public function conditionBuilder($info)
    {
        if (isset($info['current_step'])) {
            if (is_array($info['current_step']))
                $condition['in'] = $info['current_step'];
            else
                $condition['eq']['current_step'] = $info['current_step'];
        }
        if (isset($info['gender']))
            $condition['eq']['gender'] = $info['gender'];
        if (isset($info['act_key']))
            $condition['eq']['activity_key'] = $info['act_key'];
        if (isset($info['was_send_sms']))
            $condition['eq']['was_send_sms'] = $info['was_send_sms'];
        if (isset($info['user_id']))
            $condition['eq']['user_id'] = $info['user_id'];
        if (isset($info['name']))
            $condition['vague']['full_name'] = $info['name'];
        if (isset($info['stu_code']))
            $condition['vague']['stu_code'] = $info['stu_code'];
        if (isset($info['college']))
            $condition['vague']['college'] = $info['college'];

        $condition['sort']['sort'] = $info['sort'];
        $condition['sort']['sortby'] = $info['sortby'];

        return $condition;
    }

    public function sqlBuilder($condition)
    {
//        $condition = [
//          'eq' => [
//              'author_id' => 'test',
//              'activity_key' => 'test',
//              'activity_name' => 'test'
//            ],
//          'sort' => [
//              'sortby' => 'test',
//              'sort' => 'desc'
//            ]
//            'per_page' => 'test',
//          ];
        //状态限制
        $res = $this->where('status', '>=', '0');
        //等价条件查询
        $res = $res->where($condition['eq']);
        //数组查询
        if (isset($condition['in']))
            $res = $res->whereIn('current_step', $condition['in']);
        //模糊查询
        if (isset($condition['vague']))
            foreach ($condition['vague'] as $key => $value) {
                $res = $res->where($key, 'like', '%'.$value.'%');
            }
        //排序条件
        $res = $res->orderBy($condition['sort']['sortby'],
            $condition['sort']['sort']);

        return $res;
    }

//    //获取的分数的访问器
//    public function getScoreAttribute($value) {
//        return unserialize($value);
//    }
//
//    //获取的分数的访问器
//    public function getEvaluationAttribute($value) {
//        return unserialize($value);
//    }
}
