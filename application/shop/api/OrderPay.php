<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： b2b2c 插入订单功能,购物车商品金额结算功能
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------
/**
 * 备注： 该金额结算功能不可改,如进行功能变换请参照策略模式编写。
 *
 */

namespace app\shop\api;

use think\Db;
use app\shop\api\Compute;

class OrderPay
{
    /**
     * 商品数据表
     * @var int
     */
    public $cartTable = 'shop_cart';

    /**
     * 用户id
     * @var int
     */
    public $user;

    /**
     * 订单密码
     * @var string
     */
    public $ord_pwd = '';

    /**
     * 临时用户标记
     * @var int
     */
    public $session_id;

    /**
     * 设置购物车id
     * @var array
     */
    public $cartList = [];


    /**
     * 用户可使用的优惠券
     * [{'优惠券id'},{'优惠券id'}]
     * @var array
     */
    public $couponList = [];


    /**
     * 已经计算过的优惠券
     * @var array
     */
    private $selectedCoupons = [];


    /**
     * 前台用户选择的优惠券(提交订单时使用)
     * ['商家id'=>'优惠券id']
     * @var array
     */
    public $orderCoupons = [];


    /**
     * BIZ券
     * ['优惠券1','优惠券2','优惠券3']
     * @var array
     */
    public $bizCoupons = [];


    /**
     * 是否使用积分
     * @var bool
     */
    public $isUsePoints = false;


    /**
     * order_pay 信息
     * @var array
     */
    private $order_pay = [];


    /**
     * 商家|订单
     * @var array
     */
    private $shopList = [];


    /**
     * 订单列表
     * @var array
     */
    private $orderList = [];


    /**
     * 订单商品列表
     * @var array
     */
    private $goodsList = [];


    /**
     * 商品id列表
     * @var array
     */
    private $goodsIdList = [];


    /**
     * 订单下的分类列表
     * @var array
     */
    private $goodsCategoryList = [];


    /**
     * 平台商品分类优惠券
     * @var array
     */
    private $platformCoupons = [];


    /**
     * 用户积分余额
     * @var int
     */
    private $userPoints;


    /**
     * 是否需要插入到数据库
     * @var bool
     */
    private $isInsert = false;


    /**
     * 计算类实例
     * @var \stdClass
     */
    private $compute;


    /**
     * 用户选中的地址id(提交订单时传入)(用户直接下单时提供地址数组数据，正常下单时提供地址ID)
     * @var int
     * @var int
     */
    public $address_id;

    /**
     * 用户设置的地址(用户直接下单时提供地址数组数据，正常下单时提供地址ID)
     * @var int
     */
    public $address = [];


    /**
     * 发票抬头(提交订单时传入)
     * @var
     */
    protected $invoice_title;


    /**
     * 用户备注(提交订单时传入)
     * @var
     */
    protected $user_remark;


    /**
     * 获取的优惠券信息
     * @var
     */
    public $couponInfoList = [];


    /**
     * OrderPay constructor.
     */
    public function __construct()
    {
        $this->compute = new Compute();
        $this->time = time();
    }


    /**
     * 获取用户积分余额
     * @return mixed
     */
    private function selectUserPoints()
    {
        // 获取用户积分余额
        if ($this->user == 0) {
        } else {
            $this->userPoints = getTableValue('users', 'id=' . $this->user, 'pay_points');
        }
    }


