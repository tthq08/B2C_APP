<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/8/17
 * Time: 下午4:16
 */

namespace app\shop\home;


class Coupon extends HomeBase
{

    /**
     * 首页优惠券领取页面
     * @return mixed
     */
    public function index()
    {
        $param = request()->param();
        $sort = '';
        $sort_p = '';
        if( !empty($param['sort']) ){

            if( $param['sort'] == 'money' ){
                $sort = 'quota desc';
                $sort_p = 'money';
            }elseif ( $param['sort'] == 'time' ){
                $sort = 'send_end_time desc';
                $sort_p = 'time';
            }
            $param['sort'] = $sort_p;
        }
        $coupon_level = '';
        if( !empty($param['cl']) ){
            if( $param['cl'] == 1 ){
                $coupon_level = 1;
            }elseif ( $param['cl'] == 2 ){
                $coupon_level = 2;
            }
            $param['cl'] = $coupon_level;
        }
        $userLevel = '';
        if( session('user.id') ){
            $userLevel = session('user.level');
        }
        $couponList = api('shop','Coupon','indexCouponList',[30,$userLevel,$sort,$coupon_level]);
        $coupons = $couponList->items();
        $couponLevelList = api('shop','Coupon','couponLevel');
        foreach ($coupons as $k=>$coupon)
        {
            // 获取券等级名称
            $coupons[$k] = $this->couponin($coupon,$couponLevelList);
            // 查看是否已经领取
            $is_receive = api('shop','Coupon','obtainCoupon',[$coupon['id'],session('user.id')]);
            $coupons[$k]['is_receive'] = $is_receive;
        }

        $this->assign('param',$param);
        $this->assign('coupon_level',$couponLevelList);
        $this->assign('list',$coupons);
        $this->assign('page',$couponList->render());
        return $this->fetch();
    }


    /**
     * 优惠券领取
     * @return mixed
     */
    public function receive()
    {
        if( empty(request()->param('coupon')) ){
            $this->error('请选择一张优惠券');
        }
        if( empty(session('user.id')) ){
            cookie('login_referer',url('shop/coupon/index'));
            $this->error('请先登录','member/login/index');
        }
        $coupon = request()->param('coupon');
        // 检测该优惠券是否能够领取
        $isReceived = api('shop','Coupon','obtainCoupon',[$coupon,session('user.id')]);
        if( !empty($isReceived) ){
            $this->success('您已领取该优惠券,无须重复领取');
        }
        $is_receive = api('shop','Coupon','isReceiveCoupon',[$coupon,session('user.id')]);
        if( $is_receive == true ){
            // 发放优惠券
            $send = api('shop','CouponSend','send_coupon',[session('user.id'),$coupon]);
            if( $send == true ){
                $this->success('领取成功');
            }
        }
        $this->error('抱歉,您暂时不可领取该优惠券!');

    }


    /**
     * 优惠码领取
     * @return mixed
     */
    public function code_receive()
    {
        if( empty(request()->param('code')) ){
            $this->error('请选择一张优惠券');
        }
        if( empty(session('user.id')) ){
            $this->error('请先登录');
        }
        $code = request()->param('code');
        // 检测该优惠券是否能够领取
        $coupon = api('shop','Coupon','couponCodeInfo',[$code]);
        if( !empty($coupon['is_receive']) ){
            $this->success('您已领取该优惠券,无须重复领取');
        }
        // 发放优惠券
        $send = api('shop','CouponSend','codeSend',[$code,session('user.id')]);
        if( $send == true ){
            $this->success('领取成功');
        }

        $this->error('抱歉,您暂时不可领取该优惠券!');
    }


    /**
     * 优惠码领取页面
     * @return mixed
     */
    public function code()
    {
        return $this->fetch();
    }


    /**
     * ajax加载优惠券
     * @return mixed
     */
    public function ajaxcoupon()
    {
        if( empty(request()->post('code')) ){
            $this->error('请输入优惠码提取');
        }
        $code = request()->post('code');
        // 获取券的信息
        $coupon = api('shop','Coupon','couponCodeInfo',[$code]);
        if( empty($coupon) ){
            $this->error('找不到优惠券,请确认您的输入及大小写是否正确');
        }
        $message = '立即领取';
        if( $coupon['is_receive'] == 1 ){
            $message = '已领取';
        }
        if( !empty($coupon['user']) ){
            if( $coupon['user'] != session('user.id') ){
                $message = '不可领取';
            }
        }
        $coupon['message'] = $message;
        $coupon = $this->couponin($coupon,api('shop','Coupon','couponLevel'));
        $this->assign('vo',$coupon);
        return $this->fetch();
    }


    /**
     * 券信息
     * @param $coupon
     * @return mixed
     */
    protected function couponin($coupon,$couponLevelList)
    {
        // 获取券等级名称
        $coupon['coupon_level_name'] = $couponLevelList[$coupon['coupon_level']-1]['name'];
        if( $coupon['discount_type'] == 1 ){
            $coupon['discount_type_id'] = '折';
        }elseif( $coupon['discount_type'] == 2 ){
            $coupon['discount_type_id'] = tb_config('web_currency',1);
        }else{
            $coupon['discount_type_id'] = '';
        }
        // 提示
        if( !empty($coupon['goods']) ){
            $tisp = '仅指定商品可用';
        }elseif ( !empty($coupon['goods_category']) ){
            $tisp = '仅指定商品分类可用';
        }else{
            if( !empty($coupon['shop_id']) ){
                // 获取店铺名称
                $shopName = api('cust','Cust','getShopName',[$coupon['shop_id']]);
                $tisp = '仅 \''.$shopName.'\' 店铺内可用';
            }else{
                $tisp = '全平台可用';
            }
        }
        $coupon['tisp'] = $tisp;
        return $coupon;
    }


    public function _empty()
    {
        $this->redirect('index');
    }

}