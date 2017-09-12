<?php

namespace App\Http\Controllers;

use App\Models\SmsHistory;
use Illuminate\Http\Request;

use App\Http\Requests;

class SmsHistoryController extends Controller
{
    public function getHistory(Request $request)
    {
        $user = json_decode(get_detail_auth_info()->content(), true);
        $page['per_page'] = empty($request->get('per_page')) ? 50 : $request->get('per_page');
        $condition = [];
        if ($request->get('status') == 1)
            $condition['code'] = 0;
        else if (!empty($request->get('status')) && $request->get('status') == 0)
            $condition['code'] = 15;

        $condition['who_send'] = $user['user']['admin_id'];
        $need = ['request_id', 'content', 'msg', 'sub_code', 'sub_msg'];
        return response()->json([
            'status' => 1,
            'message' => 'success',
            'data' => (new SmsHistory())->getHistoryList($condition, $need, $page)
            ]);
    }
}
