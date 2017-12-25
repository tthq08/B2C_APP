<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 用户充值管理
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\api;

use think\Db;
use think\Log;

class Recharge
{

    /**
     * 创建充值单号
     * @return string
     */
    public function createSn()
    {
        $char = 'cz';
        // 创建唯一码
        $char2 = date('ymdHis',time()).rand(1000,2000);
        return $char.$char2;
    }


    /**
     * 插入数据
     * @param array $data
     * @return mixed
     */
    public function insert($data)
    {
        try{
            $insert = Db::name('user_recharge')->insertGetId($data);
        }catch (\ErrorException $e)
        {
            return $e;
        }
        return (int) $insert;
    }


    /**
     * 充值回调
     * @param string $order_sn 充值订单号
     * @param string $pay_sn 支付号
     * @param array $data 回调数据
     * @return mixed
     */
    public function CZNotifyCallback($order_sn, $pay_sn, $data)
    {
        $order_sn = explode('_',$order_sn);
        $order_sn = $order_sn[0];
        // 查询充值订单是否充值完成
        $recharge = Db::name('user_recharge')->where('order_sn',$order_sn)->find();
        if( empty($recharge) ){
            Log::error('支付回调的充值订单数据不存在!time:'.time().'|callback:'.json_encode($data).'|order_sn:'.$order_sn);
            return false;
        }
        if( $recharge['pay_status'] == 1 ){
            return true;
        }
        // 处理回调数据
        Db::startTrans();
        try{
            $this->updateUserMoney($recharge['user_id'],$recharge['account']);
            $this->updateCZOrder($recharge['id'],$pay_sn,$data);
            $this->paymentStream($recharge,$data);
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }

    /**
     * 处理用户余额
     * @param int $user
     * @param float $money
     * @return mixed
     */
    public function updateUserMoney($user, $money)
    {
        // 增加用户余额
        $inc = Db::name('users')->where('id',$user)->setInc('user_money',$money);
    }


    /**
     * 修改订单数据
     * @param int $order_id 订单id
     * @param string $pay_sn 支付单号
     * @param array $data 回调数据
     * @return mixed
     */
    public function updateCZOrder($order_id, $pay_sn, $data)
    {
        $orderData = [
            'pay_status' => 1,
            'pay_time' => NOW_TIME,
            'pay_sn' => $pay_sn,
            'pay_json' => json_encode($data),
        ];
        // 修改
        $update = Db::name('user_recharge')->where('id',$order_id)->update($orderData);
    }

    /**
     * 添加交易记录
     * @param array $orderInfo 订单数据
     * @param array $data 回调数据
     * @return mixed
     */
    public function paymentStream($orderInfo, $data)
    {
        $streamData = [
            'user' => $orderInfo['user_id'],
            'type' => 3,
            't_id' => $orderInfo['id'],
            'pay_code' => $orderInfo['pay_code'],
            'pay_name' => $orderInfo['pay_name'],
            'pay_time' => NOW_TIME,
            'callback_time' => NOW_TIME,
            'json' => json_encode($data),
        ];
        // 插入
        $insert = Db::name('payment_stream')->insert($streamData);
    }

}