<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\App\Http\Middleware\VerifyCsrfToken::class,
        ],

        'api' => [
            'throttle:60,1',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'jwt.auth' => \Tymon\JWTAuth\Middleware\GetUserFromToken::class,
        'jwt.refresh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,
        'client.change' => \App\Http\Middleware\Auth::class,
        'auth.info' => \App\Http\Middleware\AuthInfo::class,
        'act.base' => \App\Http\Middleware\Act\Base::class,
        'act.variables' => \App\Http\Middleware\Act\Variables::class,
        'act.store' => \App\Http\Middleware\Act\Store::class,
        //'act.update' => \App\Http\Middleware\Act\Update::class,
        'flow.base' => \App\Http\Middleware\Flow\Base::class,
        'flow.store' => \App\Http\Middleware\Flow\Store::class,
        //'flow.update' => \App\Http\Middleware\Flow\Update::class,
        'flow.variables' => \App\Http\Middleware\Flow\Variables::class,
        'data.actkey' => \App\Http\Middleware\ApplyData\ActKey::class,
        'data.enrollid' => \App\Http\Middleware\ApplyData\EnrollId::class,
        'data.base' => \App\Http\Middleware\ApplyData\Base::class,
        'data.index' => \App\Http\Middleware\ApplyData\Index::class,
        'data.store' => \App\Http\Middleware\ApplyData\Store::class,
        'data.flowid' => \App\Http\Middleware\ApplyData\FlowId::class,
        'data.checkauth' => \App\Http\Middleware\ApplyData\CheckAuth::class,
        'data.variables' => \App\Http\Middleware\ApplyData\Variables::class,
        'data.operation' => \App\Http\Middleware\ApplyData\Operation::class,
        'data.sendSms' => \App\Http\Middleware\ApplyData\SendSms::class,
        'data.sendSmsVariables' => \App\Http\Middleware\ApplyData\SendSmsVariables::class,
        'user.base' => \App\Http\Middleware\User\Base::class,
        'user.variables' => \App\Http\Middleware\User\Variables::class,
        'user.enroll' => \App\Http\Middleware\User\Enroll::class,
        'sms.base' => \App\Http\Middleware\Sms\Base::class,
        'sms.index' => \App\Http\Middleware\Sms\Index::class,
        'sms.store' => \App\Http\Middleware\Sms\Store::class,
        'sms.variables' => \App\Http\Middleware\Sms\Variables::class,
        'sms.test' => \App\Http\Middleware\Sms\Test::class,
        'admin.base' => \App\Http\Middleware\Admin\Base::class,
        'child_account' => \App\Http\Middleware\Org\Base::class
    ];
}
