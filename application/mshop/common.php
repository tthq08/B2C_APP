<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 商城模块通用函数库
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
use think\Db;

// 获取猜你喜欢商品列表
// @param $limit 获取条数
function getLoveGoods($limit='10')
{
    $user_id = session('user.id');
    $goods = [];
    if (empty($user_id)) {      //如果用户未登录，则取商城端口销量最高的
        $goods = Db::name('shop_goods') ->order(['sales_sum'=>'DESC']) ->limit($limit) ->select();
    } else {
        // 取用户已购买过的商品
        $orders = Db::name('shop_order') ->field('GROUP_CONCAT(id) as orders') ->where('user_id',$user_id) ->cache(true) ->find();
        $order_goods = Db::name('shop_order_goods') ->field('GROUP_CONCAT(goods_id) as goods') ->where('order_id','IN',$orders['orders']) ->cache(true) ->find();
        // 取用户购物车中的商品
        $cart_goods = Db::name('shop_cart') ->field('GROUP_CONCAT(goods) as goods') ->where('user_id',$user_id) ->cache(true) ->find();
        // 取用户收藏的商品
        $favor_goods = Db::name('shop_goods_collect') ->field('GROUP_CONCAT(goods_id) as goods') ->where('user_id',$user_id) ->cache(true) ->find();
        // 拼接用户以上所有商品的ID
        $goods_ids = implode(',', [$order_goods['goods'],$cart_goods['goods'],$favor_goods['goods']]);
        $goods_ids = array_unique(explode(',', $goods_ids));
        // 获得用户上面所有商品的分类ID
        $cate_ids = Db::name('shop_goods') ->field('GROUP_CONCAT(cat_id) as cats') ->where('id','IN',$goods_ids) ->cache(true) ->find();
        $cate_ids = array_unique(explode(',', $cate_ids['cats']));
        $goods = Db::name('shop_goods') ->where('cat_id','IN',$cate_ids) ->order(['sales_sum'=>'DESC']) ->limit($limit) ->select();
    }
    return $goods;
}

// 根据订单ID计算该订单需要的邮费
// @param $key  数据标记 （来源为0时标记为订单ID：order_id;来源为1时标记为购物车用户ID：user_id;来源为2时标记为session_id）
// @param $table  数据来源 0：订单，1:购物车,2:直接购买 ，默认为0
// @param $RMB 是否返回RMB金额
function getOrderPostage($key,$table=0,$RMB=1)
{
    switch ($table) {
        case '0':
            $ordGoods = Db::name('shop_order_goods') ->where(['order_id'=>$key]) ->select();
            break;
        
        case '1':
            $ordGoods = Db::name('shop_cart') ->field('*,goods as goods_id')->where(['user_id'=>$key,'status'=>1,'selected'=>1])->select();
            break;

        case '2':
            $ordGoods = Db::name('shop_gobuy') ->field('*,goods as goods_id')->where(['session_id'=>$key,'status'=>1,'selected'=>1])->select();            
            break;
    }
    
    $postage = 0;
    $total_weight = 0;
    $shop_id = 0;
    foreach ($ordGoods as $key => $goods) {
        $info = Db::name('shop_goods') ->find($goods['goods_id']);
        $shop_id = $info['shop_id'];
        if ($info['is_free_shipping']==1) {     //该商品包邮
            $postage += 0;
        }else{
            if ($info['shipping_type']!=0) {    //商品邮费需要另行计算时
                $postage += getGoodsCost($goods['goods_id'],$goods['goods_num'],1);
            }else{      //累计计算所有标准运费模式的商品总重
                $total_weight += $info['weight']*$goods['goods_num'];
            }
        }
    }

    if ($shop_id == 0) {       //平台订单
        $shipping_ruller = [
            'first_weight' => '1000',
            'next_weight' => '1000',
            'first_mny' => tb_config('plat_deliver_first_amount',1),
            'next_mny' => tb_config('plat_deilver_next_amount',1),
        ];
    }else{
        // 商家订单
        $shopInfo = Db::name('cust_shop') ->field('id,shipping_method,shipping_ruller') ->where('id',$shop_id) ->find();
        if ($shopInfo['shipping_method']==0) {
            $shipping_ruller   = [
                'first_weight' => '1000',
                'next_weight' => '1000',
                'first_mny' => tb_config('plat_deliver_first_amount',1),
                'next_mny' => tb_config('plat_deilver_next_amount',1),
            ];
        }else{
            $shipping_ruller = json_decode($shopInfo['shipping_ruller'],true);
        }
    }

    if ($total_weight<=$shipping_ruller['first_weight']) {      //商品总重不超过计费首重，基础运费为计费首价
        $postage += $shipping_ruller['first_mny'];
    }else{
        // 待计算的商品重量为扣除首重后的剩余重量
        $weight_count = $total_weight - $shipping_ruller['first_weight'];
        $weight_count = ceil($weight_count/1000);
        $cost_count = $weight_count*$shipping_ruller['next_mny'];
        $base_cost = $shipping_ruller['first_mny']+$cost_count;
        $postage += $base_cost;
    };

    return $postage;
}

