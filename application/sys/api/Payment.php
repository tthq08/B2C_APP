<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 支付接口操作类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\api;


class Payment
{

    /**
     * 接口code
     * @var string
     */
    public $code;


    /**
     * 接口实例
     * @var \stdClass
     */
    protected $payment;


    /**
     * 操作类实例,传入code
     * Payment constructor.
     * @param $code
     */
    public function __construct($code)
    {
        $this->code = $code;
        return $this->payInstantiate();
    }


    /**
     * 获取支付接口
     * @param $code
     * @return \stdClass|bool
     */
    public static function getPaymentInterface($code)
    {
        if( is_file(EXTEND_PATH . "plugins/payment/{$code}/{$code}.class.php") ){
            include  EXTEND_PATH . "plugins/payment/{$code}/{$code}.class.php";
            $code = '\\'.$code;
            if(!class_exists($code)){
                return false;
            }
            $payment = new $code();
            return $payment;
        }else{
            return false;
        }

    }

}