<?php

namespace app\mshop\model;

use think\Model;
/* 必须保证有网的情况下 */
/**
 * 根据地理坐标获取国家、省份、城市，及周边数据类(利用百度Geocoding API实现)
 * 百度密钥获取方法：http://lbsyun.baidu.com/apiconsole/key?application=key（需要先注册百度开发者账号）
 * Date:    2015-07-30
 * Author:  fdipzone
 * Ver: 1.0
 *
 * Func:
 * Public  getAddressComponent 根据地址获取国家、省份、城市及周边数据
 * Private toCurl              使用curl调用百度Geocoding API
 */
class Ip extends Model{
    // 百度Geocoding API
    const API = 'http://api.map.baidu.com/geocoder/v2/';
    // 不显示周边数据
    const NO_POIS = 0;
    // 显示周边数据
    const POIS = 1; 
    /**
     * 根据地址获取国家、省份、城市及周边数据
     * @param  String  $ak        百度ak(密钥)
     * @param  Decimal $longitude 经度
     * @param  Decimal $latitude  纬度
     * @param  Int     $pois      是否显示周边数据
     * @return Array
     */
    public static function IpgetAddressComponent($ip='',$pois=NULL){
   
    $pois=$pois==NULL?self::POIS:self::NO_POIS;
    @$loglat=self::ipgetloglat($ip);
    $log=$loglat['log'];
    $lat=$loglat['lat'];
        $param = array(
                //'ak' => 'hk7PBjEsPyYZm6wdtALRzE5e',
				'ak' => 'GLHqpOACGwuVpXEn6rO4Ie87XBtatVhy',
                'location' => implode(',', array($lat, $log)),
                'pois' => $pois,
                'output' => 'json'
        );
        // 请求百度api
        $response = self::toCurl(self::API, $param);
        $result = array();
        if($response){
            $result = json_decode($response, true);
        }
        return $result;
    }
    /*获取用户登录的真正ip而不是代理ip  */
    private static function getIPaddress(){
        $IPaddress='';
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $IPaddress = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
            $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $IPaddress = getenv("HTTP_CLIENT_IP");
            } else {
                $IPaddress = getenv("REMOTE_ADDR");
            }
        }
        return $IPaddress;
    }
    /* 如有没有ip参数则查询他访问本页面的ip 并得到他的经纬度和地址
     * 如果有ip参数则查询所给ip的经纬度及地址
     * */
    private static function ipgetloglat($getIp=NULL){
   
        $getIp=$getIp==NULL?self::getIPaddress():$getIp;
       
        if ($getIp=='127.0.0.1') {
            echo '测试成功';
            exit();
        }
       
        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=GLHqpOACGwuVpXEn6rO4Ie87XBtatVhy&ip={$getIp}&coor=bd09ll");
        $json = json_decode($content);
        //return $json;
        $data='';
        $data['ip']=$getIp;
        $data['log']=$json->{'content'}->{'point'}->{'x'};//按层级关系提取经度数据
        $data['lat']=$json->{'content'}->{'point'}->{'y'};//按层级关系提取纬度数据
        $data['address']=$json->{'content'}->{'address'};//按层级关系提取address数据
        return $data;
   
   
   
    }
    /**
     * 使用curl调用百度Geocoding API
     * @param  String $url    请求的地址
     * @param  Array  $param  请求的参数
     * @return JSON
     */
    private static function toCurl($url, $param=array()){
        $ch = curl_init();
        if(substr($url,0,5)=='https'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//设置返回数据是否自动显示
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        
        $response = curl_exec($ch);
        if($error=curl_error($ch)){
            return false;
        }
        curl_close($ch);
        return $response;
    }
}