    /**
     * 循环计算的所有订单
     * @return array
     */
    private function computeOrder()
    {
        // 优惠券优惠金额
        $couponPrice = 0;

        $cartList = Db::name($this->cartTable)->where('id', 'in', $this->cartList)->where('user_id', $this->user)->where(['status' => 1, 'selected' => 1])->select();


        $order = ['total_price' => 0, 'payable_price' => 0, 'discount_price' => 0, 'points' => 0, 'points_price' => 0, 'coupon_price' => 0, 'postage' => 0];
        // 循环查询价格
        foreach ($cartList as $ck => $cart) {
            // 获取商品信息
            $cartGoods = $this->cartAmount($cart);

            // 计算订单金额
            $order['total_price'] += $cartGoods['total_price'];
            $order['payable_price'] += $cartGoods['payable_price'];
            $order['discount_price'] += $cartGoods['discount_price'];
            $order['points'] += $cartGoods['points'];
            $order['points_price'] += $cartGoods['points_price'];
            //计算商品邮费
        }

        // 计算优惠券优惠
        $orderCouponPrice = $this->orderCouponPrice($order['payable_price']);

        if (!empty($orderCouponPrice)) {
            $order['coupon_id'] = $orderCouponPrice['coupon_id'];
            $order['coupon_price'] += $orderCouponPrice['price'];
            $order['payable_price'] -= $orderCouponPrice['price'];
            $couponPrice += $orderCouponPrice['price'];
        }

        if (!empty($this->orderCoupons)) {
            $bizCouponPrice = $this->bizCouponPrice($order['payable_price']);
            if (!empty($bizCouponPrice)) {
                $order['biz_coupon'] = implode(',', $bizCouponPrice['coupon_list']);
                $order['biz_coupon_price'] = $bizCouponPrice['price'];
                $order['coupon_price'] += $bizCouponPrice['price'];
                $order['payable_price'] -= $bizCouponPrice['price'];
                $couponPrice += $bizCouponPrice['price'];
            }
        }

        // 计算订单优惠
        $orderPromId = compute()->orderPrice($order['payable_price'], true);
        $orderPromPrice = compute()->orderPrice($order['payable_price']);
        $order['order_prom_id'] = $orderPromId;
        $order['order_prom_price'] = $orderPromPrice;

        // 更新支付金额
        $order['payable_price'] -= $orderPromPrice;
        // 更新优惠金额
        $order['discount_price'] += $orderPromPrice;

        if ($this->isInsert == true) {
            //创建订单号
            $order['order_sn'] = createOrderSn();
            //获取地址信息
            if (!empty($this->address_id)) {
                $address = Db::name('user_address')->where('id', $this->address_id)->find();
            } else {
                $address = $this->address;
            }
            $order['province'] = $address['province'];
            $order['city'] = $address['city'];
            $order['district'] = $address['district'];
            $order['address'] =  $address['address'];
            $order['invoice_title'] = $this->invoice_title;
            $order['user_remark'] = $this->user_remark;
            $order['consignee'] = $address['consignee'];//收货人
            $order['phone'] = $address['mobile'];//手机号码
            $order['zipcode'] = $address['zip'];//邮编
            $order['user_id'] = empty(session('user.id')) ? 0 : session('user.id');//用户ID
            $order['add_time'] = date('Y:m:d H:i:s', time());
            $order['last_pay_time'] = date('Y-m-d H:i:s', strtotime('+ 2days'));
        } else {
            // 获取用户除商品,商品分类之外的优惠券,通过订单价格
            $this->findCoupon($order['payable_price']);
        }

        $this->orderList = $order;

        $orderPay = [
            'total_price' => $order['total_price'],
            'payable_price' => $order['payable_price'],
            'points' => $order['points'],
            'points_price' => $order['points_price'],
            'discount_price' => $order['discount_price'],
            'coupon_price' => $couponPrice,
            'postage' => $order['postage'],
            'last_pay_time' => date('Y-m-d H:i:s', strtotime('+ 2days')),
        ];

        $this->order_pay = $orderPay;
    }


