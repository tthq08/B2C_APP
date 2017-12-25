<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 促销后台管理模型
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\mshop\model;


use think\Model;

class ShopPromotionDiscount extends Model
{

    public function getGoodsAttr($value)
    {
        //通过id获取基本商品基本信息
        $goods_name = getTableValue('shop_goods','id='.$value,'title');
        if( mb_strlen($goods_name) > 20 )
        {
            return mb_substr($goods_name,0,20).'..';
        }else{
            return $goods_name;
        }
    }

    public function getPriceAttr($value)
    {
        return priceFormat($value);
    }

}