// 根据商品ID和数量计算邮费
// @param $goods_id  商品ID
// @param $goods_num 购买商品的数量
// @param $RMB 是否返回RMB金额
function getGoodsCost($goods_id,$goods_num,$RMB=0)
{
    $goodsInfo = Db::name('shop_goods') ->find($goods_id);
    $cost = 0;
    if ($goodsInfo['is_free_shipping']==1) {        //商品包邮，邮费为0
        $cost = 0;
    }else{
        $total_weight = $goodsInfo['weight']*$goods_num; //商品总重
        $shipping_ruller = json_decode($goodsInfo['shipping_ruller'],true);
        // 计算出商品的基础邮费
        if ($total_weight<=$shipping_ruller['first_weight']) {      //商品总重不超过计费首重，基础运费为计费首价
            $base_cost = $shipping_ruller['first_mny'];
        }else{
            // 待计算的商品重量为扣除首重后的剩余重量
            $weight_count = $total_weight - $shipping_ruller['first_weight'];
            $weight_count = ceil($weight_count/1000);
            $cost_count = $weight_count*$shipping_ruller['next_mny'];
            $base_cost = $shipping_ruller['first_mny']+$cost_count;
        };

        // 根据商品的邮费设置计算出实际邮费
        switch ($goodsInfo['shipping_type']) {
            case '0':
                $cost = $base_cost;
                break;
            
            case '1':
                // 商品数量在限购数量以内，邮费为设定的邮费
                if ($goods_num<=$shipping_ruller['only_nums']) {
                    $cost = $shipping_ruller['only_mny'];
                }else{
                    $cost = "超出限购数量";
                }
                break;

            case '2':
                $promo = $shipping_ruller['promo'];
                $promos = explode(',', $promo);
                foreach ($promos as $key => $pro) {
                    $prom = explode('|', $pro);
                    $cost_pro[$prom[0]] = $prom[1];
                }
                arsort($cost_pro);
                $now_cost = $base_cost;
                foreach ($cost_pro as $k => $val) {
                    if ($goods_num>=$k) {
                        $now_cost = $base_cost-$val;
                        break;
                    }else{
                        continue;
                    }
                }
                $cost = $now_cost;
                break;
        }    
    }
    return $cost;
}

// 根据商品ID和数量计算应缴关税
// @param $goods_id  商品ID
// @param $spec_price  商品实价
// @param $goods_num 购买商品的数量
function countGoodsTariff($goods_id,$spec_price,$goods_num=1)
{
    $goods = Db::name('shop_goods') ->where('id',$goods_id) ->find();
    $total_amount = $spec_price;    //关税必须以商品的售价进行计算
    $tariff = ($total_amount*$goods['tariff'])/100;
    $tariff = $tariff*$goods_num;
    return $tariff;
}

/**
 * 置顶函数
 * 计算商品价格
 * @param int $goods 商品ID
 * @param int $spec 商品规格ID
 * @param int $num 购买的数量
 * @param int $coupon 优惠券ID
 * @return decimal
 * 商品价格计算流程
 * !!!!! 该函数会将所有传进去的数量统一计算，如果有限购商品或者最小购买量，请在该函数调用前进行判断，该函数不做任何限购及最小购买量判断。
 *      一、使用promGoodsPrice计算促销之后的价格
 *
 */
function goodsPrice($goods,$num,$coupon,$spec='')
{
    //获取商品的原始价格
    $shop_price = getTableValue('shop_goods','id='.$goods,'shop_price');
    //检测这个商品，以及这个规格是否参加促销
    $prom = Db::name('shop_promotion')->where('FIND_IN_SET('.$goods.',goods)')->field('prom_id,p_type,p_id,start_time,end_time')->find();
    if( $prom !== '' ){
        //商品已经参加了促销
        //检测这个商品的活动状态
        $nowDate = date('Y-m-d H:i:s',time());
        if( $prom['start_time'] <= $nowDate || $prom['end_time'] >= $nowDate ){
            //计算出单个商品当前的价格
            $newPrice = compute()->promGoodsPrice($goods,$spec,false,$num);
        }
    }else{
        //这个商品没有参加活动,原始价格
        $newPrice = $shop_price*$num;
    }
    //开始计算优惠券优惠的商品金额，获取传入的优惠券的信息
    $newPrice = compute()->couponPrice($newPrice,$coupon);
    //开始计算邮费
    $postage = compute()->postage($goods);
    $newPrice = $newPrice-$postage;
    //开始计算经过订单优惠后的金额
    $newPrice = compute()->orderPrice($newPrice);

    return $newPrice;
}


