<?php
/**
 * 支付回调.
 * User: iconblog
 * Date: 2017/11/16
 * Time: 下午2:23
 */

namespace app\shop\api;

use think\Db;
use app\shop\api\Pieces;
use think\Log;

class PayReturn
{


    /**
     * 订单信息
     * @var array
     */
    private static $order;


    /**
     * 支付信息
     * @var array
     */
    private static $orderPay;


    /**
     * 支付流水号(站内)
     * @var string
     */
    private static $serial_sn;


    /**
     * 支付交易号(第三方支付)
     * @var string
     */
    private static $trade_no;



    /**
     * 支付成功修改支付状态
     * @param string $serial_sn 支付流水号
     * @param string $trade_no 支付宝交易号
     * @return mixed
     */
    public static function update_pay_status($serial_sn,$trade_no)
    {

        if( substr($serial_sn,0,2) == 'pt' ){
            $save = Pieces::pieces_order_success($serial_sn,$trade_no);
        }else{
            $save = self::orderSuccess($serial_sn,$trade_no);
        }

        if( $save === true ){
            return true;
        }else{
            return false;
        }
    }



    /**
     * 异步回调信息处理
     * @param string $serial_sn 支付流水号
     * @param string $trade_no 支付交易号
     * @return mixed
     */
    private static function orderSuccess($serial_sn,$trade_no){

        self::$serial_sn = $serial_sn;
        self::$trade_no = $trade_no;
        //检测当前支付流水号是否正确
        try{
            self::$orderPay = Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->find();
        }catch (\Exception $e){
            Log::error($e);
        }
        if( !empty($order_pay) ){
            //修改所有订单状态
            Db::startTrans();
            try{
                self::userPoints();
                self::userMoneyChange();
                self::giveCoupon();
                self::orderStatus();
                self::orderPayStatus();
                self::updatePromotion();
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                //记录进重要日志
                Log::error($exception);
                return false;
            }
            return true;
        }else{
            //记录进重要日志
            return false;
        }
    }


