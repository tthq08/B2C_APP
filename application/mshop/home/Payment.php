<?php
namespace app\mshop\home;
use plugins\payment\wapalipay\WapPay;
use think\Db;
use think\Log;
use think\Request;
class Payment extends HomeBase
{
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();

        //检测订单是否已经完成
        if( input('serial_id/d') ){
            $is_pay = Db::name('shop_order_pay')->where('serial_sn',input('serial_id/d'))->value('is_pay');
            if( $is_pay == 1 ){
                return $this->error('订单已完成，无需重复支付');
            }
        }
        
        // tpshop 订单支付提交
        $pay_radio = request()->param('pay_radio');
        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);    
            $this->pay_code = request()->param('pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        //$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if(empty($this->pay_code)){
            if ( strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ){
                $this->pay_code = 'weixin';
            }else{
                exit('pay_code 不能为空');                
            }            
        }
        // 导入具体的支付类文件
        include  EXTEND_PATH . "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php";
        $code = '\\'.$this->pay_code; // \alipay
        if(!class_exists($this->pay_code)){
            echo '支付插件不存在';
            //$this->error('支付方式错误，请更换支付方式');
        }
        $this->payment = new $code();
    }
   
    /**
     * junshop 提交支付方式
     */
    public function getCode(){

        //C('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        $payment_arr = getPayList();
        // dump($payment_arr);die;
        $serial_id = empty(input('serial_id/d')) ? 0 : input('serial_id/d'); // 订单id

        if( $serial_id > 0 ){
            session('serial_id',$serial_id); // 最近支付的一笔订单 id
            $order = Db::name('shop_order_pay')->where("id", $serial_id)->find();
            // 查看订单是否属于当前用户
            if( $order['user_id'] != session('user.id') ){
                $this->error('这个订单不是你的');
            }
            //查看是否已经支付
            if($order['is_pay'] == 1){
                $this->error('此订单，已完成支付!');
            }
            //修改订单支付方式
            Db::name('shop_order_pay')->where("id", $serial_id)->update(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]['name']));
        }else{
            $pieces_id = empty(input('pieces_id/d')) ? 0 : input('pieces_id/d');
            if( $pieces_id > 0 ){
                $order = Db::name('shop_pieces_order')->where("id", $pieces_id)->find();
                //查看订单是否属于当前用户
                if( $order['user_id'] != session('user.id') ){
                    $this->error('这个订单不是你的');
                }
                //查看是否已经支付
                if($order['is_pay'] == 1){
                    $this->error('此订单，已完成支付!');
                }
                // 修改订单的支付方式
                Db::name('shop_pieces_order')->where("id", $pieces_id)->update(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]['name']));
            }else{
                return $this->error('订单错误了,请重新提交');
            }
        }
        Db::name('shop_order') ->where('id','IN',$order['order']) ->setField('pay_code',$this->pay_code);
        // tpshop 订单支付提交
        $pay_radio = request()->param('pay_radio');
        $pay_method = request()->param('pay_method');
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        if (!empty($pay_method)) {
            $config_value['pay_method'] = $pay_method;
        }
        $order['money'] = $order['payable_price']+$order['postage']-$order['change_mny'];

        // 微信JS支付
        if( !empty($order['pieces_sn']) ){
            $order['serial_sn'] = $order['pieces_sn'];
        }
        $wechat = cookie('wechat');
        // dump($order);die;
       if($this->pay_code == 'weixin' && isset($wechat['openid']) && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
           $code_str = $this->payment->getJSAPI($order,$config_value);
           exit($code_str);
       }else{
       	    $code_str = $this->payment->get_code($order,$config_value);
           if( $this->pay_code == 'weixin' && !empty($code_str['return_code']) && $code_str['return_code'] == 'FAIL' ){
               $this->error('该支付方式暂时不可用,请更换支付方式,或与客服联系。');
           }
       }
       $this->assign('code_str', $code_str);
       $this->assign('serial_id', $serial_id);

       return $this->fetch('payment');  // 分跳转 和不 跳转 
    }
    
    // 服务器点对点 // http://www.tp-shop.cn/index.php/Home/Payment/notifyUrl        
    public function notifyUrl(){
        $this->payment->response();
        exit();
    }

    // 页面跳转 // http://www.tp-shop.cn/index.php/Home/Payment/returnUrl        
    public function returnUrl(){
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        
        if(stripos($result['order_sn'],'recharge') !== false)
        {
            $order = Db::name('recharge')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                return $this->fetch('recharge_success');   
            else
                return $this->fetch('recharge_error');   
            exit();            
        }
                
        $order = Db::name('order')->where("order_sn", $result['order_sn'])->find();
        if(empty($order)) // order_sn 找不到 根据 order_id 去找
        {
            $order_id = session('order_id'); // 最近支付的一笔订单 id        
            $order = Db::name('order')->where("order_id", $order_id)->find();
        }
                
        $this->assign('order', $order);
        if($result['status'] == 1)
            return $this->fetch('success');   
        else
            return $this->fetch('error');   
    }

    /**
     * 异步回调信息处理
     * @param string $serial_sn 支付流水号
     * @param string $$trade_no 支付交易号
     */
    public function order_success($serial_sn,$trade_no){
        //检测当前支付流水号是否正确
        try{
            $order_pay = Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->find();
        }catch (\Exception $e){
            Log::error($e);
        }

        if( $order_pay ){
            //修改所有订单状态
            Db::startTrans();
            try{
                $this->user_points($order_pay['user_id'],$order_pay['order']);
                $this->order_success($order_pay['order'],$trade_no);
                $this->order_status($order_pay['order'],$trade_no);
                $this->order_pay_status($serial_sn,$trade_no);
                $this->reduce_stock($order_pay['order']);
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                //记录进重要日志
                Log::error(json_encode($exception));
                return false;
            }
            return true;
        }else{
            //记录进重要日志
            Log::error('订单错误了');
            return false;
        }
    }

    /**
     * 修改用户积分
     */
    protected function user_points($user,$order){
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
                    $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
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
     * 赠送用户代金券
     */
    protected function give_coupon($user,$order){
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
                $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                if( $prom['p_type'] == 3 || $prom['type'] == 4){
                    $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                    if($promInfo['discount_type'] == 4){
                        //优惠券发放记录
                        coupon()->send_coupon($user,$promInfo['expression']);
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
                coupon()->send_coupon($user,$order_prom_info['expression']);
            }
        }
    }

    /**
     * 修改订单状态，支付类型
     */
    protected function order_status($order,$trade_no){

        Db::name('shop_order')->where('id in('.$order.')')->update(['is_pay'=>1,'status'=>2,'pay_sn'=>$trade_no,'pay_code'=>$this->pay_code]);
    }

    /**
     * 修改订单流水状态
     */
    protected function order_pay_status($serial_sn,$trade_no){
        Db::name('shop_order_pay')->where('serial_sn',$serial_sn)->update(['is_pay'=>1,'pay_time'=>date('Y-m-d H:i:s',time()),'pay_sn'=>$trade_no]);
    }

    /**
     * 添加支付记录
     */
    protected function payment_record(){
        //添加支付记录
    }


    /**
     * 处理余额变动
     */

    /**
     * 减少库存
     */
    protected function reduce_stock($order){
        //获取流水内的所有商品
        $order_goods_arr = Db::name('shop_order_goods')->where('order_id in('.$order.')')->field('id,order_id,goods,goods_num')->select();
        foreach ($order_goods_arr as $item){
            //修改库存
            //使用文件锁，锁定商品，防止高并发下库存错误
            //$lock_file = getGoodsLockFile($item['goods']);
            //$fp = fopen($lock_file,"w");
            //if( flock($fp , LOCK_EX) ){
            if( $item['spec_id'] > 0 ){
                Db::name('shop_spec_price')->where('id',$item['spec_id'])->setDec('store_count',$item['goods_num']);
            }else{
                Db::name('shop_goods')->where('id',$item['goods'])->setDec('stock',$item['goods_num']);
            }
                //flock($fp , LOCK_UN);
           // }
            //fclose($fp);
        }
    }

}
