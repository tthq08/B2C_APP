<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 结算处理类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\api;

use think\Controller;
use think\Db;

class Compute
{


    /**
     * 计算商品促销之后的价格
     * @param int $goods 商品ID
     * @param int $spec 商品规格ID
     * @return $promPrice 促销价格
     * 商品价格计算流程
     *
     *      1、当前时间是否小于活动结束时间（小于则跳过活动价格计算）
     *      2、当前价格是否小于活动满足的商品价格（小于则跳过活动价格计算）
     *      3、查看当前优惠类型;
     *      4、根据优惠类型计算出最新价格（如果是代金券或者免邮机会将在用户确认收货后发放）
     *
     *      得出最新价格；
     */
    function promGoodsPrice($goods,$spec='',$getProm = false,$goodsNum=1)
    {
        intval($goods);
        $newPrice = getTableValue('shop_goods','id='.$goods,'shop_price');//最新价格
        // dump($newPrice);die;
        //查看当前商品参加的促销
        $field = 'prom_id,p_type,p_id,start_time,end_time';//后面需要用到的字段
        $prom = Db::name('shop_promotion')->where('`start_time` <= "'.date('Y-m-d H:i:s',time()).'"')->where('`end_time` > "'.date('Y-m-d H:i:s',time()).'"')->where('FIND_IN_SET('.$goods.',goods)')->where('status','1')->field($field)->find();

        if( $getProm == true ){
            if( empty($prom) ){
                return false;
            }
            return $prom;
        }
        if( empty($prom) ){
            //获取规格金额
            if( empty($spec) ){
                return $newPrice;
            }
            $spec_price = getTableValue('shop_spec_price',['goods_id'=>$goods,'key_sign'=>$spec],'price');
            return $spec_price;
        }elseif ($prom['end_time'] < date('Y-m-d H:i:s',time())){
            return $newPrice;
        }
        //根据促销类型已经促销ID查询出详细信息
        switch ($prom['p_type']){
            case '1':
                $promInfo = getPanicInfo($prom['p_id']);//抢购促销
                break;
            case '2':
                $promInfo = getGroupInfo($prom['p_id']);//团购促销
                break;
            case  '3':
                $promInfo = getDiscountInfo($prom['p_id'],$goods,$goodsNum);//优惠促销
                break;
        }
        if( $getProm == true ){
            if( empty($promInfo['status']) ){
                return false;
            }
            return $prom;
        }
        if( empty($promInfo) ){
            return $newPrice;
        }
        //根据促销类型对活动商品进行计算，类型为团购和抢购时则直接调取固定金额，为优惠促销时则根据优惠类型计算优惠的价格
        //dump($promInfo);die;
        $p_type = empty($prom['p_type']) ? '' : $prom['p_type'];
        if( $p_type == 1 || $p_type == 2 ){

            if( $promInfo['goods_spec'] == '' || $spec == '' ){
                $newPrice = $promInfo['price'];
            }else{
                // dump($promInfo);
                // dump($spec);
                $spec_price = empty($promInfo['goods_spec'][$spec]) ? getTableValue('shop_spec_price',['goods_id'=>$goods,'key_sign'=>$spec],'price') : $promInfo['goods_spec'][$spec];
                $newPrice = $spec_price;
            }
        }elseif( $p_type == 3 ){
            $discount_type = $promInfo['discount_type'];//根据优惠类型及优惠体现，算出优惠的价格
            /*优惠类型：
             *      1、打折
             *      2、金额
             *      3、代金券
             *      4、积分
             *      5、免邮机会
             *  计算价格时只取1和2
             */
            if( $discount_type == 1 ){
                $discountPrice = $newPrice*$promInfo['expression']/100;
            }elseif( $discount_type == 2 ){
                $discountPrice = $newPrice-$promInfo['expression'];
            }else{
                $discountPrice = $newPrice;
            }
            $goodsPrice = getTableValue('shop_goods','id='.$goods,'shop_price') * $goodsNum;
            if( $promInfo['money'] <= $goodsPrice ){
                return $discountPrice;
            }
        }
        // dump($newPrice);
        return $newPrice * $goodsNum;
    }

    /**
     * 计算优惠券的金额
     * @param decimal $price 当前价格
     * @param int $coupon 优惠券ID
     * @return decimal
     */
    function couponPrice($price,$coupon)
    {
        //获取该优惠券的信息
        $couponInfo = Db::name('shop_coupon')->where('status',1)->where('id',$coupon)->find();

        //查看该价格是否满足优惠券使用价格
        if( $couponInfo == '' || $price < $couponInfo['money'] ){
            return 0;
        }
        //根据优惠类型，决定扣除的金额
        if ( $couponInfo['type'] == 1 ){//打折
            $newPrice = $price*$couponInfo['amount']/100;
        }elseif ($couponInfo['type'] == 2){
            $newPrice = $price-$couponInfo['amount'];
        }
        $couponPrice = $price - $newPrice;
        return $couponPrice;
    }

    /**
     * 计算订单促销后的金额
     * @param decimal $price 传入的金额
     * @param boolean $getid 是否获取ID
     * @return decimal|int
     */
    function orderPrice($price,$getid = false)
    {
        $nowDate = date('Y-m-d H:i:s', NOW_TIME);
        //取出当前金额支持的订单促销活动
        $orderInfo = Db::name('shop_promotion_order')->where('start_time < "'.$nowDate.'"')->where('end_time > "'.$nowDate.'"')->where('status',1)->where('money <= '.$price)->order('money desc')->field('order_id,type,expression')->find();
        if( $orderInfo == '' ){
            return 0;
        }
        if( $getid == true ){
            return $orderInfo['order_id'];
        }
        //根据优惠类型，决定扣除的金额
        if ( $orderInfo['type'] == 1 ){//打折
            $newPrice = $price * $orderInfo['expression']/100;
        }elseif ($orderInfo['type'] == 2){
            $newPrice = $price - $orderInfo['expression'];
        }
        $orderPrice = $price - $newPrice;
        return $orderPrice;
    }

    /**
     * 计算邮费
     * @param int $goods
     * @return decimal
     */
    function postage($goods){
        return 0;
    }


}