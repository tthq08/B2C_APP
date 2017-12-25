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
use think\Db;

class ShopPromotion extends Model
{
    /*
     * 将促销分类ID转换为促销分类名称
     */
    public function getPTypeAttr($value)
    {
        $p_type = [1=>lang('panic_buying'),2=>lang('group_buying'),3=>lang('discount_prom'),4=>lang('order_prom')];
        return $p_type[$value];
    }

    public function getPIdAttr($value,$data)
    {
        //获取活动标题
        switch ( $data['p_type'] ){
            case 1:
                $model = 'panic';
                break;
            case 2:
                $model = 'group';
                break;
            case 3:
                $model = 'discount';
                break;
            case 4:
                $model = 'order';
                break;
        }
        $title = Db::name('shop_promotion_'.$model)->where($model.'_id',$value)->value('title');

        if( mb_strlen($title) > 20 )
        {
            return mb_substr($title,0,20).'..';
        }else{
            return $title;
        }
    }

    public function getGoodsAttr($value)
    {
        //通过id获取基本商品基本信息
        if($value !== ''){
            $goods_name = getTableValue('shop_goods','id='.$value,'title');
            if( mb_strlen($goods_name) > 20 )
            {
                return mb_substr($goods_name,0,20).'..';
            }else{
                return $goods_name;
            }
        }else{
            return $value;
        }

    }

}