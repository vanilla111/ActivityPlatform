<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2016/10/13
 * Time: 23:33
 */

if (! function_exists('test')) {
    function test()
    {
        return 'success';
    }
}

if (! function_exists('verify')) {
    /**
     * 验证用户学号和身份证后六位
     *
     * @param string $code
     * @param string $pass
     *
     * @return mixed
     */
    function verify($code, $pass)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://hongyan.cqupt.edu.cn/api/verify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query(['stuNum' => $code, 'idNum' => $pass]));

        $output = curl_exec($ch);
        curl_close ( $ch );

        return json_decode($output, true);
    }
}

if (! function_exists('unique_array')) {
    function unique_array($arr)
    {
        return array_flip(array_flip($arr));
    }
}

if (! function_exists('is_date')) {
    function is_date($date)
    {
        if($date == date('Y-m-d H:i:s', strtotime($date)))
            return true;
        else
            return false;
    }
}

if (! function_exists('check_phoneNum')) {
    function check_phoneNum($phoneNum)
    {
        if (!is_numeric($phoneNum)
            || !preg_match('/1[0-9]{10}/', $phoneNum)
            || strlen($phoneNum) > 11)
            return false;
        else
            return true;
    }
}

//检查是否有未被定义的参数
if (! function_exists('hasNotDefine')) {
    function hasNotDefine($info, $define)
    {
        foreach ($info as $k1 => $v) {
            $flag = 0;
            foreach ($define as $k2) {
                if ($k1 == $k2)
                    $flag = 1;
            }
            if ($flag != 1)
                return true;
        }
        return false;
    }
}

//取出键值不需要的参数，并用‘,’拼接返回
if (! function_exists('unset_useless')) {
    function unset_useless($arr = [], $useless = [] )
    {
        $pos = [];
        foreach ($arr as $k1 => $v1) {
            foreach ($useless as $k2 => $v2) {
                if ($v1 == $v2) {
                    unset($arr[$k1]);
                }
            }
        }

        foreach ($useless as $k => $v) {
            $i = array_search($v, $arr); //找到下标
            array_splice($arr, $i, 1); //删除元素并重新建立索引
        }

        $string = [$arr[0]];
        foreach ($arr as $key => $value) {
            $string = $string . ',' . $value;
        }

        return $string;
    }
}

//删除键相同的数组元素
if (! function_exists('unset_idle')) {
    function unset_idle($arr = [], $useless = [])
    {
        foreach ($arr as $k1 => $v1) {
            foreach ($useless as $k2 => $v2) {
                if ($k1 == $v2) {
                    unset($arr[$k1]);
                }
            }
        }
        return $arr;
    }
}

if (! function_exists('unset_empty')) {
    function unset_empty($arr = [])
    {
        foreach ($arr as $key => $value) {
            if (empty($arr[$key])) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }
}

if (! function_exists('getConditionString')) {
    function getConditionString($condition) {
        $string = '筛选条件: ';
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'act_key':{
                    $string .= ' 活动ID:' . $value . ';';
                }break;
                case 'stu_code':{
                    $string .= ' 学号检索:' . $value . ';';
                }break;
                case 'name':{
                    $string .= ' 姓名检索:' . $value . ';';
                }break;
                case 'college':{
                    $string .= ' 学院检索:' . $value . ';';
                }break;
                case 'gender':{
                    $string .= ' 性别检索:' . $value . ';';
                }break;
            }
        }

        return $string;
    }
}

if (! function_exists('is_all_letter')) {
    function is_all_letter($str)
    {
        if (!preg_match("/[\x7f-\xff]/", $str))
            return true;

        return false;
    }
}

if (! function_exists('is_json')) {
    function is_json($str)
    {
        return !is_null(json_decode($str));
    }
}

if (! function_exists('get_detail_auth_info')) {
    function get_detail_auth_info() {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }
}

if (! function_exists('utf8_strlen')) {
    function utf8_strlen($str = null)
    {
        // 将字符串分解为单元
        preg_match_all("/./us", $str, $match);
        // 返回单元个数
        return count($match[0]);
    }
}

if (! function_exists('uft8_to_unicode')) {
    function utf8_str_to_unicode($utf8_str)
    {
        $utf8_str = iconv('UTF-8', 'UCS-2', $utf8_str);
        $len = strlen($utf8_str);
        $str = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2) {
            $c = $utf8_str[$i];
            $c2 = $utf8_str[$i + 1];
            if (ord($c) > 0) {    // 两个字节的文字
                $str .= 'u'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
            } else {
                $str .= $c2;
            }
        }
        return $str;
    }
}