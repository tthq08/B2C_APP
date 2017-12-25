<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/7/6
 * Time: 16:27
 */

namespace app\member\home;

use think\Db;
use think\Log;

class Order extends Homebase
{

    /**
     * 用户订单列表
     */
    public function index(){
        //获取用户所有订单列表
        $data = input();
        if (empty($data['type'])) {
            $data['type'] = 'all';
        }
        if( $data['type'] == 'all' ){
            $where = '`status` > 0';
            $this->assign('status','all');
        }else{
            $status = !empty($data['type']) ?  $data['type'] : 0;
            $status = intval($status);
            $this->assign('status',$status);
            if( $status >= 0 ){
                if ($status == 2) {
                    $where = "status = 2 OR status =3";
                }else{
                    $where = '`status` = '.$status;
                }
            }
        }

        if (!empty($data['days'])) {
            $start_date = date('Y-m-d',strtotime('-'.($data['days']-1).' days')).' 00:00:00';
            $where2['add_time'] = ['>= time',$start_date];
            $this->assign('days',$data['days']);
        }else{
            $this->assign('days','');
        }

        if (!empty($data['month'])) {
            $data['month'] = $data['month'] >=10 ? $data['month'] : '0'.$data['month'];
            $start_date = date('Y').'-'.$data['month'].'-'.'01'.' 00:00:00';
            $end_date = date('Y-m-d', strtotime("$start_date +1 month -1 day"))." 23:59:59";
            $where2['add_time'][] = ['>= time',$start_date];
            $where2['add_time'][] = ['<= time',$end_date];
            $this->assign('month',$data['month']);
        }else{
            $this->assign('month','');
        }

        if (!empty($data['time_start']) && !empty($data['time_end'])) {
            $start_date = $data['time_start'].' 00:00:00';
            $end_date = $data['time_end']." 23:59:59";
            $where2['add_time'][] = ['>= time',$start_date];
            $where2['add_time'][] = ['<= time',$end_date];
            $this->assign('time_start',$data['time_start']);
            $this->assign('time_end',$data['time_end']);
        }else{
            $this->assign('time_start','');
            $this->assign('time_end','');
        }


        if( !empty($data['key']) ){
            $order_ids = Db::name('shop_order_goods') ->field('GROUP_CONCAT(order_id) as ids') ->where('goods_name','LIKE',"%{$data['key']}%") ->whereOr('spec_title','LIKE',"%{$data['key']}%") ->find();

            $where .= ' and `order_sn` = \''.$data['key'] . '\'';
            if (!empty($order_ids['ids'])) {
                $where .= ' OR id IN('.$order_ids['ids'].')';
            }
        }
        // dump($where);
        $page_row = tb_config('list_rows',1);
        if (empty($where2)) {
            $orderList = Db::name('shop_order')->where($where) ->where('user_id',session('user.id'))->order('add_time desc')->paginate($page_row);
        } else {
            $orderList = Db::name('shop_order')->where($where) ->where($where2)->where('user_id',session('user.id'))->order('add_time desc')->paginate($page_row);
        }

        // dump(Db::name('shop_order')->getLastSql());
        $orderArr = [];
        foreach ($orderList->items() as $order){
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
        $this->assign('page',$orderList->render());
        $this->assign('orderList',$orderArr);
        return $this->fetch();
    }

    /**
     * 取消订单
     * @param int $id 订单ID
     */
    public function cancel($id){
        intval($id);
        if( empty($id) ){
            $id = intval(request()->param('id'));
            if(empty($id)){
                $this->error('请选择需要删除的订单,如果持续出现此消息，请联系客服');
            }
        }
        $points = getTableValue('shop_order','id='.$id,'points');
        Db::startTrans();
        try{
            // 修改用户积分
            api('member','Points','record',[session('user.id'),$points,10,getTableValue('shop_order','id='.$id,'order_sn')]);
            // 添加进订单日志
            api('shop','Order','insert_order_action',[$id,0,'用户取消订单!']);
            restore_stock($id);     //恢复订单所有商品的库存
            $update = Db::name('shop_order')->where(['id'=>$id,'user_id'=>session('user.id')])->update(['status'=>'0']);
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            Log::error($exception);
            return $this->error('取消失败');
        }
        if( $update === false ){
            $this->error(lang('cancel_order_failed'));
        }else{
            $this->success(lang('cancel_order_success'));
        }
    }


    /**
     * 删除订单
     * @param int $id 订单ID
     */
    public function del_order($id){
        intval($id);
        $update = Db::name('shop_order')->where(['id'=>$id,'user_id'=>session('user.id')])->update(['status'=>-1]);
        // 添加进订单日志
        api('shop','Order','insert_order_action',[$id,0,'用户删除订单!']);
        if( $update === false ){
            $this->error(lang('del_order_failed'));
        }else{
            $this->success(lang('del_order_success'));
        }
    }

    /**
     * 查询订单物流
     * @param int $id 订单ID
     * @return mixed
     */
    public function chaxun($id)
    {
        $id = intval($id);
        //查询快递编号和快递号
        $orderShipping = api('shop','Order','shippingInfo',array($id,session('user.id')));
        if( empty($orderShipping) ){
            $this->assign('shippingInfo',12345);
        }else{
            //查询物流跟踪信息
            $kdTrail = kdTrail($orderShipping['shipping_code'],$orderShipping['shipping_sn']);
            $Traces = $kdTrail['Traces'];
            $TracesLen = count($Traces);
            $Traces2 = [];
            foreach ($Traces as $k=>$v){
                $Traces2[$k] = $Traces[$TracesLen-$k-1];
            }
            $kdTrail['Traces'] = $Traces2;
            $this->assign('kdTrail',$kdTrail);
            $this->assign('shippingInfo',$orderShipping);
        }
        return $this->fetch();
    }

    /**
     * 订单详情页面
     */
    public function detail(){
        //获取订单信息
        $id = request()->param('id');
        $order_info = Db::name('shop_order')->where('id',$id)->where('status>=0')->where('user_id',session('user.id'))->find();
        if(  $order_info == ''){
            $this->redirect('shop/User/order_list');
        }
        $status_arr = config('order_status');
        $order_info['status_str'] = $status_arr[$order_info['status']];
        //获取订单中的所有商品
        $goodsList = Db::name('shop_order_goods')->where('order_id',$order_info['id'])->select();
        $order_info['goods_list'] = $goodsList;
        $this->assign('order_info',$order_info);

        // 获取订单日志
        $order_action = api('shop','Order','action_list',[$id]);
        $this->assign('order_action',$order_action);
        return $this->fetch();
    }

    /**
     * 用户确认收货
     */
    public function confirm(){
        //检测订单是否存在
        $id = intval(request()->param('id'));
        if( $id == 0 ){
            $this->error(lang('order_id_is_field'));
        }
        $orderInfo = Db::name('shop_order')->where('id',$id)->where('user_id',session('user.id'))->find();
        if( $orderInfo == '' ){
            $this->error(lang('cart_order_failed'));
        }
        if( $orderInfo['is_pay'] == 0 ){
            $this->error(lang('order_is_not_pay'));
        }
        if( $orderInfo['is_send'] == 0 ){
            $this->error(lang('order_is_not_send'));
        }
        if( $orderInfo['status'] == 5 ){
            $this->error(lang('order_goods_is_receive'));
        }
        //修改订单信息
        $updateData['status'] = 5;
        $updateData['receiving_time'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try{
            Db::name('shop_order')->where('id',$id)->update($updateData);
            // 添加进订单日志
            $action_note = '用户确认收货!';
            api('shop','Order','insert_order_action',[$orderInfo['id'],0,$action_note]);
            // 用户商品积分
            api('shop','Order','user_points',[session('user.id'),$orderInfo['order_sn']]);

            // 添加积分记录，修改用户积分
            api('member','Points','record',[session('user.id'),$orderInfo['points'],2,$orderInfo['order_sn']]);

            // 检测是否是新用户订单
            if( empty(session('user.new_user')) && session('user.new_user') == 1 ){
                // 检测是否有优惠券
                $coupon = api('shop','Coupon','getNewUserCoupon',$orderInfo['payable_price']-$orderInfo['change_mny']);
                if( !empty($coupon) ){
                    // 发送优惠券
                    api('shop','Coupon','send_coupon',[session('user.id'),$coupon['id']]);
                }
                Db::name('users')->where('id',session('user.id'))->update(['new_user'=>0]);
                session('user.new_user',1);
            }
            // 添加用户分成信息
            if ($orderInfo['is_ping']==1) {
                saveUserDistribution($id);
            }
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
        $this->success(lang('order_goods_is_receive_success'));
    }


    /**
     * 用户确认收货后跳转 交易成功
     */
    public function order_success($id){
        $this->assign('id',$id);
        $service_id = 33;
        $service_cate = Db::name('web_cate') ->where('pid',$service_id)->order(['sort'=>'DESC']) ->select();
        $this->assign('serv_cate',$service_cate);
        return $this->fetch();
    }


    /**
     * 我的评价页面
     */
    public function comment()
    {
        return $this->fetch();
    }

    /**
     * 添加评价
     */
    public function add_comment(){
        $id = request()->param('id');
        $id = intval($id);
        if( $id == 0 ){
            $this->error('订单商品错误');
        }
        $order_goods = Db::name('shop_order_goods')->where('id',$id)->field('id,goods_id,spec_id,spec_key,spec_title,is_comment')->find();
        if( $order_goods['is_comment'] == 1 ){
            $this->error('该商品已经评论，无需重复评论','mshop/user/order_list');
        }
        $goodsInfo = Db::name('shop_goods')->where('id',$order_goods['goods_id'])->field('thumb,title')->find();
        $spec_title = $order_goods['spec_title'] ? $order_goods['spec_title'] : '';
        $this->assign('spec_title',$spec_title);
        $this->assign('goodsInfo',$goodsInfo);
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * 保存商品评价
     */
    public function save_comment(){
        $postData = request()->post();
        if( empty($postData['id']) || intval($postData['id']) == 0 ){
            $this->error('订单商品错误');
        }

        if( empty($postData['description_match']) || intval($postData['description_match']) == 0 || $postData['description_match'] > 5 ){
            echo $postData['description_match'];
            $this->error('请对描述相符打分');
        }elseif( empty($postData['shipping_service']) || intval($postData['shipping_service']) == 0 || $postData['shipping_service'] > 5 ){
            $this->error('请对物流服务打分');
        }elseif( empty($postData['service_attitude']) || intval($postData['service_attitude']) == 0 || $postData['service_attitude'] > 5 ){
            $this->error('请对服务态度打分');
        }elseif( empty($postData['commentG']) || intval($postData['commentG']) == 0 || $postData['commentG'] > 3 ){
            $this->error('请给商品一个评价');
        }

        //获取订单商品是否正确
        $orderGoods = Db::name('shop_order_goods')->where('user_id',session('user.id'))->where('id',$postData['id'])->field('order_id,goods_id,goods_num,spec_id,spec_key,spec_title,shop_id,goods_num,is_comment')->find();
        if( empty($orderGoods) ){
            $this->error('订单商品出错了');
        }
        if( $orderGoods['is_comment'] == 1  ){
            $this->error('您已经评价了该订单');
        }
        //获取商品用户
        $user_id = getTableValue('shop_order','id='.$orderGoods['order_id'],'user_id');
        if( $user_id != session('user.id') ){
            $this->error('不属于您的订单商品');
        }
        //获取用户信息
        $userInfo = Db::name('users')->where('id',$user_id)->field('email,nickname')->find();

        //插入数据赋值
        $saveData['goods_id'] = $orderGoods['goods_id'];
        $saveData['shop_id'] = $orderGoods['shop_id'];
        $saveData['email'] = $userInfo['email'];
        $saveData['username'] = $userInfo['nickname'];
        $saveData['ip_address'] = get_client_ip();
        $saveData['order_id'] = $orderGoods['order_id'];
        $saveData['goods_rank'] = $postData['commentG'];
        $saveData['description_rank'] = $postData['description_match'];
        $saveData['deliver_rank'] = $postData['shipping_service'];
        $saveData['service_rank'] = $postData['service_attitude'];
        $saveData['content'] = empty($postData['content']) ? '' : $postData['content'];
        $saveData['user_id'] = session('user.id');

        if( !empty($postData['uupimg']) ){
            $postData['uupimg'] = rtrim($postData['uupimg'],',');
            $postData['uupimg'] = explode(',',$postData['uupimg']);
        }
        $saveData['img'] = json_encode($postData['uupimg']);
        $saveData['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
        //插入操作
        Db::startTrans();
        try{
            Db::name('shop_goods_comment')->insert($saveData);
            // 更新
            Db::name('shop_order_goods')->where('id',$postData['id'])->update(['is_comment'=>1]);
            // 检测该订单的商品是否已经评价
            $is_comment  = Db::name('shop_order_goods')->where('order_id',$orderGoods['order_id'])->where('is_comment',0)->count();

            // 添加进订单日志
            api('shop','Order','insert_order_action',[$orderGoods['order_id'],0,'用户评论订单商品:#'.$postData['id'].'#']);

            if( $is_comment <= 1 ){
                Db::name('shop_order')->where('id',$orderGoods['order_id'])->update(['is_comment'=>1,'status'=>6]);
            }
            // 更新商品评价数量缓存
            if( !empty(cache('goodsCommentNum:'.$orderGoods['goods_id'])) ){
                $num = cache('goodsCommentNum:'.$orderGoods['goods_id']) + 1;
                cache('goodsCommentNum:'.$orderGoods['goods_id'],$num,'','shop-goods_comment');
            }
            Db::commit();
        }catch (\Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }
        $this->success('评价成功','mshop/user/order_list');
    }


    /**
     * 检测订单中的商品是否全部存在
     * @return mixed
     */
    public function check_order_goods(){
        $order_id = empty(request()->param('id')) ? 0 : request()->param('id');
        $order_id = intval($order_id);
        //获取商品
        $field = 'og.goods_id,og.goods_num,og.spec_id,og.prom_id';
        $order_goods = Db::name('shop_order_goods')->alias('og')->join('shop_order o','og.order_id=o.id')->where(['og.order_id'=>$order_id,'o.user_id'=>session('user.id')])->field($field)->select();
        //不可购买的商品
        $cant_buy = [];
        foreach ($order_goods as  $goods){
            //检测商品数量，商品状态
            $goodsInfo = Db::name('shop_goods')->where('id',$goods['goods_id'])->field('title,status,stock')->find();
            if( $goodsInfo['status'] == 0 ){
                if( $goods['spec_id'] > 0 ){
                    //获取规格库存数量
                    $spec_stock = getTableValue('shop_spec_price','id='.$goods['spec_id'],'store_count');
                    if ($spec_stock < $goods['goods_num']){
                        $cant_buy[$goods['goods_id']] = $goodsInfo['title'].":商品已经下架或者库存不足\n";
                    }
                }else{
                    if( $goodsInfo['stock'] < $goods['goods_num'] ){
                        $cant_buy[$goods['goods_id']] = $goodsInfo['title'].":商品已经下架或者库存不足\n";
                    }
                }
                //查看活动是否加入促销活动
                if( !empty($goods['prom_id']) || $goods['prom_id'] >0 ){
                    //检测促销活动是否可用
                    $checkProm = promotion()->checkProm($goodsInfo['prom_id']);
                    if( $checkProm == false ){
                        $cant_buy[$goods['goods_id']] = $goodsInfo['title'].":商品促销活动已经结束，您仍可购买该商品，但是不可享受促销\n";
                    }
                }
            }
        }
        if( count($cant_buy) > 0 ){
            $data = implode(',',$cant_buy);
            $data .= '是否继续购买';
            $this->error($data);
        }else{
            $this->buy_again($order_id);
        }
    }

    public function buy_again($order_id){
        $order_id = empty(request()->param('id')) ? $order_id : request()->param('id');
        $order_id = intval($order_id);
        //获取商品
        $field = 'og.goods_id,og.goods_num,og.spec_id,og.prom_id,og.spec_title';
        $order_goods = Db::name('shop_order_goods')->alias('og')->join('shop_order o','og.order_id=o.id')->where(['og.order_id'=>$order_id,'o.user_id'=>session('user.id')])->field($field)->select();
        foreach ($order_goods as $goods){
            //检测商品数量，商品状态
            $goodsInfo = Db::name('shop_goods')->where('id',$goods['goods_id'])->field('title,status,stock,shop_price')->find();
            if( $goodsInfo['status'] != 0 ){
                if( $goods['spec_id'] > 0 ){
                    //获取规格库存数量
                    $spec = Db::name('shop_spec_price')->where('id='.$goods['spec_id'])->field('key_sign,key_name,price')->find();
                    $spec_stock = getTableValue('shop_spec_price','id='.$goods['spec_id'],'store_count');
                    if( empty($spec_stock) )
                    {
                        $info = getTableValue('shop_goods','id='.$goods['goods_id'],'title')."【".$goods['spec_title']."】:商品已经下架或者库存不足\n";
                        $this->error($info);
                    }
                    if ($spec_stock >= $goods['goods_num']){
                        $cartData['spec_id'] = $goods['spec_id'];
                        $cartData['spec_key'] = $spec['key_sign'];
                        $cartData['spec_text'] = $spec['key_name'];
                        $cartData['shop_price'] = $spec['price'];
                    }
                }else{
                    $cartData['shop_price'] = $goodsInfo['shop_price'];
                }

                //加入购物车
                $goodsData = Db::name('shop_goods')->where('id',$goods['goods_id'])->field('cid,shop_id,id,title,market_price')->find();
                //检测购物车中是否存在该商品
                $is_exist = getTableValue('shop_cart','`user_id`='.session('user.id').' and `goods` = '.$goodsData['id'] .' and `status`>0','goods_num');
                if( $is_exist == '' ) {
                    $cartData['cid'] = $goodsData['cid'];
                    $cartData['shop_id'] = $goodsData['shop_id'];
                    $cartData['user_id'] = session('user.id');
                    $cartData['goods'] = $goodsData['id'];
                    $cartData['goods_name'] = $goodsData['title'];
                    $cartData['market_price'] = $goodsData['market_price'];
                    $cartData['goods_num'] = $goods['goods_num'];
                    $cartData['selected'] = 1;
                    //查看活动是否加入促销活动

                    if (!empty($goods['prom_id'])) {
                        //检测促销活动是否可用
                        $checkProm = promotion()->checkProm($goods['prom_id']);
                        if ($checkProm == true) {
                            $cartData['prom_id'] = $goods['prom_id'];
                        }
                    }
                    $cartData['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
                }
                //插入操作
                try{
                    if( !empty($is_exist) ){
                        if( $is_exist < $goods['goods_num'] ){
                            Db::name('shop_cart')->where('user_id='.session('user.id').' and goods = '.$goodsData['id'])->update(['goods_num'=>$goods['goods_num']]);
                        }
                    }else{
                        Db::name('shop_cart')->insert($cartData);
                    }
                }catch (\Exception $exception){
                    $this->error($exception->getMessage());
                }

            }
        }
        $this->success();
    }

}