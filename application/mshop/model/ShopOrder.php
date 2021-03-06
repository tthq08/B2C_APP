<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 订单管理模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\mshop\model;


use think\Model;

class ShopOrder extends Model
{
    public function getStatusAttr($value){
        //加载config
        $orderConfig = include APP_PATH."../application/shop/config.php";
        $orderStatus = $orderConfig['order_status'];
        return $orderStatus[$value];
    }
}