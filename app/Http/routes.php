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

Route::get('/test', 'BaseController@Test');

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

//$api = app('Dingo\Api\Routing\Router');

//$api->version('v1', function ($api) {
    Route::group(['middleware' => 'client.change'], function () {
        //超级管理员
        Route::resource('activity/admin/org', 'Admin\OrgController');
        //管理员
        Route::post('activity/auth/login', 'AuthController@toLogin');
        Route::get('activity/auth/checkremember', 'AuthController@checkRememberStatus');
        Route::get('activity/auth/cancelremember', 'AuthController@cancelRememberToken');
        Route::resource('activity/auth', 'AuthController', ['except' => ['index', 'create', 'destroy']]);
        //用户
        Route::group(['middleware' => 'web'], function () {
            Route::post('activity/user/login', 'UserController@toLogin');
            Route::post('activity/user/enroll', 'UserController@Enroll');
            Route::get('activity/user/applydata', 'UserController@getUserApplyData');
            Route::resource('activity/user', 'UserController', ['except' => ['index', 'create', 'destroy', 'edit']]);
        });

        Route::group(['middleware' => 'jwt.auth'], function () {
            //活动
            Route::put('activity/act/{act_key}/start', 'ActController@startAct')->where('act_key', '[0-9]+');
            Route::put('activity/act/{act_key}/end', 'ActController@endAct')->where('act_key', '[0-9]+');
            Route::resource('activity/act', 'ActController', ['except' => ['create', 'edit']]);
            //流程
            Route::resource('activity/flow', 'FlowController', ['except' => ['create', 'edit']]);
            //申请信息
            Route::post('activity/applydata/sendsms', 'ApplyDataController@sendSMS');
            Route::post('activity/applydata/operation', 'ApplyDataController@operation');
            Route::resource('activity/applydata', 'ApplyDataController', ['except' => ['create', 'edit']]);
            //短信
            Route::get('activity/sms/templet', 'SmsController@getAdminSmsTemp');
            //$api->post('activity/sms/testsms', 'SmsController@sendTestSms');
            Route::resource('activity/sms', 'SmsController', ['except' => ['create', 'edit']]);
        });
    });
//});
