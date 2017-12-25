<?php
namespace api;
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 系统转移（中转站）模块：
//              负责系统各个模块api之间的共享
//              js，css，图片，视频等资源文件的真实路径调用
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

class Transfer
{
    //当前实例
    private static $transfer;
    //api列表
    protected $apiList = [];

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if( !(self::$transfer instanceof self) )
        {
            self::$transfer = new Transfer();
        }
        return self::$transfer;
    }

    /**
     * 用于禁止克隆对象
     */
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }

    /**
     * 实例化模块api
     * @param string $module
     * @param string $api
     * @param string $action
     * @param array|string $param
     * @return mixed
     */
    public function api($module,$api,$action,$param = '')
    {
        $api = ucfirst($api);
        $apiFile = APP_PATH . $module . DS .'api' . DS .$api .'.php';

        //验证api是否存在
        if( !file_exists($apiFile) ){
            return false;
        }
        $api_str = $this->ApiPSR_4path($module,$api);
        //分割$action
        $is_static = substr($action,0,2) == '::' ? true : false;
        if( $is_static == true ){
            //静态方法
            $action = substr($action,2,strlen($action));
            $apiC = $api_str;
        }else{
            //实例化
            if( empty($this->apiList[$api_str]) ){
                $this->apiList[$api_str] = new $api_str;
            }
            $apiC = $this->apiList[$api_str];
        }
        //验证方法是否存在
        if( !method_exists($apiC,$action) ){
            return false;
        }
        if(is_array($param)){
            return call_user_func_array(array($apiC,$action),$param);
        }else{
            return call_user_func(array($apiC,$action),$param);
        }
    }

    /**
     * 在列表中清除api实例
     * @param string $module
     * @param string $api
     */
    public function clearApi($module,$api)
    {
        $psr = $this->ApiPSR_4path($module,$api);
        unset($this->apiList[$psr]);
    }

    /**
     * 获取PSR-4路径
     * @param string $module
     * @param string $api
     * @return mixed
     */
    public function ApiPSR_4path($module,$api)
    {
        //实例化
        $api = ucfirst($api);
        $api_str = 'app\\'.$module.'\api\\'.$api;
        return $api_str;
    }

}