<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 优惠券查询模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\mshop\api;

use think\Db;

class Coupon
{
    protected $_DB;

    public function __construct(){
        //定义，连接数据表
        $this->_DB = Db::name('shop_coupon');
    }

    /**
     * 优惠券发放
     * @param int $user_id 用户ID
     * @param int $coupon_id 优惠券ID
     */
    public function send_coupon($user_id,$coupon_id){
        intval($user_id);
        //获取优惠券详细信息
        $couponInfo = Db::name('shop_coupon')->where('id',$coupon_id)->field('start_time,end_time')->find();
        $sendData['user'] = $user_id;
        $sendData['coupon'] = $coupon_id;
        $sendData['is_use'] = 0;
        $sendData['start_time'] = $couponInfo['start_time'];
        $sendData['end_time'] = $couponInfo['end_time'];
        $insert = Db::name('user_coupon')->insert($sendData);
        if( $insert === false ){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 查询用户所有可使用优惠券
     * @param int $user_id  用户ID
     * @param int $is_use   是否查询可以使用的ID
     * @return array
     */
    public function userCoupon($user_id,$is_use = true){
        intval($user_id);
        if($user_id){
            if( $is_use == true ){
                $nowDate = date('Y-m-d H:i:s',time());
                $where = 'uc.`status` = 1 and uc.`start_time` < "'.$nowDate.'" and uc.`end_time` > "'.$nowDate.'" and uc.`is_use` = 0';
            }else{
                $where = '`status` = 1';
            }
            $field = 'uc.id as uc_id,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.type,sc.amount,sc.money,sc.goods,sc.goods_category,sc.prom_id,sc.shop';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('user',$user_id)->select();

            return $userCoupon;
        }else{
            return ;
        }
    }


    /**
     * 获取优惠券的使用范围
     * @param int $id 优惠券ID
     * @return string
     */
    public function getUseScope($id){
        //获取优惠券信息
        intval($id);
        $couponInfo = Db::name('shop_coupon')->where('id',$id)->where('status',1)->field('shop,goods,goods_category')->find();
        if( $couponInfo =='' ){
            return '优惠券不存在';
        }
        if( $couponInfo['goods'] > 0 ){
            return '指定商品使用';
        }elseif($couponInfo['goods_category']){
            if( $couponInfo['shop'] > 0 ){
                return '指定店铺下商品分类使用';
            }
            return '指定全场商品分类使用';
        }elseif ($couponInfo['shop'] > 0){
            return '指定店铺使用';
        }else{
            return  '全场通用';
        }
    }


    /**
     * 查询用户已经使用的优惠券
     * @param int $user_id  用户ID
     * @param int $is_use   是否查询可以使用的ID
     * @return array
     */
    public function userUsedCoupon($user_id){
        intval($user_id);
        if($user_id){
            $nowDate = date('Y-m-d H:i:s',time());
            $where = 'uc.`status` = 1 and uc.`start_time` < "'.$nowDate.'" and uc.`end_time` > "'.$nowDate.'" and uc.`is_use` = 1';

            $field = 'uc.id as uc_id,uc.use_time,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.type,sc.amount,sc.money,sc.goods,sc.goods_category,sc.prom_id,sc.shop';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('user',$user_id)->select();

            return $userCoupon;
        }else{
            return ;
        }
    }

    /**
     * 查询用户已经过期的优惠券
     * @param int $user_id  用户ID
     * @param int $is_use   是否查询可以使用的ID
     * @return array
     */
    public function userTimedCoupon($user_id){
        intval($user_id);
        if($user_id){
            $nowDate = date('Y-m-d H:i:s',time());
            $where = 'uc.`status` = 1 and uc.`end_time` < "'.$nowDate.'" and uc.`is_use` = 0 ';

            $field = 'uc.id as uc_id,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.type,sc.amount,sc.money,sc.goods,sc.goods_category,sc.prom_id,sc.shop';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('user',$user_id)->select();

            return $userCoupon;
        }else{
            return ;
        }
    }


    /**
     * 查看优惠券的种类
     * 1/折扣券 2/金额券 3/积分券 4/免邮券
     * @param int $id 优惠券ID
     * @return string
     */
    function getTypeStr($id){
        $type = getTableValue('shop_coupon','id='.$id,'type');
        $discount_type = config('discount_type');
        return $discount_type[$type];
    }


    /**
     * 查询该会员是否领取当前优惠券
     * @param int $coupon_id 优惠券ID
     * @param int $user_id 用户ID
     * @return boolean
     */
    public function obtainCoupon($coupon_id,$user_id){
        $exist = Db::name('user_coupon')->where(['id'=>$user_id,'coupon'=>$coupon_id])->value('id');
        if( $exist == '' ){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 查询该优惠券用户是否可以
     * @param int $coupon_id 优惠券ID
     * @param int $user_id 用户ID
     * @param int $goods_id default null 商品ID，加入商品ID后判断用户能否对该商品使用
     * @return boolean
     */
    public function userUse($coupon_id,$user_id,$goods_id = ''){

        //查找优惠券详细信息
//        if( $goods_id == '' ){
            $couponInfo = $this->couponInfo($coupon_id,'num,used_num,user_group');
            if( $couponInfo['num'] > $couponInfo['used_num'] ){
                //检测优惠券对应的商品
                $user_group_arr = explode(',',$couponInfo['user_group']);
                $user_group = getTableValue('users','id='.$user_id,'level');
                if( in_array($user_group,$user_group_arr) ){
                    return true;
                }
            }
            return false;
//        }else{
//            $couponInfo = $this->couponInfo($coupon_id,'num,used_num,user_group,money,goods,goods_category,prom_id,shop,');
//            //检测优惠券对应的商品
//            $goods_arr = explode(',',$couponInfo['goods']);
//            if( in_array($goods_id,$goods_arr) ){
//                //获取该商品信息
//                $goods_field = 'id,cat_id,';
//                $goodsInfo = DB::name('shop_goods')->field($goods_field)->where('id',$goods_id)->find();
//            }else{
//                return false;
//            }
//        }
    }

    /**
     * 查询优惠券详细信息
     * @param int $coupon_id  优惠券ID
     * @return array
     */
    public function couponInfo($coupon_id,$field = ''){
        intval($coupon_id);
        if($coupon_id){
            $where = '`status` = 1 and `id` = '.$coupon_id;
            //获取所有优惠券
            $userCoupon = $this->_DB->field($field)->where($where)->find();
            return $userCoupon;
        }else{
            return ;
        }
    }

}