    /**
     * 修改用户积分
     * @return mixed
     */
    private static function userPoints(){

        $user = self::$orderPay['user_id'];
        $order = self::$orderPay['order'];
        //修改用户积分
        $orderArr = explode(',',$order);
        $points = 0;
        foreach ( $orderArr as $order ){
            // 查看当前订单的优惠方式是什么
            $goodsList = Db::name('shop_order_goods')->where('order_id',$order)->field('goods_id,prom_id')->select();
            foreach( $goodsList as $goodsA ){
                $goods = Db::name('shop_goods')->where('id',$goodsA['goods_id'])->field('give_score,title')->find();
                $points += $goods['give_score'];
                // 积分领取记录
                $points_receive['user_id'] = $user;
                $points_receive['num'] = $goods['give_score'];
                $points_receive['remark'] = '购买商品：'.$goods['title'].',赠送积分。';
                $points_receive['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
                Db::name('shop_points_receive')->insert();
                // 查看商品中是否存在促销ID
                if( $goodsA['prom_id'] > 0 ){
                    //获取促销信息
                    $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                    if( $prom['p_type'] == 3 || $prom['type'] == 4){
                        $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                        if($promInfo['discount_type'] == 3){
                            $points += $promInfo['expression'];
                            //积分领取记录
                            $points_receive['user_id'] = $user;
                            $points_receive['num'] = $promInfo['expression'];
                            $points_receive['remark'] = '参加优惠促销：'.$promInfo['title'].',赠送积分。';
                            $points_receive['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
                            Db::name('shop_points_receive')->insert();
                        }
                    }
                }
            }
            //查询订单信息
            $order_prom_id = Db::name('shop_order')->where('id',$order)->value('order_prom_id');
            if($order_prom_id == ''){
                break;
            }
            //获取订单优惠类型
            $order_prom_info = Db::name('shop_promotion_order')->where('prom_id > 0')->where('order_id',$order_prom_id)->field('title,type,expression')->find();
            if( $order_prom_info == 3 ){
                //赠送积分
                $points += $order_prom_info['expression'];
                //积分领取记录
                $points_receive['user_id'] = $user;
                $points_receive['num'] = $order_prom_info['expression'];
                $points_receive['remark'] = '订单优惠：'.$order_prom_info['title'].',赠送积分。';
                $points_receive['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
                Db::name('shop_points_receive')->insert();
            }
        }
        //修改用户积分
        Db::name('users')->where('id',$user)->setInc('pay_points',$points);
    }

    /**
     * 赠送用户代金券
     */
    private static function giveCoupon(){


        $orderArr = explode(',',self::$orderPay['order']);
        foreach ( $orderArr as $order ){
            //查看当前订单的优惠方式是什么
            /**
             * 1、打折
             * 2、金额
             * 3、积分
             * 4、优惠券
             * 5、免邮
             */
            //查询商品的赠送积分
            $goodsList = Db::name('shop_order_goods')->where('prom_id > 0')->where('order_id',$order)->field('goods_id,prom_id')->select();
            foreach( $goodsList as $goodsA ){

                //获取促销信息
                $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                if( $prom['p_type'] == 3 || $prom['type'] == 4){
                    $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                    if($promInfo['discount_type'] == 4){
                        //优惠券发放记录
                        api('shop','CouponSend','send_coupon',[self::$orderPay['user_id'],$promInfo['expression']]);
                    }
                }
            }
            //查询订单信息
            $order_prom_id = Db::name('shop_order')->where('id',$order)->value('order_prom_id');
            if($order_prom_id == ''){
                break;
            }
            //获取订单优惠类型
            $order_prom_info = Db::name('shop_promotion_order')->where('order_id',$order_prom_id)->field('title,type,expression')->find();
            if( $order_prom_info == 4 ){
                //赠送优惠券
                api('shop','CouponSend','send_coupon',[self::$orderPay['user_id'],$order_prom_info['expression']]);
            }
        }
    }


    /**
     * 修改订单状态
     */
    private static function orderStatus(){
        Db::name('shop_order')->where('id in('.self::$orderPay['order'].')')->update(['is_pay'=>1,'pay_time'=>date('Y-m-d H:i:s', NOW_TIME),'status'=>3,'pay_sn'=>self::$serial_sn]);
    }

    /**
     * 修改订单流水状态
     */
    private static function orderPayStatus(){
        Db::name('shop_order_pay')->where('serial_sn',self::$serial_sn)->update(['is_pay'=>1,'pay_time'=> NOW_TIME,'pay_sn'=>self::$trade_no]);
    }


    /**
     * 处理余额变动
     */
    private static function userMoneyChange(){
        //获取用户信息
        $serialInfo = Db::name('shop_order_pay')->where('serial_sn',self::$serial_sn)->field('user_id,payable_price,postage,change_mny,balance_price,order')->find();
        $order = explode(',',$serialInfo['order']);
        $order_num = count($order);
        $money = $serialInfo['payable_price']+$serialInfo['postage']-$serialInfo['change_mny']+$serialInfo['banlance'];
        //修改金额
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('total_amount',$money);

        //修改最近购买量
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('new_order',$order_num);
        //修改总购买量
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('total_order',$order_num);
    }



    /**
     * 修改促销状态
     * @return mixed
     */
    private static function updatePromotion()
    {
        // 获取当前参加的促销活动
        $orderArr = explode(',',self::$orderPay['order']);
        foreach ( $orderArr as $order ){
            //查看当前订单的优惠方式是什么
            $orderInfo = Db::name('shop_order')->where('id',$order)->find();
            if( !empty($orderInfo['order_prom_id']) ){
                Db::name('shop_promotion_order')->where('id',$orderInfo['order_prom_id'])->setInc('buy_num',1);
                Db::name('shop_promotion_order')->where('id',$orderInfo['order_prom_id'])->setInc('buy_money',$orderInfo['payable_price']-$orderInfo['change_mny']);
            }
            //查询商品的赠送积分
            $goodsList = Db::name('shop_order_goods')->where('prom_id > 0')->where('order_id',$order)->field('goods_id,prom_id')->select();
            foreach( $goodsList as $goodsA ){
                //获取促销信息
                $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->setInc('buy_num'.$goodsA['goods_num']);
            }
        }
    }



}