<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Http\Controllers', 'middleware' => 'client.change'], function ($api) {
        //超级管理员
        $api->resource('activity/admin/org', 'Admin/OrgController');
        //管理员
        $api->post('activity/auth/login', 'AuthController@toLogin');
        $api->get('activity/auth/checkremember', 'AuthController@checkRememberStatus');
        $api->get('activity/auth/cancelremember', 'AuthController@cancelRememberToken');
        $api->resource('activity/auth', 'AuthController', ['except' => ['index', 'create', 'destroy']]);
        //用户
        $api->group(['middleware' => 'web'], function ($api) {
            $api->post('activity/user/login', 'UserController@toLogin');
            $api->post('activity/user/enroll', 'UserController@Enroll');
            $api->get('activity/user/applydata', 'UserController@getUserApplyData');
            $api->resource('activity/user', 'UserController', ['except' => ['index', 'create', 'destroy', 'edit']]);
        });

        $api->group(['middleware' => 'jwt.auth'], function ($api) {
            //活动
            $api->put('activity/act/{act_key}/start', 'ActController@startAct')->where('act_key', '[0-9]+');
            $api->put('activity/act/{act_key}/end', 'ActController@endAct')->where('act_key', '[0-9]+');
            $api->resource('activity/act', 'ActController', ['except' => ['create', 'edit']]);
            //流程
            //$api->post('activity/flow/{flow_id}/removecorrelation', 'FlowController@removeCorrelation')->where('flow_id', '[0-9]+');
            $api->resource('activity/flow', 'FlowController', ['except' => ['create', 'edit']]);
            //申请信息
            $api->post('activity/applydata/sendsms', 'ApplyDataController@sendSMS');
            $api->post('activity/applydata/operation', 'ApplyDataController@operation');
            $api->resource('activity/applydata', 'ApplyDataController', ['except' => ['create', 'edit']]);
            //短信
            $api->get('activity/sms/templet', 'SmsController@getAdminSmsTemp');
            //$api->post('activity/sms/testsms', 'SmsController@sendTestSms');
            $api->resource('activity/sms', 'SmsController', ['except' => ['create', 'edit']]);
        });
    });
});
