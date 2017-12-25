<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/5/6
 * Time: 10:10
 */

namespace app\member\model;

use think\Model;


class UserDistribution extends Model
{
    public function getUserIdAttr($value){
        $username = getTableValue('users','id='.$value,'nickname');
        return $username;
    }
    public function getUserRAttr($value){
        $username = getTableValue('users','id='.$value,'nickname');
        return $username;
    }
    public function getIsDividedIntoAttr($value){
        if( $value == 1 ){
            return '是';
        }else{
            return '否';
        }
    }
    public function getOrderIdAttr($value){
        $order_sn = getTableValue('shop_order','id='.$value,'order_sn');
        return $order_sn;
    }
}