    /**
     * 计算单个购物车内容的金额
     * @param $cartInfo
     * @return array
     */
    private function cartAmount($cartInfo)
    {
        // 商品应付金额
        $goods_payable_price = compute()->promGoodsPrice($cartInfo['goods'], $cartInfo['spec_key'],false,$cartInfo['goods_num']);

        // 商品总金额
        if ( empty($cartInfo['spec_id']) ){
            // 无规格,取商品价格算总金额
            $goods_total_price = $cartInfo['shop_price'] * $cartInfo['goods_num'];
        } else {
            // 有规格,取规格价格算总金额
            $spec_price = Db::name('shop_spec_price')->where('id', $cartInfo['spec_id'])->value('price');
            $goods_total_price = $spec_price * $cartInfo['goods_num'];
        }

        $goods_promotion_info = compute()->promGoodsPrice($cartInfo['goods'], $cartInfo['spec_key'],true);
        $goods_promotion_id = empty($goods_promotion_info['p_id']) ? 0 : $goods_promotion_info['p_id'];
        $goods_promotion_type = empty($goods_promotion_info['p_type']) ? 0 : $goods_promotion_info['p_type'];

        // 积分使用金额
        $points_price = 0;
        $points = 0;
        if (!empty($this->isUsePoints) && $this->isUsePoints == true && $this->userPoints > 0) {
            // 商品使用的积分金额
            $points_price = api('shop', 'compute', 'pointsPrice', [$cartInfo['goods'], $this->userPoints]) * $cartInfo['goods_num'];
            // 商品使用的积分
            $points = $points_price * tb_config('web_point', 1, 1);
            // 更新用户总积分
            $this->userPoints -= $points;
            // 更新商品应付金额
            $goods_payable_price = $goods_payable_price - $points_price;
        }

        $this->goodsList[$cartInfo['id']] = [
            'goods_id' => $cartInfo['goods'],
            'payable_price' => $goods_payable_price,
            'total_price' => $goods_total_price,
            'discount_price' => $goods_total_price - $goods_payable_price,
            'pay_price' => round($goods_payable_price / $cartInfo['goods_num'], 2),
            'postage' => 0,
            'shop_price' => $cartInfo['shop_price'],
            'goods_num' => $cartInfo['goods_num'],
            'goods_name' => $cartInfo['goods_name'],
            'points' => $points,
            'prom_id' => $goods_promotion_id,
            'prom_type' => $goods_promotion_type,
            'points_price' => $points_price,
            'spec_id' => $cartInfo['spec_id'],
            'spec_key' => $cartInfo['spec_key'],
            'spec_title' => $cartInfo['spec_text'],
        ];

        if (!$this->isInsert) {
            $this->goodsList[$cartInfo['id']]['add_time'] = $cartInfo['add_time'];
        }

        $this->goodsIdList[$cartInfo['goods']] = $goods_payable_price;

        // 规格ID
        if (!empty($cartInfo['spec_id'])) {
            $this->goodsList[$cartInfo['id']]['spec_title'] = getTableValue('shop_spec_price', 'id=' . $cartInfo['spec_id'], 'key_name');
        } else {
            $this->goodsList[$cartInfo['id']]['spec_title'] = '';
        }

        // 查找优惠券
        $this->findGoodsCoupon($cartInfo['goods'], (float)$goods_payable_price);

        $nowCate_id = getTableValue('shop_goods', 'id=' . $cartInfo['goods'], 'cat_id');

        // 当前所有该分类下的价格总和
        $this->goodsCategoryList[$nowCate_id] = empty($this->goodsCategoryList[$nowCate_id]) ? 0 : $this->goodsCategoryList[$nowCate_id];

        $this->goodsCategoryList[$nowCate_id] += $goods_payable_price;

        return $this->goodsList[$cartInfo['id']];
    }


    /**
     * 查找除商品和商品分类之外的优惠券
     * @param float $orderPrice
     * @return array
     */
    protected function findCoupon($orderPrice)
    {
        // 查询店铺下的该分类优惠券
        $time = time();
        $where = '`coupon_level` <> 1 and `start_time` <= ' . $time . ' and `end_time` >= ' . $time . ' and `used_num` < `num` and `status` = 1' . ' and `money` <=' . $orderPrice . ' and `user` = ' . $this->user;

        $coupons = Db::name('user_coupon')->where($where)->order('end_time asc')->select();
        $shopCoupons = Db::name('user_coupon')->where($where)->order('end_time asc')->select();
        array_merge($coupons, $shopCoupons);
        // 放到当前订单下的优惠券列表
        foreach ($coupons as $k => $coupon) {
            foreach ($this->shopList as $shop) {
                if (empty($coupon['shop_id']) || $coupon['shop_id'] == $shop) {
                    if (empty($this->couponList)) {
                        $this->couponList = [];
                    }
                    if (!in_array($coupon, $this->couponList)) {
                        $this->couponList[$coupon['id']] = $coupon;
                    }
                }
            }
        }

    }


    /**
     * 查找商品优惠券
     * @param int $goods_id 商品id
     * @param int $temporary_price 计算价格
     * @param int $shop 店铺id
     * @return array
     */
    protected function findGoodsCoupon($goods_id, $temporary_price)
    {
        // 条件
        $time = time();
        $where = 'uc.`coupon_level` <> 1 and uc.`start_time` <= ' . $time . ' and uc.`end_time` >= ' . $time . ' and uc.`used_num` < `num` and uc.`status` = 1' . ' and uc.`money` <=' . $temporary_price . '';
        // 查找优惠券
        $couponList = Db::name('user_coupon')->alias('uc')->join('__USER_COUPON_BIND__ ucb', 'ucb.user_coupon = uc.id')->where('ucb.goods', $goods_id)->where($where)->field('uc.*')->order('uc.`end_time` asc')->select();

        if (empty($this->couponList)) {
            $this->couponList = [];
        }

        foreach ($couponList as $k => $coupon) {
            if (!in_array($coupon['id'], $this->couponList)) {
                $this->couponList[$coupon['id']] = $coupon;
            }
        }

    }