/**
 * 写入交易消息
 * @param int $order_id
 * @param string $title
 * @param string $message
 */
function sendOrderMessage($order_id,$title,$message){
    //获取用户ID
    $user_id = getTableValue('shop_order','id='.$order_id,'user_id');
    $savaData['user_id'] = $user_id;
    $savaData['order_id'] = intval($order_id);
    $savaData['title'] = htmlspecialchars($title);
    $savaData['message'] = htmlspecialchars($message);
    //插入操作
    $insert = Db::name('shop_order_message')->insert($savaData);
    if( $insert === false ){
        return false;
    }
    return true;
}


/**
 * 实例化促销api
 * @param $model
 */
function promotion($model = ''){
    $promotionApi  = new \app\shop\api\Promotion($model);
    return $promotionApi;
}

/**
 * 实例化订单api
 */
function order(){
    $orderApi = new \app\shop\api\Order();
    return $orderApi;
}

/**
 * 支付成功修改支付状态
 * @param string $serial_sn 支付流水号
 * @param string $trade_no 支付宝交易号
 */
use app\shop\api\Pieces;
function update_pay_status($serial_sn,$trade_no){
    //检测 $serial_sn 是否是拼团订单
    if( substr($serial_sn,0,2) == 'pt' ){
        $save = Pieces::pieces_order_success($serial_sn,$trade_no);
    }else{
        $save = order_success($serial_sn,$trade_no);
    }

    if( $save === true ){
        return true;
    }else{
        return false;
    }
}

/**
 * 异步回调信息处理
 * @param string $serial_sn 支付流水号
 * @param string $$trade_no 支付交易号
 */
function order_success($serial_sn,$trade_no){
    //检测当前支付流水号是否正确
    try{
        $order_pay = Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->find();
    }catch (\Exception $e){
        return false;
    }
    if( $order_pay ){
        if ($order_pay['is_pay']==0) {
            //修改所有订单状态
            Db::startTrans();
            try{
                //user_points($order_pay['user_id'],$order_pay['order']); //修改为确认收货后到账
                user_money_change($serial_sn);
                order_success($order_pay['order'],$trade_no);
                give_coupon($order_pay['user_id'],$order_pay['order']);
                order_status($order_pay['order'],$trade_no);
                order_pay_status($serial_sn,$trade_no);
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                //记录进重要日志
                //return $this->error($exception->getMessage());
                return false;
            }
        }
        return true;
    }else{
        //记录进重要日志
        return false;
    }
}

/**
 * 添加记录到订单日志表
 * @param int $order_id 订单ID
 * @param int $action_user 操作管理员ID
 * @param int $order_status 订单状态
 * @param int $shipping_status 物流状态
 * @param int $pay_status 支付状态
 * @param int $action_note 操作备注
 * @param int $status_desc 状态描述
 * @return boolean
 */
function insert_order_action($order_id,$action_user,$order_status,$action_note,$status_desc = ''){
    //设置保存信息
    $actionData['order_id'] = $order_id;
    $actionData['action_user'] = $action_user;
    $actionData['order_status'] = $order_status;
    $actionData['shipping_status'] = getTableValue('shop_order',['id'=>$order_id],'is_send');
    $actionData['pay_status'] = intval(getTableValue('shop_order',['id'=>$order_id],'is_pay'));
    $actionData['action_note'] = $action_note;
    $actionData['status_desc'] = $status_desc;
    $actionData['log_time'] = date('Y-m-d H:i:s',time());

    Db::name('shop_order_action')->insert($actionData);
}


/**
 * 修改用户积分
 */
