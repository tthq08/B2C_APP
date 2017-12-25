<?php
namespace plugins\payment\wapalipay;
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/26
 * Time: 下午6:08
 */
class WapPay
{

    protected $config ;
    public function __construct()
    {
        // 加载支付核心文件
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'core/wappay/service/AlipayTradeService.php';
        require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'core/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';
        $this->config = include(dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'core/config.php');

    }

    /**
     * 支付接口
     * @param string $out_trade_no 商户订单号,商户网站订单系统中唯一订单号，必填
     * @param string $order_name 订单名称
     * @param int $money 付款金额
     * @param string $goods_desc 商品描述
     * @return mixed
     */
    public function pay($out_trade_no,$order_name,$money,$goods_desc = '')
    {

        // 订单名称，必填
        $subject = $order_name;
        // 付款金额，必填
        $total_amount = $money;
        // 商品描述，可空
        $body = $goods_desc;
        //超时时间
        $timeout_express="1m";

        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $payResponse = new \AlipayTradeService($this->config);

        $result = $payResponse->wapPay($payRequestBuilder,$this->config['return_url'],$this->config['notify_url']);

    }


    /**
     * 支付同步回调
     * @return mixed
     */
    public function returnUrl()
    {

    }


    /**
     * 支付异步回调
     * @return mixed
     */
    public function notifyUrl()
    {

    }


    /**
     * 退款接口
     * @return mixed
     */
    public function refund()
    {

    }


    /**
     * 退款回调接口
     * @return mixed
     */

}