    /**
     * 查找商品分类优惠券
     * @return array
     */
    protected function findGoodsCategoryCoupon()
    {

        foreach ($this->goodsCategoryList as $category => $price) {
            // 查询店铺下的该分类优惠券
            $time = time();
            $where = 'uc.`coupon_level` <> 1 and uc.`start_time` <= ' . $time . ' and uc.`end_time` >= ' . $time . ' and uc.`used_num` < `num` and uc.`status` = 1' . ' and uc.`money` <=' . $price;

            $coupons = Db::name('user_coupon')->alias('uc')->join('__USER_COUPON_BIND__ ucb', 'ucb.`user_coupon` = uc.`id`')->where('ucb.`goods_category`', $category)->where($where)->order('us.`end_time` asc')->select();

            // 查询无店铺的该分类优惠券
            $platformCoupons = Db::name('user_coupon')->alias('uc')->join('__USER_COUPON_BIND__ ucb', 'ucb.`user_coupon` = uc.`id`')->where('ucb.`goods_category`', $category)->where($where)->where('shop_id', 0)->order('us.`end_time` asc')->select();
            $this->platformCoupons[$category] = $platformCoupons;

            // 放到当前订单下的优惠券列表
            foreach ($coupons as $k => $coupon) {
                if (!in_array($coupon, $this->couponList)) {
                    $this->couponList[$coupon['id']] = $coupon;
                }
            }
        }
    }


    /**
     * 计算biz券产生的金额
     * @return mixed
     */
    private function bizCouponPrice($price)
    {
        $couponPrice = 0;
        $couponList = [];
        if (!empty($this->orderCoupons[2])) {
            foreach ($this->orderCoupons[2] as $k => $coupon) {
                $couponInfo = api('shop', 'Coupon', 'userCouponInfoFromId', [$coupon]);
                $this->couponInfoList[$coupon] = $couponInfo;
                if (empty($couponInfo) || $couponInfo['user'] != $this->user) {
                    return (int)0;
                }
                if ($couponInfo['start_time'] < $this->time && $couponInfo['end_time'] > $this->time && !in_array($couponInfo['id'], $this->selectedCoupons)) {
                    // 总金额减少
                    $price -= $couponInfo['quota'];
                    if ($price > 0) {
                        $this->selectedCoupons[] = $couponInfo['id'];
                        $couponList[] = $couponInfo['id'];
                        // 金额叠加
                        $couponPrice += $couponInfo['quota'];
                    }
                }
            }
            return ['price' => $couponPrice, 'coupon_list' => $couponList];
        }
        return $couponPrice;
    }


    /**
     * 订单优惠券金额计算
     * @return mixed
     */
    private function orderCouponPrice($price)
    {
        // 查询订单的优惠券
        if (!empty($this->orderCoupons[1])) {
            $couponId = $this->orderCoupons[1];
            $couponInfo = api('shop', 'Coupon', 'userCouponInfoFromId', [$couponId]);

            if (empty($couponInfo) || $couponInfo['user'] != $this->user) {
                return (int)0;
            }
            if ($couponInfo['start_time'] < $this->time && $couponInfo['end_time'] > $this->time && !in_array($couponInfo['id'], $this->selectedCoupons)) {
                // 如果是针对商品
                $couponPrice = 0;
                if (!empty($couponInfo['goods'])) {
                    // 检测商品是否具备使用该优惠券资格
                    if (array_key_exists($couponInfo['goods'], $this->goodsIdList)) {
                        if ($this->goodsIdList[$couponInfo['goods']] > $couponInfo['money']) {
                            // 获取当前商品的价格,扣除当前商品和订单的价格
                            $cprice = $this->goodsList[$couponInfo['goods']]['payable_price'];
                        } else {
                            return (int)0;
                        }
                    } else {
                        return (int)0;
                    }
                } // 如果是针对商品分类
                elseif (empty($couponInfo['goods']) && !empty($couponInfo['goods_category'])) {

                    if (array_key_exists($couponInfo['goods_category'], $this->goodsCategoryList)) {
                        if ($this->goodsCategoryList[$couponInfo['goods_category']] > $couponInfo['money']) {
                            // 获取当前商品分类的价格,扣除当前订单的价格
                            $cprice = $this->goodsCategoryList[$this->goodsCategoryList[$couponInfo['goods_category']]];
                        } else {
                            return (int)0;
                        }
                    } else {
                        return (int)0;
                    }
                } else {
                    if ($price > $couponInfo['money']) {
                        $cprice = $price;
                    } else {
                        return (int)0;
                    }
                }
                if ($couponInfo['discount_type'] == 1) {
                    // 折扣
                    $couponPrice = round($cprice * $couponInfo['quota'] / 100);
                } elseif ($couponInfo['discount_type'] == 2) {
                    // 金额抵扣
                    $couponPrice = $cprice - $couponInfo['quota'];
                }else{
                    $couponPrice = $price;
                }
                $price = $price - $couponPrice;
                // 放入已经使用的优惠券当中
                $this->selectedCoupons[] = $couponInfo['id'];
                $this->couponInfoList[$couponInfo['id']] = $couponInfo;

                return ['price' => $price, 'coupon_id' => $couponInfo['id']];
            } else {
                return (int)0;
            }
        }
        return (int)0;
    }