function user_points($user,$order){
    //修改用户积分
    $orderArr = explode(',',$order);
    $points = 0;
    foreach ( $orderArr as $order ){
        //查看当前订单的优惠方式是什么
        /**
         * 1、打折
         * 2、金额
         * 3、积分
         * 4、优惠券
         * 5、免邮
         */
        //查询商品的赠送积分
        $goodsList = Db::name('shop_order_goods')->where('order_id',$order)->field('goods_id,prom_id')->select();
        foreach( $goodsList as $goodsA ){
            $goods = Db::name('shop_goods')->where('id',$goodsA['goods_id'])->field('give_score,title')->find();
            $points += $goods['give_score'];
            //积分领取记录
            $points_receive['user_id'] = $user;
            $points_receive['num'] = $goods['give_score'];
            $points_receive['remark'] = '购买商品：'.$goods['title'].',赠送积分。';
            $points_receive['add_time'] = date('Y-m-d H:i:s',time());
            Db::name('shop_points_receive')->insert();
            //查看商品中是否存在促销ID
            if( $goodsA['prom_id'] > 0 ){
                //获取促销信息
                $prom = Db::name('shop_promotion')->where('prom_id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                if( $prom['p_type'] == 3 || $prom['type'] == 4){
                    $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                    if($promInfo['discount_type'] == 3){
                        $points += $promInfo['expression'];
                        //积分领取记录
                        $points_receive['user_id'] = $user;
                        $points_receive['num'] = $promInfo['expression'];
                        $points_receive['remark'] = '参加优惠促销：'.$promInfo['title'].',赠送积分。';
                        $points_receive['add_time'] = date('Y-m-d H:i:s',time());
                        Db::name('shop_points_receive')->insert();
                    }
                }
            }
        }
        //查询订单信息
        $order_prom_id = Db::name('shop_order')->where('id',$order)->value('order_prom_id');
        if($order_prom_id == ''){
            break;
        }
        //获取订单优惠类型
        $order_prom_info = Db::name('shop_promotion_order')->where('prom_id > 0')->where('order_id',$order_prom_id)->field('title,type,expression')->find();
        if( $order_prom_info == 3 ){
            //赠送积分
            $points += $order_prom_info['expression'];
            //积分领取记录
            $points_receive['user_id'] = $user;
            $points_receive['num'] = $order_prom_info['expression'];
            $points_receive['remark'] = '订单优惠：'.$order_prom_info['title'].',赠送积分。';
            $points_receive['add_time'] = date('Y-m-d H:i:s',time());
            Db::name('shop_points_receive')->insert();
        }
    }
    //修改用户积分
    Db::name('users')->where('id',$user)->setInc('pay_points',$points);
}

/**
 * 处理余额变动
 */
function user_money_change($serial_sn){
    //获取用户信息
    $serialInfo = Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->field('user_id,payable_price,postage,change_mny,balance_price,order')->find();
    $order = explode(',',$serialInfo['order']);
    $order_num = count($order);
    $money = $serialInfo['payable_price']+$serialInfo['postage']-$serialInfo['change_mny']+$serialInfo['balance_price'];
    // 向会员发送消息通知
    if (!empty($serialInfo['user_id'])) {
        sendMsg(1,$serialInfo['user_id'],'user_pay_ok',['order_sn'=>$serial_sn]);
        //修改金额
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('total_amount',$money);

        // 计算用户支付经验
        $experience = $serialInfo['payable_price']*tb_config('exp_pay_percent',1);
        geiv_exp($serialInfo['user_id'],$experience,'pay');

        //修改用户余额
        //Db::name('users')->where('id',$serialInfo['user_id'])->setDec('user_money',$money);
        //修改最近购买量
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('new_order',$order_num);
        //修改总购买量
        Db::name('users')->where('id',$serialInfo['user_id'])->setInc('total_order',$order_num);
    }else{
        // 直接下单用户
        $order = Db::name('shop_order') ->where('id','IN',$serialInfo['order']) ->find();
        $mobile = $order['phone'];
        sendMsg(0,$mobile,'user_pay_ok',['order_sn'=>$serial_sn],'82');
    }

}

// 用户增加经验
function geiv_exp($uid,$exp,$type='')
{
    // 添加用户的支付经验
    Db::name('users')->where('id',$uid)->setInc('experience',$exp);
    // 获取用户当前等级及当前经验值
    $user = Db::name('users') ->field('level,experience') ->where('id',$uid)->find();
    // 更新用户的等级信息
    $experience = $user['experience'];
    // 取得当前经验时用户应得的等级
    $lv = Db::name('user_level') ->where('points','<',$experience) ->order('id DESC') ->find();
    
    if ($lv>$user['level']) {       //如果应得等级高于用户当前等级，则更新用户等级，并发放奖励
        $res = Db::name('users')->where('id',session('user.id')) ->setField('level',$lv['id']);
        if($res!==false){
            // 向用户发放升级奖励积分
            Db::name('users') ->where('id',session('user.id')) ->setField('points',$lv['lv_give_points']);
        }
    }
}

/**
 * 赠送用户代金券
 */
