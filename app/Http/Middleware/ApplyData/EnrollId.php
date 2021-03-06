<?php

namespace App\Http\Middleware\ApplyData;
use App\Models\ActDesign;
use App\Models\ApplyData;
use Closure;
use JWTAuth;

class EnrollId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $enroll_id = $request->segment(3);

        $data_m = new ApplyData();
        $apply = $data_m->getApplyData(['enroll_id' => $enroll_id], '*');
        $apply_mes = json_decode($apply, true);
        if (empty($apply_mes))
            return response()->json(['status' => 0, 'message' => '查无此信息'], 400);

        $act_key = $apply_mes[0]['activity_key'];
        $flow_id = $apply_mes[0]['current_step'];
        $apply_info = $apply_mes;

        $auth_info = JWTAuth::decode(JWTAuth::getToken());
        $author_id = $auth_info['sub'];

        if (!$res = (new ActDesign())->getActInfo(['activity_id' => $act_key], ['author_id', 'activity_name', 'enroll_flow']))
            return response()->json(['status' => 0, 'message' => '非法访问'], 404);
        else {
            if ($res['author_id'] != $author_id)
                return response()->json(['status' => 0, 'message' => '非法请求'], 403);
        }

        $request->attributes->add(compact('flow_id'));
        $request->attributes->add(compact('act_key'));
        $request->attributes->add(compact('apply_info'));

        return $next($request);
    }
}
