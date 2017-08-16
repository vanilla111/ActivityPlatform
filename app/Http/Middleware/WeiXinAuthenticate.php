<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WeiXinAuthenticate
{
    /**
     * 构成OAuth的基础URL
     * @var string
     */
    protected $baseOAUTH = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    /**
     * 获取AccessToken的基础URL
     * @var string
     */
    protected $baseACCESS = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 获取用户信息
     * @var string
     */
    protected $baseINFO = 'https://api.weixin.qq.com/sns/userinfo';

    /**
     * OAuth认证范围
     * @var string
     */
    protected $scope = 'snsapi_userinfo';

    /**
     * @var string
     */
    protected $state = '';

    /**
     * 跳转域名
     * @var string
     */
    protected $domain = 'http://hongyan.cqupt.edu.cn';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = [
            'openid'   => 'ouRCyjng0vYbA0di_cvqh74W__1g',
            'nickname' => 'wws',
            'avatar' => 'test'
        ];
        $request->session()->put('weixin.user', $user);
        $request->attributes->add(compact('user'));

        $config = $this->load();

        if (!$request->session()->has('weixin.user')) {
            // 拿到了可以换取accessToken的code
            if ($request->has('state') && $request->has('code')) {
                $code = $request->get('code');

                // 验证state参数是否改变
                abort_if($request->get('state') != $request->session()->get('weixin.state'), 500);

                // 发起POST请求获取accessToken
                $data = $this->send(
                    $this->baseACCESS,
                    [
                        'appid' => $config['appid'],
                        'secret' => $config['secret'],
                        'code' => $code,
                        'grant_type' => 'authorization_code'
                    ]
                );

                if (is_array($data)) {
                    $user = $this->send(
                        $this->baseINFO,
                        ['access_token' => $data['access_token'], 'openid' => $data['openid'], 'lang' => 'zh_CN'], true
                    );
                    $user['access_token'] = $data['access_token'];
                    $request->session()->set('weixin.user', $user);

                    return redirect()->to($this->url($request));
                }
            }

            // 生成随机的state字符串
            $this->state = sha1(uniqid(mt_rand(1, 1000000), true));

            $oauth2 = $this->baseOAUTH . '?' . $this->build_query([
                    'appid' => $config['appid'],
                    'redirect_uri' => $this->domain . '/activity/wx',
                    'response_type' => 'code',
                    'scope' => $this->scope,
                    'state' => $this->state
                ]);

            $request->session()->set('weixin.state', $this->state);

            return new RedirectResponse($oauth2 . '#wechat_redirect');

        }

        return $next($request);
    }

    /**
     * 载入公众号应用参数
     * @return array
     */
    private function load()
    {
        return ['appid' => env('WEIXIN_APPID'), 'secret' => env('WEIXIN_SECRET')];
    }

    /**
     * 通过curl与腾讯服务器进行交互
     * @param  string $url
     * @param  array  $data
     * @return array|null
     */
    private function send($url, array $data, $get = false)
    {
        $ch = curl_init();
        // 不需要返回header
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 返回JSON字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // POST格式发送
        if (!$get) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
        // 设置URL
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $this->build_query($data));

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    /**
     * 获取跳转链接
     * @param  Request $request
     * @return string
     */
    private function url($request)
    {
        $queries = array_except($request->query(), ['code', 'state']);

        return $this->domain . (empty($queries) ? '' : '?' . $this->build_query($queries));
    }

    private function build_query(array $data)
    {
        return http_build_query($data, '', '&', PHP_QUERY_RFC1738);
    }
}