function give_coupon($user,$order){
//修改用户积分
    $orderArr = explode(',',$order);
    foreach ( $orderArr as $order ){
        //查看当前订单的优惠方式是什么
        /**
         * 1、打折
         * 2、金额
         * 3、积分
         * 4、优惠券
         * 5、免邮
         */
        //查询商品的赠送积分
        $goodsList = Db::name('shop_order_goods')->where('prom_id > 0')->where('order_id',$order)->field('goods_id,prom_id')->select();
        foreach( $goodsList as $goodsA ){

            //获取促销信息
            $prom = Db::name('shop_promotion')->where('prom_id',$goodsA['prom_id'])->field('p_type,p_id')->find();
            if( $prom['p_type'] == 3 || $prom['type'] == 4){
                $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                if($promInfo['discount_type'] == 4){
                    //优惠券发放记录
                    api('shop','CouponSend','send_coupon',[$user,$promInfo['expression']]);
                }
            }
        }
        //查询订单信息
        $order_prom_id = Db::name('shop_order')->where('id',$order)->value('order_prom_id');
        if($order_prom_id == ''){
            break;
        }
        //获取订单优惠类型
        $order_prom_info = Db::name('shop_promotion_order')->where('order_id',$order_prom_id)->field('title,type,expression')->find();
        if( $order_prom_info == 4 ){
            //赠送优惠券
            api('shop','CouponSend','send_coupon',[$user,$promInfo['expression']]);
        }
    }
}

/**
 * 修改订单状态
 */
function order_status($order,$trade_no){
    $orderInfo = Db::name('shop_order')->where('id',$order)->field('change_mny,payable_price,postage')->find();
    $pay_money = $orderInfo['payable_price']-$orderInfo['change_mny'];
    Db::name('shop_order')->where('id in('.$order.')')->update(['is_pay'=>1,'pay_time'=>date('Y-m-d H:i:s',time()),'status'=>2,'pay_sn'=>$trade_no,'pay_money'=>$pay_money]);
    $orders = explode(',', $order);
    foreach ($orders as $key => $ord) {
        $user_id = Db::name('shop_order') ->where('id',$ord) ->value('user_id');
        insert_order_action($ord,$user_id,2,'订单支付','用户付款成功');
    }
}

/**
 * 修改订单流水状态
 */
function order_pay_status($serial_sn,$trade_no){
    Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->update(['is_pay'=>1,'pay_time'=>date('Y-m-d H:i:s',time()),'pay_sn'=>$trade_no]);
}

/**
 * 添加支付记录
 */
function payment_record(){
    //添加支付记录
}


/**
 * 减少库存
 */
function reduce_stock($order){
    //获取流水内的所有商品
    $order_goods_arr = Db::name('shop_order_goods')->where('order_id in('.$order.')')->field('id,goods_id,spec_key,order_id,goods_num')->select();
    foreach ($order_goods_arr as $item){
        //修改库存
        //使用文件锁，锁定商品，防止高并发下库存错误
        $lock_file = getGoodsLockFile($item['order_id']);
        $fp = fopen($lock_file,"w+");
        if( flock($fp , LOCK_EX) ){
            if( !empty($item['spec_key']) ){
                $stock = Db::name('shop_spec_price')->where(['goods_id'=>$item['goods_id'],'key_sign'=>$item['spec_key']]) ->value('store_count');
                if ($stock<$item['goods_num']) {
                    throw new Exception("库存不足", 1);

                }
                Db::name('shop_spec_price')->where(['goods_id'=>$item['goods_id'],'key_sign'=>$item['spec_key']])->setDec('store_count',$item['goods_num']);
            }else{
                $stock = Db::name('shop_goods')->where(['id'=>$item['goods_id']]) ->value('stock');
                if ($stock<$item['goods_num']) {
                    throw new Exception("库存不足", 1);
                    
                    // return ['code'=>0,'msg'=>'库存不足'];
                }
                Db::name('shop_goods')->where('id',$item['goods_id'])->setDec('stock',$item['goods_num']);
            }
            flock($fp , LOCK_UN);
        }
        fclose($fp);
    }
}


/**
 * 恢复库存
 */
function restore_stock($order){
    //获取流水内的所有商品
    $order_goods_arr = Db::name('shop_order_goods')->where('order_id in('.$order.')')->field('id,goods_id,spec_key,order_id,goods_num')->select();
    foreach ($order_goods_arr as $item){
        //修改库存
        //使用文件锁，锁定商品，防止高并发下库存错误
        $lock_file = getGoodsLockFile($item['order_id']);
        $fp = fopen($lock_file,"w+");
        if( flock($fp , LOCK_EX) ){
        if( !empty($item['spec_key']) ){
            Db::name('shop_spec_price')->where(['goods_id'=>$item['goods_id'],'key_sign'=>$item['spec_key']])->setInc('store_count',$item['goods_num']);
        }else{
            Db::name('shop_goods')->where('id',$item['goods_id'])->setInc('stock',$item['goods_num']);
        }
            flock($fp , LOCK_UN);
        }
        fclose($fp);
    }
}

/**
 * 获取商品锁文件路径名称
 * @param int $goods_id 商品ID
 */
function getGoodsLockFile($goods_id){
    $file  = ROOT_PATH . 'public/lock/shop/goods/'.$goods_id.'.lock';
    return $file;
}