    /**
     * 启动项
     * 该启动项顺序不可乱
     * @return mixed
     */
    private function startupItem()
    {
        // 启动查询用户积分余额
        $this->selectUserPoints();

        // 启动订单计算
        $this->computeOrder();
    }


    /**
     * 获取金额数据
     * @return array
     */
    public function get()
    {
        $this->startupItem();
        $jsonData = [
            'order_pay' => $this->order_pay,
            'order_list' => $this->orderList,
            'goods_list' => $this->goodsList,
        ];

        return json_encode($jsonData);
    }


    /**
     * 插入订单
     * @return mixed
     */
    public function insert()
    {
        $this->isInsert = true;
        $this->startupItem();

        Db::startTrans();
        try {
            $orderIdList = $this->insertOrder();
            $serial_id = $this->insertOrderPay($orderIdList);
            $order_ids = implode(',', $orderIdList);
            api('shop','Goods','reduce_stock',[$order_ids]);   //扣除订单中的商品库存

            $this->deteleCart();
            // 修改优惠券使用次数
            $this->updateUsedCoupon();
            Db::commit();
        } catch (\ErrorException $e) {
            Db::rollback();
            return $e;
        }
        $jsonData = [
            'order_pay' => $this->order_pay,
            'order_list' => $this->orderList,
            'goods_list' => $this->goodsList,
            'serial_id' => $serial_id,
        ];
        return json_encode($jsonData);
    }


    /**
     * 插入订单表
     * @return mixed
     */
    private function insertOrder()
    {
        $idList = [];
        // 插入

        $order_id = Db::name('shop_order')->insertGetId($this->orderList);
        $idList[] = $order_id;
        $this->insertOrderGoods( $order_id);

        return $idList;
    }

    /**
     * 删除提交的购物车
     * @return mixed
     */
    private function deteleCart()
    {
        // 删除购物车中已经提交的商品
        if (!empty($this->session_id)) {
            Db::name($this->cartTable)->where('session_id', $this->session_id)->where('id', 'in', $this->cartList)->delete();
        } else {
            Db::name($this->cartTable)->where('user_id', $this->user)->where('id', 'in', $this->cartList)->delete();
        }
    }


    /**
     * 插入订单商品表
     * @return mixed
     */
    private function insertOrderGoods($order_id)
    {
        foreach ($this->goodsList as $orderGoods) {
            $orderGoods['order_id'] = $order_id;
            Db::name('shop_order_goods')->insert($orderGoods);
        }
    }


    /**
     * 插入订单支付表
     * @return mixed
     */
    private function insertOrderPay($orderIdList)
    {
        $this->order_pay['serial_sn'] = createSerialSn();
        $this->order_pay['order'] = implode(',', $orderIdList);
        $this->order_pay['add_time'] = date('Y-m-d H:i:s', time());
        $user_id = empty(session('user.id')) ? 0 : session('user.id');
        $this->order_pay['user_id'] = $user_id;
        $insertId = Db::name('shop_order_pay')->insertGetId($this->order_pay);
        return $insertId;
    }


    private function updateUsedCoupon()
    {
        foreach ($this->selectedCoupons as $coupon) {
            // 获取信息
            $couponInfo = $this->couponInfoList[$coupon];
            // 修改使用次数
            if ($couponInfo['used_num'] + 1 == $couponInfo['num']) {
                $data['is_use'] = 1;
            }
            $data['use_time'] = $this->time;
            Db::name('user_coupon')->where('id', $coupon)->update($data);
            Db::name('user_coupon')->where('id', $coupon)->setInc('used_num');
            // 增加优惠券的使用次数
            Db::name('shop_coupon')->where('id', $couponInfo['coupon'])->setInc('used_num');
        }
    }


}