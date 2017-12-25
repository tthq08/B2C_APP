<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 前台用户中心模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\member\mobile;

use app\member\home\Homebase;
use think\Db;

class Index extends Homebase
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        if( empty(session('user.id')) ){
            // $this->redirect('member/login/index');
            $this->error('尚未登录，请登录后再试','member/login/index');
        }
        //================购物车信息
        //获取用户购物车信息
        $cartList = Db::name('shop_cart')->alias('c')->join(config('prefix').'shop_goods g','c.goods =g.id')->field('c.id,c.goods_name,c.goods,g.thumb')->where('c.user_id',session('user.id'))->where('c.status',1)->select();
        //获取购物车商品数量
        $cartGoodsSum = Db::name('shop_cart')->where('user_id',session('user.id'))->where('status',1)->sum('goods_num');
        //获取购物车总价格
        $cartGoodsPrice = Db::name('shop_cart')->where('user_id',session('user.id'))->where('status',1)->sum('shop_price');
        //================购物车信息
        $this->assign('cartGoodsSum',$cartGoodsSum);
        $this->assign('cartGoodsPrice',priceFormat($cartGoodsPrice));
        $this->assign('cartList',$cartList);
        $this->assign('user',session('user'));
    }

    /**
     * 用户中心
     * @return mixed
     */
    public function index(){
        $user_id = session('user.id');

        // 更新用户的等级信息
        geiv_exp($user_id,0);

        // $user = Db::name('users')->where('id',session('user.id'))->find();
        // $points = $user['pay_points'];
        // $lv = Db::name('user_level') ->where('points','<',$points) ->order('id DESC') ->find();
        // // dump($lv);
        // Db::name('users')->where('id',session('user.id')) ->setField('level',$lv['id']);

        // 有效的优惠券数量(未使用、状态正常的有效期内的优惠券)
        $useable_coupon = Db::name('user_coupon')->where(['is_use'=>0,'user'=>$user_id,'status'=>1])->where('end_time','>= time',time()) ->count();
        $this->assign('useable_coupon',$useable_coupon);

        // 获取各个订单状态数
        $order1 = Db::name('shop_order')->where(['status'=>1,'user_id'=>$user_id]) ->count();
        $order3 = Db::name('shop_order')->where(['status'=>2,'user_id'=>$user_id]) ->whereOr('status',3) ->count();
        $order4 = Db::name('shop_order')->where(['status'=>4,'user_id'=>$user_id]) ->count();
        $order5 = Db::name('shop_order')->where(['status'=>5,'user_id'=>$user_id]) ->count();
        $this->assign('order1',$order1);
        $this->assign('order3',$order3);
        $this->assign('order4',$order4);
        $this->assign('order5',$order5);

        //查询用户的所有订单
        $orderList = Db::name('shop_order')->where('user_id',$user_id)->where('status>=0')->order('add_time desc') ->limit(3) ->select();
        $orderArr = [];
        foreach ($orderList as $key => $order) {
            //获取当前订单下的商品
            //检测是否是拼团订单
            if( $order['pieces_id'] > 0 ){
                $goods_id = Db::name('shop_pieces_order')->where('id',$order['pieces_id'])->field('goods,spec')->find();
                //获取信息
                $orderGoods = Db::name('shop_goods')->where('id',$goods_id)->field('title as goods_name,g.thumb')->select();
                $orderGoods['id'] = $order['id'];
            }else{
                $orderGoods = Db::name('shop_order_goods')->alias('og')->join(config('prefix').'shop_goods g','g.id = og.goods_id')->field('og.id,og.goods_id,og.goods_num,og.spec_key,g.title as goods_name,g.thumb')->where('order_id',$order['id'])->select();
            }

            $orderArr[$order['id']] = $order;
            $orderArr[$order['id']]['goods_list'] = $orderGoods;
        }
        $this->assign('orderList',$orderArr);
        return $this->fetch();
    }


    /**
     * 用户订单列表
     */
    public function order_list(){
        $_GET['type'] = !empty(request()->param('type')) ? request()->param('type') : '';
        $status = $_GET['type'];
        //获取用户所有订单列表
        $where = '';
        if( $status !== '' ){
            $where = '`status`='.$status;
        }
        $orderList = Db::name('shop_order')->where($where)->where('user_id',session('user.id'))->select();
        $orderArr = [];
        foreach ($orderList as $order){
            //获取当前订单下的商品
            $orderGoods = Db::name('shop_order_goods')->alias('og')->join(config('prefix').'shop_goods g','g.id = og.goods_id')->field('og.id,og.goods_id,g.title as goods_name,g.thumb')->where('order_id',$order['id'])->select();
            $orderArr[$order['id']] = $order;
            $orderArr[$order['id']]['goods_list'] = $orderGoods;
        }
        $this->assign('orderList',$orderArr);
        return $this->fetch();
    }

    /**
     * 用户基本信息页面
     */
    public function info(){

        return $this->fetch();
    }
    /**
     * 保存用户基本信息
     */
    public function save_info(){
        if( request()->isPost() ){
            $postData = request()->post();
            //插入数据
            $insertData = [];
            if( isset($postData['head_pic']) && $postData['head_pic'] !== '' ){
                $insertData['head_pic'] = $postData['head_pic'];
            }
            if ( isset($postData['nickname']) && $postData['nickname'] !== '' ){
                $insertData['nickname'] = $postData['nickname'];
            }
            if ( isset($postData['qq']) && $postData['qq'] !== '' ){
                $insertData['qq'] = $postData['qq'];
            }
            if ( isset($postData['birthday']) && $postData['birthday'] !== '' ){
                $insertData['birthday'] = $postData['birthday'];
            }
            if ( isset($postData['sex']) && $postData['sex'] !== '' ){
                $insertData['sex'] = $postData['sex'];
            }
            if( count($insertData) > 0 ){
                $insert = Db::name('users')->where('id',session('user.id'))->update($postData);
                if( $insert === false ){
                    $this->error('保存失败');
                }
                //更新session
                $user = session('user');
                $user = array_merge($user,$insertData);
                session('user',$user);
                $this->success('保存成功');
            }
            $this->error('数据错误');
        }else{
            $this->error('');
        }
    }

    /**
     * 用户地址管理
     */
    public function address_list(){
        //获取用户收货地址
        $addressList = Db::name('user_address')->where('user_id',session('user.id'))->where('status',1)->select();
        $this->assign('lists',$addressList);
        return $this->fetch();
    }

    /**
     * 用户地址添加
     */
    public function add_address(){
        return $this->fetch();
    }
    /**
     * 用户地址编辑
     */
    public function edit_address(){
        dump(request()->param());die;
        return $this->fetch();
    }

    /**
     * 用户地址保存
     */
    public function save_address(){

    }

    /**
     * 用户收货地址设为默认
     */
    public function set_default(){
        //修改收货地址为默认
        $id = request()->param('id');
        intval($id);
        if($id > 0){
            Db::startTrans();
            try{
                Db::name('user_address')->where('user_id',session('user.id'))->update(['is_default'=>0]);
                Db::name('user_address')->where('id',$id)->where('user_id',session('user.id'))->update(['is_default'=>1]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('设置成功');
        }else{
            $this->error('收货地址选择错误');
        }
    }

    /**
     * 删除用户收货地址
     */
    public function del_address(){
//修改收货地址为默认
        $id = request()->param('id');
        intval($id);
        if($id > 0){
            $del = Db::name('user_address')->where('id',$id)->where('user_id',session('user.id'))->update(['status'=>0]);
            if( $del === false ){
                $this->error('收货地址删除失败');
            }
            $this->success('收货地址删除成功');
        }else{
            $this->error('收货地址选择错误');
        }
    }

    /**
     * 我的评价页面
     */
    public function comment(){

    }

    /**
     * 我的优惠券页面
     */
    public function coupon(){

    }

    /**
     * 我的积分页面
     */
    public function account(){

    }

    /**
     * 我的收藏页面
     */
    public function goods_collect(){

    }

    /**
     * 申请提现页面
     */
    public function withdrawals(){

    }

    /**
     * 安全设置
     */
    public function safety_settings(){

    }

    /**
     * 余额页面
     */
    public function recharge(){

    }

    /**
     * 退货管理
     */
    public function return_goods_list(){

    }

    /**
     * 佣金管理
     */
    public function rebate_list(){

    }

    /**
     * 我的推广
     */
    public function spread_list(){

    }

    // 用户签到
    public function ajax_sign()
    {
        if( empty(session('user.id')) ){
            $this->error('尚未登录，请登录后再试');
        }
        $user_id = session('user.id');
        $sign_last = Db::name('user_sign') ->where('user_id',$user_id) ->order(['id'=>'DESC']) ->find();
        $sign_day = $sign_last['sign_time'];
        // 取上次签到日期
        $sign_day = date("Y-m-d",strtotime($sign_day));
        // 昨天日期
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        // 今天日期
        $today = date('Y-m-d');

        // 如果最后一次签到日期是今天，则表明已经签到过，无须再次签到
        if ($sign_day==$today) {
            return ['code'=>-1,'msg'=>'已签到','data'=>$sign_last];
        }
        // 如果上次签到时间是昨天，则连续签到天数+1，否则连续签到天数重新计算
        if ($sign_day==$yesterday) {
            $sign_days = $sign_last['days']+1;
        }else{
            $sign_days = 1;
        }

        $signData = [
            'user_id' => $user_id,
            'sign_time' => date('Y-m-d H:i:s'),
            'sign_date' => date('Y-m-d'),
            'days' => $sign_days,
        ];
        $res = Db::name('user_sign') ->insert($signData);
        $signData['dayth'] = date('d');
        //添加用户积分
        Db::name('users') ->where('id',session('user.id')) ->setInc('pay_points',10);
        if ($res!==false) {
            return ['code'=>1,'msg'=>'签到成功','data'=>$signData];
        } else {
            return ['code'=>-1,'msg'=>'签到失败，请重试'];
        }
        
    }

}