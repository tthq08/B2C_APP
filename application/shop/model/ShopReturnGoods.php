<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/4/27
 * Time: 18:38
 */

namespace app\shop\model;


use think\Db;
use think\Model;

class ShopReturnGoods extends Model
{
    public function getOrderIdAttr($value){
        $order_sn = getTableValue('shop_order','id='.$value,'order_sn');
        return $order_sn;
    }
    public function getUserIdAttr($value){
        $user_name = getTableValue('users','id='.$value,'nickname');
        return $user_name;
    }
    public function getStatusAttr($value){
        $status  = config('return_goods');
        return $status[$value];
    }
    public function getOrderGoodsIdAttr($value){
        $goods = Db::name('shop_order_goods')->where('id',$value)->field('goods_name,spec_title')->find();

        return mb_substr($goods['goods_name'],0,20).".. <br> 规格：".$goods['spec_title'];
    }
}