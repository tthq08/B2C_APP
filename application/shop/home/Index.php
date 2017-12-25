<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块前台主控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\home;

use think\Db;

class Index extends HomeBase
{
    public function index()
    {
        $panicList = api('shop', 'Promotion', 'showPanicList', [0]);

        $this->assign($panicList);
        return $this->fetch();
    }

    /**
     * 抢购页面
     * @return mixed
     */
    public function flash_buy()
    {
        $type = empty(request()->param('type')) ? 1 : request()->param('type');

        $page = empty(request()->param('page')) ? 1 : request()->param('page');

        $panicList = api('shop', 'Promotion', 'showPanicList', [$type, 25, $page]);

        $this->assign($panicList);
        if (request()->isAjax()) {
            return $this->fetch('gettimesflashbuy');
        }
        return $this->fetch();
    }


    /**
     * ajax获取指定分类下的推荐商品
     * @param int $cat_id 分类ID
     * @return mixed
     */
    public function ajaxGetCateCommGoods($cat_id)
    {
        // 获取分类下的所有子分类ID
        $ids = Db::name('goods_category')->where("FIND_IN_SET($cat_id,parent_id_path)")->column('id', 'id');
        $ids = implode(',', $ids);

        $goods_list = Db::name('shop_goods')->where('cat_id', 'IN', $ids)->cache(true)->select();
        $this->assign('goodsList', $goods_list);
        return $this->fetch();
    }


    // 二维码
    public function qr_code()
    {
        include EXTEND_PATH . '/phpqrcode/phpqrcode.php';
        $param = input();
        $url = urldecode($param['data']);
        error_reporting(E_ERROR);
        \QRcode::png($url);
    }


    /**
     * 获取商品分类
     * @param $pid
     * @return mixed
     */
    public function category($pid = '')
    {
        if (empty($pid)) {
            return [];
        }
        // 获取当前下的子分类
        $cateList = api('shop', 'Goods', 'categoryList', [$pid]);
        foreach ($cateList as $k => $cate) {
            $list = api('shop', 'Goods', 'categoryList', [$cate['id']]);
            $cateList[$k]['list'] = $list;
        }
        $this->assign('list', $cateList);
        return $this->fetch('ajaxcategory');
    }


    public function ajaxBarCart()
    {
        if (request()->post()) {
            $postData = request()->post();
            // dump($postData);die;
            //修改所有选中为取消选中
            $all_cart = implode(',', array_keys($postData['goods_num']));
            Db::name('shop_cart')->where('id in(' . $all_cart . ')')->update(['selected' => 0]);
            if (!empty($postData['cart_select'])) {
                //修改选中的为选中
                $selected = implode(',', array_keys($postData['cart_select']));
                Db::name('shop_cart')->where('id in(' . $selected . ')')->update(['selected' => 1]);
            }
            //修改所有购物车商品的数量
            foreach ($postData['goods_num'] as $key => $value) {
                $goods_id = Db::name('shop_cart')->where('id', $key)->value('goods');
                $allow_max = Db::name('shop_goods')->where('id', $goods_id)->value('max_buy');
                if ($allow_max > 0 && $allow_max < $value) {
                    return ['code' => 0, 'msg' => lang('Cart_nums_more_than_allow', [$allow_max]), 'data' => ['cart_id' => $key, 'nums' => $allow_max]];
                }
                Db::name('shop_cart')->where('id', $key)->update(['goods_num' => $value]);
            }
        }
        $this->login_id = session('user.id');
        if (empty($this->login_id) || $this->login_id == '') {
            $this->login_id = $this->session_id;
        }
        //通过login_id查询当前的购物车订单
        $login_id = $this->login_id;
        if (is_numeric($login_id)) {
            $session_id = $this->session_id;
            $where = "user_id=$login_id OR session_id='$session_id'";
        } else {
            $where = ['session_id' => $login_id];
        }
        $cartList = [];
        $total_price = 0;
        $selected_num = 0;
        //获取当前店铺ID的信息
        $cart = Db::name('shop_cart')->where($where)->where('status', 1)->select();
        $cartList['check_num'] = Db::name('shop_cart')->where($where)->where(['status' => 1, 'selected' => 1])->count('id');
        $selected_num += $cartList['check_num'];
        $newCart = [];
        foreach ($cart as $kis => $item) {

            if ($item['selected'] == 1) {
                $total_price += compute()->promGoodsPrice($item['goods'], $item['spec_key'],false,$item['goods_num']);
            }
            $newCart[] = $item;
        }
        // dump($newCart);
        $cartList = $newCart;

        //$total_price = priceFormat($total_price,1);
        // print_r($cartList);
        $this->assign('selected_num', $selected_num);
        $this->assign('cartList', $cartList);
        $this->assign('total_price', $total_price);
        return $this->fetch();
    }

    public function group_buy()
    {
        return $this->fetch();
    }

    public function new_goods()
    {
        return $this->fetch();
    }

    public function hot_goods()
    {
        return $this->fetch();
    }

    public function activity()
    {
        return $this->fetch();
    }

    public function getLang()
    {
        dump(cookie('think_var'));
    }

