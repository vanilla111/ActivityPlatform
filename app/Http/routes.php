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
//Route::options('/', function() {
//    header("Access-Control-Allow-Origin: *");
//    header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
//    header("Access-Control-Allow-Headers: content-type,authorization");
//    return response(null, 204);
//});
    Route::group(['middleware' => ['web', 'WXAuth'], 'prefix' => 'wx'], function () {
        Route::get('/index', function () {
            return view('wx_client/index');
        });
        Route::get('/choose', function () {
            return view('wx_client/choose');
        });
        Route::get('userInfo', 'WeiXin\EnrollController@getUserInfo');
        Route::post('enroll', 'WeiXin\EnrollController@enroll');
    });

    Route::group(['middleware' => ['client.change'], 'prefix' => 'api'], function () {
        //超级管理员
        Route::post('admin/smscharge', 'Admin\OrgController@chargeSms');
        Route::resource('admin/org', 'Admin\OrgController');
        Route::resource('admin/smstemp', 'Admin\SmsTempController');
        //组织管理员
        Route::resource('org', 'OrgController', ['except' => ['create', 'edit']]);
        //管理员
        Route::post('auth/login', 'AuthController@toLogin');
        Route::get('auth/checkremember', 'AuthController@checkRememberStatus');
        Route::get('auth/cancelremember', 'AuthController@cancelRememberToken');
        Route::resource('auth', 'AuthController', ['except' => ['index', 'create', 'destroy']]);
        //用户
        Route::group(['middleware' => 'web'], function () {
            Route::post('user/login', 'UserController@toLogin');
            Route::post('user/enroll', 'UserController@Enroll');
            Route::get('user/applydata', 'UserController@getUserApplyData');
            Route::resource('user', 'UserController', ['except' => ['index', 'create', 'destroy', 'edit']]);
        });

        Route::group(['middleware' => ['jwt.auth']], function () {
            //活动
            Route::put('act/{act_key}/start', 'ActController@startAct')->where('act_key', '[0-9]+');
            Route::put('act/{act_key}/end', 'ActController@endAct')->where('act_key', '[0-9]+');
            Route::resource('act', 'ActController', ['except' => ['create', 'edit']]);
            //流程
            Route::resource('flow', 'FlowController', ['except' => ['create', 'edit']]);
            //申请信息
            Route::post('applydata/sendsms', 'ApplyDataController@sendSMS');
            Route::post('applydata/excel', 'ApplyDataController@uploadExcelFile');
            Route::get('applydata/excel', 'ApplyDataController@getExcelFile');
            Route::post('applydata/operation', 'ApplyDataController@operation');
            Route::post('applydata/onekeyup', 'ApplyDataController@isSendSmsAndUpgrade');
            Route::resource('applydata', 'ApplyDataController', ['except' => ['create', 'edit']]);
            //短信
            Route::get('sms/history', 'SmsHistoryController@getHistory');
            Route::get('sms/templet', 'SmsController@getAdminSmsTemp');
            Route::post('sms/test', 'SmsController@sendTestSms');
            Route::resource('sms', 'SmsController', ['except' => ['create', 'edit']]);
        });
    });
