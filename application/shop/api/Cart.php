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


use think\Db;

class Cart
{
    /**
     * 判断该商品（规格），是否允许加入购物车
     * @param $goods
     * @param $spec
     * @return boolean
     */
    public function allow_add($goods,$spec)
    {
        // 查看该商品（规格）是否参加促销活动；
        $promInfo = api('shop','promotion','promInfoFormGoods',$goods);

    }

    /**
     * 获取用户购物车数量
     * @param $user_id
     * @return int
     */
    public function getCartNum($user_id = '')
    {
        if( empty($user_id) ){
            if( empty(session('user.id')) )
            {
                // 获取session_id
                $user_id = session_id();
                $where = '`session_id` = "'.$user_id.'"';
            }else{
                $user_id = session('user.id');
                $where = '`user_id` = '.$user_id;
            }
        }else{
            $where = '`user_id` = "'.$user_id.'"';
        }
        // 显示当前用户购物车数量
        $cart_num = Db::name('shop_cart') ->where($where) ->where('status',1) ->count();
        return $cart_num;
    }



    /**
     * 获取购物车的用户
     * @param $cart
     * @return int
     */
    public function getCartUser($cart)
    {
        $user = Db::name('shop_cart')->where('id',$cart)->field('user_id,session_id')->find();
        if( empty($user) ){
            return false;
        }
        return $user;
    }

}