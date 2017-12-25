<?php
/**
 * Created by PhpStorm.
 * User: TBMALL
 * Date: 2017/4/10
 * Time: 14:11
 */

namespace app\mshop\model;


use think\Model;

class ShopPromotionGroup extends Model
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