/**
 * 实例化价格计算类
 */
function compute(){
    $cou = 'app\shop\api\Compute';
    $compute = new $cou();
    return $compute;
}

/**
 * 获取抢购促销详细信息
 * @param int $id 抢购促销ID
 * @return array
 */
function getPanicInfo($id)
{
    $field = 'panic_id,title,description,min_buy_num,max_buy_num,use_coupon,use_integral,use_shopping_coupon,user_group,add_shopping_car,add_user_integral,buy_num,end_time,start_time,status';
    //获取信息
    $panicInfo = Db::name('shop_promotion_panic')->where('panic_id',$id)->field($field)->find();
    //获取商品信息
    $prom_id = getPromID(1,$panicInfo['panic_id']);
    $panicGoods = Db::name('shop_promotion_goods')->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->find();
    $panicInfo['goods'] = $panicGoods['goods_id'];
    //解析goods_spec
    if( $panicGoods['goods_spec'] !== '' ){
        $panicInfo['goods_spec'] = unserialize($panicGoods['goods_spec']);
    }else{
        $panicInfo['goods_spec'] = '';
    }
    $panicInfo['price'] = $panicGoods['price'];
    return $panicInfo;
}

/**
 * 获取团购促销详细信息
 * @param int $id 团购促销ID
 * @return array
 */
function getGroupInfo($id)
{
    $field = 'group_id,title,description,group_num,virtual_buy_num,min_buy_num,max_buy_num,use_coupon,use_integral,use_shopping_coupon,user_group,add_shopping_car,add_user_integral,buy_num,end_time,start_time,status';
    $groupInfo = Db::name('shop_promotion_group')->where('group_id',$id)->where('status',1)->field($field)->find();

    //获取商品信息
    $prom_id = getPromID(2,$groupInfo['group_id']);
    $groupGoods = Db::name('shop_promotion_goods')->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->find();
    $groupInfo['goods'] = $groupGoods['goods_id'];
    //解析goods_spec
    if( $groupGoods['goods_spec'] !== '' ){
        $groupInfo['goods_spec'] = unserialize($groupGoods['goods_spec']);
    }else{
        $groupInfo['goods_spec'] = '';
    }
    $groupInfo['price'] = $groupGoods['price'];
    return $groupInfo;
}

/**
 * 获取优惠促销详细信息
 * @param int $id 优惠促销ID
 * @return array
 */
function getDiscountInfo($id,$goods = '')
{
    $field = 'discount_id,title,description,discount_type,money,expression,min_buy_num,max_buy_num,use_coupon,use_integral,use_shopping_coupon,user_group,add_shopping_car,add_user_integral,buy_num,end_time,start_time,status';
    $discountInfo = Db::name('shop_promotion_discount')->where('discount_id',$id)->where('status',1)->field($field)->find();

    //获取商品信息
    if( $goods == '' ){
        $where = 'goods_id = '.$goods;
    }else{
        $where = '1=1';
    }
    $prom_id = getPromID(3,$discountInfo['discount_id']);
    $discountGoods = Db::name('shop_promotion_goods')->where($where)->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->select();
    if( $goods == '' ){
        foreach ($discountGoods as $goods){
            $discountInfo['goods'][]['goods'] = $goods['goods_id'];
            $discountInfo['goods'][]['goods_spec'] = explode(',',$goods['goods_spec']);
            $discountInfo['goods'][]['price'] = $goods['price'];
        }
    }else{
        $discountInfo['goods'] = $goods['goods_id'];
        $discountInfo['goods'] = $goods['goods_id'];
        $discountInfo['goods'] = $goods['goods_id'];
    }

    return $discountInfo;

}

/**
 * 获取订单促销详细信息
 * @param int $id 订单促销ID
 * @return array
 */
function getOrderInfo($id)
{
    $field = 'order_id,title,description,discount_type,money,expression,use_coupon,use_integral,use_shopping_coupon,user_group,buy_num,end_time,start_time,status';
    $orderInfo = Db::name('shop_promotion_order')->where('order_id',$id)->where('status',1)->field($field)->find();

    return $orderInfo;
}

/**
 * 检测商品是否已经收藏
 * @param int $goods_id 商品ID
 * @param int $user_id 用户ID
 */
