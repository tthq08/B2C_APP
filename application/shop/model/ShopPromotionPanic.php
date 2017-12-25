<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 促销后台管理模型
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\model;


use think\Model;

class ShopPromotionPanic extends Model
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

}