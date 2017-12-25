<?php
namespace Translate\Api;
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/20
 * Time: 上午10:06
 */
class YouDao implements Init
{

    /**
     * 翻译接口配置
     * @var array
     */
    static $config = [
        'CURL_TIMEOUT' => 20,
        'URL' => 'http://openapi.youdao.com/api',
        'APP_KEY' => '1ef0e96248287764',
        'SEC_KEY' => '662lZ2OmG9DjaqDnD3MSTnayg34dyONH',
    ];


    /**
     * 执行翻译
     * @param array $args 参数
     * @return array
     */
    public static function execute($args)
    {
        // TODO: Implement execute() method.

        $queryArgs = array(
            'q' => $args['query'],
            'appKey' => self::$config['APP_KEY'],
            'salt' => rand(10000, 99999),
            'from' => $args['from'],
            'to' => $args['to'],

        );
        $queryArgs['sign'] = self::buildSign(self::$config['APP_KEY'], $args['query'], $queryArgs['salt'], self::$config['SEC_KEY']);
        $ret = self::call(self::$config['URL'], $queryArgs);
        $ret = json_decode($ret, true);
        return $ret;
    }


    /**
     * 加密
     * @param $appKey
     * @param $query
     * @param $salt
     * @param $secKey
     * @return string
     */
    function buildSign($appKey, $query, $salt, $secKey)
    {
        $str = $appKey . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }

    /**
     * 发起网络请求
     * @param $url
     * @param null $args
     * @param string $method
     * @param int $testflag
     * @param string $timeout
     * @param array $headers
     * @return bool|mixed
     */
    function call($url, $args = null, $method = "post", $testflag = 0, $timeout = '', $headers = array())
    {
        $timeout = $timeout?:self::$config['CURL_TIMEOUT'];
        $ret = false;
        $i = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }
        return $ret;
    }

    function callOnce($url, $args = null, $method = "post", $withCookie = false, $timeout = CURL_TIMEOUT, $headers = array())
    {
        $ch = curl_init();
        if ($method == "post") {
            $data = convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            $data = convert($args);
            if ($data) {
                if (stripos($url, "?") > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    function convert(&$args)
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . "&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

}