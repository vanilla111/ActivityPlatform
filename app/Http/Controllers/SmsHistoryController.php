<?php

namespace App\Http\Controllers;

use App\Models\SmsHistory;
use Illuminate\Http\Request;

use App\Http\Requests;
use JWTAuth;

class SmsHistoryController extends Controller
{
    public function getHistory(Request $request)
    {
        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];
        $page['per_page'] = empty($request->get('per_page')) ? 50 : $request->get('per_page');
        $condition = [];
        if ($request->get('status') == 1)
            $condition['code'] = 0;
        else if (!empty($request->get('status')) && $request->get('status') == 0)
            $condition['code'] = 15;

        $condition['who_send'] = $author_id;
        //return $condition;
        $need = ['who_send', 'request_id', 'content', 'msg', 'sub_code', 'sub_msg', 'act_key', 'flow_id', 'name', 'stu_code', 'contact'];
        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => (new SmsHistory())->getHistoryList($condition, $need, $page)
            ]);
    }
}