    // 手动点击加载更多商品时获取商品数据
    public function ajaxNewestGoods($page, $nums = 15, $is_new = 0)
    {
        $span = $nums;
        $where['status'] = 1;
        $where['is_audit'] = 1;
        $where['trash'] = 0;
        if ($is_new == 1) {
            $where['is_new'] = 1;
        }
        $goods = Db::name('shop_goods')->where($where)->order('create_time DESC')->limit($page * $span, $span)->select();
        $this->assign('goods_list', $goods);
        return $this->fetch();
    }

    // 手动点击加载更多商品时获取商品数据
    public function ajaxHotGoods($page, $nums = 5, $is_hot = 0)
    {
        $span = $nums;
        $where['status'] = 1;
        $where['is_audit'] = 1;
        $where['trash'] = 0;
        if ($is_hot == 1) {
            $where['is_hot'] = 1;
        }
        $goods = Db::name('shop_goods')->where($where)->order('sales_sum DESC')->limit($page * $span, $span)->select();
        $this->assign('goods_list', $goods);
        return $this->fetch('ajaxnewestgoods');
    }

    public function ajaxGetBrand($page, $nums = 14, $is_hot = 0)
    {
        $where = [
            'status' => '1',
            'is_home_comm' => 1,
            'is_audit' => 1,
            'trash' => 0,
        ];
        $brands = Db::name('shop_brand')->where($where)->order(['sort' => 'ASC', 'id' => "DESC"])->limit($page * $nums, $nums)->select();
        $this->assign('list', $brands);
        return $this->fetch();
    }

    public function winLogin($url = '')
    {
        $url = !empty($url) ? base64_decode($url) : '';
        $this->assign('url', $url);
        return $this->fetch();
    }

    public function ajaxGetCollectGoods()
    {
        $user_id = session('user.id');
        if (empty($user_id)) {
            return ['code' => 4001, 'msg' => '未检测到登录用户，请先行登录。'];
        }
        $model = Db::name('shop_goods_collect');
        $collectList = $model->where('user_id', $user_id)->where('status', 1)->paginate(8);

        $this->assign('page', $collectList->render());
        $this->assign('goods_collect_list', $collectList);

        $goods_ids = Db::name('shop_goods_collect')->field('GROUP_CONCAT(goods_id) as ids')->where(['user_id' => $user_id, 'status' => 1])->find();
        $cates = Db::name('shop_goods')->field('GROUP_CONCAT(cat_id) as ids')->where('id', 'IN', $goods_ids['ids'])->find();
        $goods_comm = Db::name('shop_goods')->where('cat_id', 'IN', $cates['ids'])->order('sales_sum')->limit(15)->select();
        $this->assign('recommend_goods', $goods_comm);
        return $this->fetch();
    }

    public function ajaxGetFocusShopes()
    {
        $user_id = session('user.id');
        if (empty($user_id)) {
            return ['code' => 4001, 'msg' => '未检测到登录用户，请先行登录。'];
        }

        $shops = Db::name('cust_shop_collect')->where(['user_id' => $user_id, 'status' => 1])->paginate(5);
        $collectList = $shops->all();
        foreach ($collectList as $key => $collect) {
            $collectList[$key]['shop'] = Db::name('cust_shop')->find($collect['shop_id']);
            $collectList[$key]['shop_goods_rank'] = Db::name('shop_goods_comment')->where(['shop_id' => $collect['shop_id']])->avg('goods_rank');
            $collectList[$key]['shop_serv_rank'] = Db::name('shop_goods_comment')->where(['shop_id' => $collect['shop_id']])->avg('service_rank');
        }
        $this->assign('page', $shops->render());
        $this->assign('collect_list', $collectList);
        return $this->fetch();
    }

    public function article($id)
    {
        $info = Db::name('web_intact')->find($id);
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function news($id)
    {
        $news = Db::name('web_content')->find($id);
        $extends = Db::name('web_content_article')->where('aid', $id)->find();
        $news = array_merge($news, $extends);
        $this->assign('info', $news);
        return $this->fetch();
    }

    // 查询订单流水的支付状态
    public function check_order_pay_status($serial_id = 0)
    {
        $order_pay = Db::name('shop_order_pay')->where('id', $serial_id)->value('is_pay');
        if (!empty($order_pay)) {
            $this->success('支付完成');
        } else {
            $this->error('支付状态仍未完成');
        }
    }


    // 直接下单的订单查询
    public function oquery()
    {
        if (request()->isPost()) {
            $data = input();
            $res = Db::name('shop_order')->where(['phone' => $data['mobile'], 'ord_pwd' => md5($data['ord_pwd'])])->find();
            if ($res) {
                return ['code' => 1, 'msg' => '查询订单成功', 'data' => $res['id']];
            } else {
                $this->error('抱歉，您查询的订单不存在，请确认正确信息后再尝试。');
            }

        } else {
            return $this->fetch();
        }
    }

    public function ordershow($id, $ord_pwd)
    {
        $order_info = Db::name('shop_order')->where('id', $id)->find();
        if ($order_info['ord_pwd'] != md5($ord_pwd)) {
            $this->error('参数非法...');
        }
        //获取订单中的所有商品
        $goodsList = Db::name('shop_order_goods')->where('order_id', $order_info['id'])->select();
        $order_info['goods_list'] = $goodsList;
        $this->assign('order_info', $order_info);
        $status = config('order_status');
        $status = $status[$order_info['status']];
        $this->assign('order_status', $status);
        return $this->fetch();
    }

    public function uploadWeb()
    {
        $data = input();
        return $this->fetch();
    }

}