function getGoodsCollectStatus($goods_id,$user_id){
    $goods = intval($goods_id);
    $user_id = intval($user_id);
    //查询
    $goodsCollectId = Db::name('shop_goods_collect')->where(['goods_id'=>$goods,'user_id'=>$user_id,'status'=>1])->value('id');
    if( $goodsCollectId != '' ){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取订单状态名称
 * @param int $status 订单状态
 * @return array
 */
function getOrderStatusName($status)
{
    $config = config('order_status');

    return $config[$status];
}


/**
* 获取指定分类ID下的指定数量的文章 
*
* @param int $cid  分类ID
* @param int $nums  输出文章数量
*/ 
function getArticleList($cid,$nums=1)
{
    $now = time();
    $art_list = Db::name('web_content') ->where(['cid'=>$cid,'trash'=>0]) ->limit($nums) ->select();
    return $art_list;
}

/**
 * 优惠券类加载函数
 * @return
 */
function coupon(){
    $coupon = new app\shop\api\Coupon();
    return $coupon;
}

/**
 * 获取总表ID
 * @param $type 促销类型：1-抢购、2-团购、3-优惠、4-订单
 * @param $id 促销表ID 具体促销表的ID
 * @return int
 */
function getPromID($type,$id)
{
    $prom_id = Db::name('shop_promotion')->where(['p_type'=>$type,'p_id'=>$id])->value('prom_id');
    if( $prom_id == '' ) {
        return lang('Promotion_is_not_exist');
    }
    return $prom_id;
}

/**
 * 获取促销表名称
 * @param int $prom_id 促销总表ID
 */
function getPromTable($prom_id){
    $prom_type = getTableValue('shop_promotion','id='.$prom_id,'p_type');
    switch ($prom_type){
        case 1:
            return 'shop_promotion_panic';
            break;
        case 2:
            return 'shop_promotion_group';
            break;
        case 3:
            return 'shop_promotion_discount';
            break;
        case 4:
            return 'shop_promotion_order';
            break;
    }
}


/**
 * 添加用户分成信息
 * @param int $order_id
 * @return bool
 */
function saveUserDistribution($order_id){
    //获取订单信息
    $orderInfo  = Db::name('shop_order')->where('id',$order_id)->field('user_id,payable_price,change_mny')->find();
    //获取分成配置
    $disConfig = getUserDistributionConfig();
    if( !empty($disConfig) ){
        if( $disConfig ){
            //获取用户上级别
            $user_r = $orderInfo['user_id'];
            $userTree = Db::name('users_tree')->where('uid',$user_r)->value('tree_path_id');
            $userTree = explode(',',$userTree);
            $userTree = array_reverse($userTree);
            //设置保存数据
            $saveData['user_r'] = $user_r;
            $saveData['order_id'] = $order_id;
            $saveData['order_price'] = $orderInfo['payable_price']-$orderInfo['change_mny'];
            $saveData['add_time'] = date('Y-m-d H:i:s');
            $saveData['divided_into_time'] = date('Y-m-d H:i:s',strtotime('+2 days'));
            //查看分成级别
            foreach ($disConfig['level_proportion'] as $k=>$v){
                //$k=>级别，$v=>分成比例
                if( !empty($userTree[$k]) ){
                    $user_id = $userTree[$k];
                    $saveData['user_id'] = $user_id;
                    //计算分成金额
                    $saveData['divided_into_price'] = ($orderInfo['payable_price']-$orderInfo['change_mny'])*($v/100);
                    Db::name('user_distribution')->insert($saveData);
                }
            }
        }
    }
}


/**
 * 获取订单内的商品
 * @param int $id 订单ID
 * @return array
 */
function getOrderGoods($id){
    //获取
    $orderGoodsArr = \think\Db::name('shop_order_goods')->alias('og')->join(config('prefix')."shop_goods g",'og.goods_id = g.id')->field('g.id,g.title as goods_name,g.thumb')->where('order_id',$id)->select();
    $orderGoods = '';
    foreach ($orderGoodsArr as $goods){
        $orderGoods[$goods['id']] = $goods;
        $orderGoods[$goods['id']]['url'] = U('mshop/Goods/goods',array('id'=>$goods['id']));
    }
    return $orderGoods;
}

/**
 * 获取楼层广告列表
 * @param int $cid  分类ID
 * @param int $pid  广告位置ID
 * @param int $nums  输出广告数量
 * @return mixed
 */
function getFloorAd($cid,$pid,$nums=1,$is_phone=1)
{
    $ad_list = Db::name('goods_categoryimg') ->where(['cid'=>$cid,'position'=>$pid,'is_show'=>1,'is_phone'=>$is_phone]) ->limit($nums) ->select();
    return $ad_list;
}

/**
* 获取指定广告位ID下的指定数量的广告 
*
* @param int $pid  广告位ID
* @param int $nums  输出广告数量
* @param int $id  单个广告id
*/ 
function getAdLists($pid,$nums=null,$id=null)
{
    $now = time();
    if( (empty($id)) || (empty($nums))){
        $ad_list = Db::name('admin_ad') ->where(['pid'=>$pid,'isdspy'=>1]) ->where("start_time<=$now AND end_time>=$now") ->select();
        return $ad_list;
    }else{
        $ad_list = Db::name('admin_ad') ->where(['pid'=>$pid,'isdspy'=>1,'id'=>$id]) ->where("start_time<=$now AND end_time>=$now") ->limit($nums) ->select();
        return $ad_list;
    }

}

/**
* 
* 后台按照自定义分类id的广告位 添加广告 只要是显示 就输出 所有在后台自行选择隐藏或显示该广告
* @param  $attach  自定义 分类id
* @param int $id  广告id  如果空输出所有
*/ 
function getAdList2($attach)
{
    // select * from tb_admin_ad as a inner join tb_admin_ad_position as p on a.pid=p.id where p.attach='all'
    $now = time();
    $tag = 'a.ad_name,a.ad_title,a.ad_link,a.ad_pic,a.ad_video,a.target';
    $sql = 'select '.$tag.' from tb_admin_ad as a inner join tb_admin_ad_position as p on a.pid=p.id where (a.start_time<='.$now.' AND a.end_time>='.$now.') AND a.isdspy=1 AND p.attach='."'$attach'";
    $ad_list = Db::query($sql);
    return $ad_list;
}


/**
 * 多个数组的笛卡尔积
*
* @param unknown_type $data
*/ 
function combineDika($data) {  
    $cnt = count($data);  
    $result = array();  
    foreach($data[0] as $item) {  
        $result[] = array($item);  
    }  
    for($i = 1; $i < $cnt; $i++) {  
        $result = combineArray($result,$data[$i]);  
    }  
    return $result;  
}  
   
/** 
 * 两个数组的笛卡尔积 
 * 
 * @param unknown_type $arr1 
 * @param unknown_type $arr2 
 */  
function combineArray($arr1,$arr2) {  
    $result = array();  
    foreach ($arr1 as $item1) {  
        foreach ($arr2 as $item2) {  
            $temp = $item1;  
            $temp[] = $item2;  
            $result[] = $temp;  
        }  
    }  
    return $result;  
}  

/**
 * 根据分类ID 获取整个分类分支
 * @param string $cat_id 分类id
 * @return string
 */
function getCateLine($cat_id)
{
    $cate[] = $cat_id;
    $pid = Db::name('goods_category') ->where('id',$cat_id) ->value('pid');
    if ($pid!=0) {
        $new_pid = getCateLine($pid);
        array_unshift($cate, $new_pid);
    }else{
        array_unshift($cate, $pid);
    }
    return implode('_', $cate);
}

// 根据分类ID 获取分类名称
function getCateName($cat_id)
{
    $cateName = Db::name('goods_category') ->where('id','IN',$cat_id) ->column('name','id');
    $cateNames = implode(' / ', $cateName);
    return $cateNames;
} 

/**
 * 创建订单号
 * @return string
 */
function createOrderSn()
{
    //当前加上8为随机数
    $date = date("ymdhis",time());
    $date = $date.rand(12345678,87654321);
    return $date;
}

/**
 * 创建流水号
 * @return string
 */
function createSerialSn()
{
    //当前加上8为随机数
    $date = date("ymdhis",time());
    $date = 'biz'.$date.rand(1234,8765);
    return $date;
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id,$width,$height){

     if(empty($goods_id))
         return '';
    //判断缩略图是否存在
    $path = "./uploads/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}";
  
    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg'; 
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg'; 
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif'; 
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png'; 
        
    $thumb = Db::name('shop_goods')->where("id",$goods_id)->value('thumb');
    if(empty($thumb)) return '';
    
    $thumb = '.'.$thumb; // 相对路径
    if(!file_exists($thumb)) return '';

    $image = \think\Image::open($thumb);
        
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    //生成缩略图
    if(!is_dir($path)){
        mkdir($path);
        chmod($path,0777);
    }
    
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    
    return '/'.$path.$goods_thumb_name;
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img,$goods_id,$width,$height){
    //判断缩略图是否存在
    $path = "./uploads/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";
    // 如果相册数据中有商品规格参数,则图片路径需要加上规格数据
    if (isset($sub_img['spec_key'])) {
        $goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$sub_img['spec_key']}_{$width}_{$height}";
    }
    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
    
    $original_img = '.'.$sub_img['image_url']; //相对路径
    if(!file_exists($original_img)) return '';
    
    // $image = new \Think\Image();
    // $image->open($original_img);
    $image = \think\Image::open($original_img);
    
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    // 生成缩略图
    if(!is_dir($path)){
        mkdir($path);
        chmod($path,0777);
    }
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    return '/'.$path.$goods_thumb_name;
}

function getGoodsCommentNum($goods_id)
{
    $nums = Db::name('shop_goods_comment') ->where(['goods_id'=>$goods_id]) ->count();
    return $nums;
}