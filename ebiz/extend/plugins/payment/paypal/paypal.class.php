<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： paypal支付插件
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

use think\Controller; 
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */

class paypal extends Controller
{    
    public $tableName = 'plugin'; // 插件表        
    public $paypal_config = array();// Paypal支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();

        $paymentPlugin = include "config.php";// 找到微信支付插件的配置
        // dump($paymentPlugin);
        $config_val = $paymentPlugin['config'];
        foreach ($config_val as $key => $config) {
            $config_value[$config['name']] = $config['value'];
        }
        $this->pay_config = $config_value;  //支付配置
        $this->pay_config['transport']     = 'http';//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {
        $pay_config = $this->paypal_config;
        // dump($_SERVER['HTTP_REFERER']);die;
        // dump($order);
        $returnUrl = $_SERVER['HTTP_REFERER'];
        $notify_url = "http://".$_SERVER['HTTP_HOST'].url('shop/Payment/notifyUrl',array('pay_code'=>'paypal'));
        $goods_name = tb_config('pay_cust_title',1);
        $goodsAmt = round($order['money']);
        $account = $this->pay_config['account'];
        $url = $this->pay_config['is_sandbox']==1?'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
        $html_text = <<<EOF
        <form id="paypalForm" style="display:none;" name="paypalForm" action="{$url}" method="post">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="{$account}">
            <input type="hidden" name="item_name" value="{$goods_name}">
            <input type="hidden" name="amount" value="{$goodsAmt}">
            <input type="hidden" name="charset" value="UTF-8">
            <input type="hidden" name="invoice" value="{$order['serial_sn']}">
            <input value='{$notify_url}' type='hidden' name='cancel_return '>
            <input value="{$notify_url}" type='hidden' name='notify_url'>
            <input value="{$returnUrl}" type='hidden' name='return'>
            <input type="hidden" name="no_shipping" value="1">
            <input type="hidden" name="no_note" value="0">

            <button type="submit" >submit</button>
        </form>
        <script>document.forms['paypalForm'].submit();</script>
EOF;
            
            return $html_text;
    }
    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {                
        $data = request()->param();
        $res = $this->verifyData($data);
        if($res) //验证成功
        {
            $order_sn = $data['invoice']; //商户订单号                    
            $trade_no = $data['txn_id']; //Paypal交易号                   
            $trade_status = $data['payment_status']; //交易状态
            
            // Paypal解释: 交易成功且结束，即不可再做任何操作。
            if( $this->pay_config['is_sandbox'] == 1 ){
                if($trade_status == 'Pending')
                {
                    update_pay_status($order_sn,$trade_no); // 修改订单支付状态
                }
            }else{
                if($trade_status == 'Completed')
                {
                    update_pay_status($order_sn,$trade_no); // 修改订单支付状态
                }
            }
            
            echo "200 OK"; // 告诉Paypal处理成功
        }
        else 
        {                
            echo "fail"; //验证失败                                
        }
    }

    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function respond2()
    {                
        $data = input('');
        
        $res = $this->verifyData($data);
        if($res) //验证成功
        {
            $order_sn = $data['invoice']; //商户订单号                    
            $trade_no = $data['txn_id']; //Paypal交易号                   
            $trade_status = $data['payment_status']; //交易状态
            
            // Paypal解释: 交易成功且结束，即不可再做任何操作。
            if( $this->pay_config['is_sandbox'] == 1 ){
                if($trade_status == 'Pending')
                {
                    update_pay_status($order_sn,$trade_no); // 修改订单支付状态
                    return array('status'=>1,'order_sn'=>$order_sn);//跳转至成功页面
                }else{
                    return array('status'=>0,'order_sn'=>$order_sn); //跳转至失败页面
                }
            }else{
                if($trade_status == 'Completed')
                {
                    update_pay_status($order_sn,$trade_no); // 修改订单支付状态
                    return array('status'=>1,'order_sn'=>$order_sn);//跳转至成功页面
                }else{
                    return array('status'=>0,'order_sn'=>$order_sn); //跳转至失败页面
                }
            }


        }
        else 
        {                
            return array('status'=>0,'order_sn'=>$data['invoice']); //跳转至失败页面                             
        }
    }
    
    public function verifyData($data)
    {
        $req = 'cmd=_notify-validate';
        if(function_exists('get_magic_quotes_gpc')) $get_magic_quotes_exists = true;
        foreach ($data as $key => $value) {        
            if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1){
                $value = urlencode(stripslashes($value)); 
            }else{
                $value = urlencode($value);
            }
            $req.= "&$key=$value";
        }
        $url = $this->pay_config['is_sandbox']==1?'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        $res = curl_exec($ch);
        if (strcmp ($res, "VERIFIED") == 0) {
            return true;
        } else if (strcmp ($res, "INVALID") == 0) {
            return false;
        }
          
        // assign posted variables to local variables  
        // $item_name = $data['item_name'];  
        // $item_number = $data['item_number'];  
        // $payment_status = $data['payment_status'];  
        // $payment_amount = $data['mc_gross'];  
        // $payment_currency = $data['mc_currency'];  
        // $txn_id = $data['txn_id'];  
        // $receiver_email = $data['receiver_email'];  
        // $payer_email = $data['payer_email'];  
        // $mc_gross = $data['mc_gross']; // 付款金额  
        // $custom = $data['custom']; // 得到订单号  
          
        // if (!$fp) {  
        //     return false;
        // } else {  
        //     fputs ($fp, $header . $req);  
        //         while (!feof($fp)) {  
        //             $res = fgets ($fp, 1024);  
        //             db('test') ->insert(['error'=>json_encode($res)]);
        //             if (strcmp ($res, "VERIFIED") == 0) {
        //                 $pay_config = $this->paypal_config;
        //                 if ($data['payment_status'] !== 'Completed') {
        //                     return false;
        //                 }

        //                 if ($data['payer_status'] !== 'verified') {
        //                     return false;
        //                 }

        //                 if ($data['business'] != $pay_config['account']) {
        //                     return false;
        //                 }

        //                 return true;
        //                 // check the payment_status is Completed  
        //                 // check that txn_id has not been previously processed  
        //                 // check that receiver_email is your Primary PayPal email  
        //                 // check that payment_amount/payment_currency are correct  
        //                 // process payment  
        //                 // 验证通过。付款成功了，在这里进行逻辑处理（修改订单状态，邮件提醒，自动发货等）  
        //             }  
        //         else if (strcmp ($res, "INVALID") == 0) {
        //             return false;
        //             // log for manual investigation  
        //             // 验证失败，可以不处理。  
        //         }  
        //     }  
        //     fclose ($fp);  
        // }
    }

}