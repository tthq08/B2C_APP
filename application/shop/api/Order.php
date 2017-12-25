<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 订单管理api
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\api;

use think\Db;
use think\Log;

class Order
{
    protected $_order_DB;
    protected $_order_pay_DB;
    protected $_order_goods_DB;
    protected $_order_action_DB;

    public function __construct()
    {
        $this->_order_DB = Db::name('shop_order');
        $this->_order_pay_DB = Db::name('shop_order_pay');
        $this->_order_goods_DB = Db::name('shop_order_goods');
        $this->_order_action_DB = Db::name('shop_order_action');
    }

    /**
     * 查询订单信息
     * @param $order_id
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getOrderInfo($order_id,$field = ''){
        $orderInfo = Db::name('shop_order')->where('id',$order_id)->field($field)->find();
        return $orderInfo;
    }

    /**
     * 插入订单
     * @param $data
     * @param bool $get_id
     * @return bool|int|string
     */
    public function insertData($data,$get_id = true){
        if( $get_id == false ){
            $insert = $this->_order_DB->insert($data);
            if( $insert !== false ){
                return true;
            }
        }else{
            $insert = $this->_order_DB->insertGetId($data);
            if( $insert > 0 ){
                return $insert;
            }
        }
        return false;
    }

    /**
     * 插入多条数据
     * @param $allData
     * @return bool
     */
    public function insertDataAll($allData){
        $insert = $this->_order_DB->insertAll($allData);
        if( $insert !== false ){
            return true;
        }
        return false;
    }

    /**
     * 添加记录到订单日志表
     * @param int $order_id 订单ID
     * @param int $action_user 操作管理员ID
     * @param int $action_note 操作备注
     * @param int $status_desc 状态描述
     * @return boolean
     */
    public function insert_order_action($order_id,$action_user,$action_note,$status_desc = ''){

        $orderInfo = $this->getOrderInfo($order_id,'status,is_send,is_pay');
        //设置保存信息
        $actionData['order_id'] = $order_id;
        $actionData['action_user'] = $action_user;
        $actionData['order_status'] = $orderInfo['status'];
        $actionData['shipping_status'] = $orderInfo['is_send'];
        $actionData['pay_status'] = $orderInfo['is_pay'];
        $actionData['action_note'] = $action_note;
        $actionData['status_desc'] = $status_desc;
        $actionData['log_time'] = date('Y-m-d H:i:s', NOW_TIME);

        $d = Db::name('shop_order_action')->insert($actionData);
        return $d;
    }

    /**
     * 获取用户最近的一条交易消息
     * @param int $user_id
     * @param string $field
     * @return array|bool
     */
    public function getNextOrderMessage($user_id,$field = ''){
        $user_id = intval($user_id);
        if( $user_id != 0 ){
            //获取最近的一条消息
            $message = Db::name('shop_order_message')->where('status',0)->where('user_id',$user_id)->field($field)->find();
            if( $message == '' ){
                return '';
            }
            return $message;
        }else{
            return false;
        }
    }

    /**
     * 获取用户交易消息列表
     * @param int $user_id
     * @param string $field
     * @return bool/array
     */
    public function getOrderMessage($user_id,$field = ''){
        $user_id = intval($user_id);
        if( $user_id != 0 ){
            //获取最近的一条消息
            $message = Db::name('shop_order_message')->where('status > -1')->where('user_id',$user_id)->field($field)->select();
            if( $message == '' ){
                return '';
            }
            return $message;
        }else{
            return false;
        }
    }

    /**
     * 修改所有交易消息状态
     * @param int $user_id
     * @parme int $status
     * @return bool
     */
    public function setAllOrderMessageStatus($user_id,$status){
        $user_id = intval($user_id);
        if( $user_id != 0 ){
            //获取最近的一条消息
            $update = Db::name('shop_order_message')->where('user_id',$user_id)->update(['status'=>$status]);
            if( $update == '' ){
                return false;
            }
            return true;
        }else{
            return false;
        }
    }


    /**
     * 查询快递信息
     * @param int $id 订单ID
     * @return array
     */
    public function shippingInfo($id,$user_id)
    {
        $id = intval($id);
        $condition = [
            'id' => $id,
            'user_id' => $user_id,
        ];
        $shippingInfo = Db::name('shop_order')->where($condition)->field('shipping_code,shipping_sn')->find();
        return $shippingInfo;
    }

    /**
     * 获取订单内的所有商品
     * @param int $id
     * @return mixed
     */
    public function goodsList($id)
    {
        // 获取订单内商品
        $goodsList = Db::name('shop_order_goods')->where('order_id',$id)->select();
        // 循环查询每个商品，每个规格的库存，状态
        foreach ($goodsList as $key=>$goods)
        {
            // 查询库存
            if( $goodsList[$key]['spec_id'] > 0 ){
                // 查询规格库存
                $stock = Db::name('shop_spec_price')->where('id',$goods['spec_id'])->field('store_count,price')->find();
                $goodsList[$key]['stock'] = $stock['store_count'];
                $goodsList[$key]['goods_price'] = $stock['price'];
                $goodsList[$key]['market_price'] = getTableValue('shop_goods','id='.$goods['goods_id'],'stock');
                $goodsList[$key]['goods_status'] = getTableValue('shop_goods','id='.$goods['goods_id'],'status');
            }else{
                // 查询商品库存
                $goodsInfo = Db::name('shop_goods')->where('id',$goods['goods_id'])->field('shop_price,stock,market_price,status')->find();
                $goodsList[$key]['stock'] = $goodsInfo['stock'];
                $goodsList[$key]['goods_price'] = $goodsInfo['shop_price'];
                $goodsList[$key]['market_price'] = $goodsInfo['market_price'];
                $goodsList[$key]['goods_status'] = $goodsInfo['status'];
            }
        }
        // 返回结果
        return $goodsList;
    }

    /**
     * 计算订单可以获取的积分
     * @param int $id
     * @return int
     */
    public function order_points($id)
    {
        // 查询商品的赠送积分
        $points = 0;
        $goodsList = Db::name('shop_order_goods')->where('order_id',$id)->field('goods_id,prom_id')->select();
        foreach( $goodsList as $goodsA ){
            $goods = Db::name('shop_goods')->where('id',$goodsA['goods_id'])->field('give_score,title')->find();
            $points += $goods['give_score'];
            //查看商品中是否存在促销ID
            if( $goodsA['prom_id'] > 0 ){
                //获取促销信息
                $prom = Db::name('shop_promotion')->where('id',$goodsA['prom_id'])->field('p_type,p_id')->find();
                if( $prom['p_type'] == 3 || $prom['type'] == 4){
                    $promInfo = Db::name(getPromTable($goodsA['prom_id']))->where('id',$prom['p_id'])->field('title,discount_type,expression')->find();
                    if($promInfo['discount_type'] == 3){
                        $points += $promInfo['expression'];
                    }
                }
            }
        }
        //查询订单信息
        $order_prom_id = Db::name('shop_order')->where('id',$id)->value('order_prom_id');
        //获取订单优惠类型
        $order_prom_info = Db::name('shop_promotion_order')->where('order_id > 0')->where('order_id',$order_prom_id)->field('title,type,expression')->find();
        if( $order_prom_info == 3 ){
            //赠送积分
            $points += $order_prom_info['expression'];
        }
        return $points;
    }


    /**
     * 获取订单分成奖励列表
     * @param int $id
     * @return array
     */
    public function order_distribution($id)
    {
        $order_distribution = Db::name('user_distribution')->where('status',1)->where('order_id',$id)->select();
        return $order_distribution;
    }


    /**
     * 计算最新订单数量，前一天加上当天
     * @return int
     */
    public function getNewOrderNum()
    {
        // 计算前一天开始时间


    }


    /**
     * 计算总的销售额
     * @param string
     */
    public function getSalesVolume()
    {
        $price = Db::name('shop_order')->where('status >= 3')->sum('payable_price-change_mny');
        return $price;
    }


    /**
     * 获取订单所有日志
     * @param $id
     * @return array
     */
    public function action_list($id)
    {
        $list = Db::name('shop_order_action')->where('order_id',$id) ->order('log_time desc')->select();
        return $list;
    }



    /**
     * 获取订单状态名称
     * @param int $id
     * @return bool
     */
    public function getStatusName($id)
    {
        $status = config('order_status');
        if( !empty($status[$id]) ){
            return $status[$id];
        }
        return false;
    }

    /**
     * 获取不同订单状态数量
     * @param int $user_id
     * @param int $status
     * @return int
     */
    public function getOrderStatusNum($user_id,$status)
    {
        $count = 0;
        return $count;
    }


    /**
     * 获取订单日志
     * @param $order_id
     * @return array
     */
    public function orderLogList($order_id)
    {
        // 获取订单日志
        $where = [
            'order_id' => $order_id,
        ];
        $logList = Db::name('shop_order_action')->where($where)->select();
        return $logList;
    }


    /**
     * 获取订单内的商品列表
     * @param $order_id
     * @return array
     */
    public function orderGoodsList($order_id)
    {
        $where = [
            'order_id' => $order_id,
        ];
        $list = Db::name('shop_order_goods')->where($where)->select();
        return $list;
    }

    /**
     * 获取订单商品详细信息
     * @param $og_id
     * @return array
     */
    public function orderGoodsDetail($og_id)
    {
        // 获取基本信息
        $info = Db::name('shop_order_goods')->where('id',$og_id)->find();

        // 获取商品图片
        $info['thumb'] = getTableValue('shop_goods','id='.$info['goods_id'],'thumb');
        // 获取订单用户
        $info['user'] = getTableValue('shop_order','id='.$info['order_id'],'user_id');

        return $info;
    }


    /**
     * 标记订单
     * @param array $ids
     * @param int $consultor
     * @return boolean
     */
    public function signOrder($ids, $consultor)
    {
        // 修改
        try{
            $data = [
                'is_sign' => 1,
            ];
            $up = Db::name('shop_order')->where('id','in',$ids)->where('shop_id',$consultor)->update($data);
        }catch (\ErrorException $e)
        {
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }

    /**
     * 标记订单
     * @param array $ids
     * @param int $consultor
     * @return boolean
     */
    public function cancelSignOrder($ids, $consultor)
    {
        // 修改
        try{
            $data = [
                'is_sign' => 0,
            ];
            $up = Db::name('shop_order')->where('id','in',$ids)->where('shop_id',$consultor)->update($data);
        }catch (\ErrorException $e)
        {
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }


    /**
     * 保存退款信息
     * @param $data
     * @return mixed
     */
    public function saveReturnGoodsInfo($data)
    {
        Db::startTrans();
        try{
            // 保存退款信息
            $save = Db::name('shop_return_goods')->insertGetId($data);
            // 修改订单商品状态
            Db::name('shop_order_goods')->where('id',$data['order_goods_id'])->update(['is_return'=>1]);
            // 添加进订单日志
            $this->insert_order_action($data['order_id'],0,$data['order_status'],'用户申请退款!');
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            Log::error(json_encode($e));
            return false;
        }
        return $save;
    }


    /**
     * 订单发货
     * @param array $data
     * @param int $order_id
     * @return mixed
     */
    public function doDliverGoods($data, $order_id)
    {
        // 保存订单数据
        Db::startTrans();
        try{
            $up = Db::name('shop_order')->where('id',$order_id)->update($data);
            // 添加进订单日志
            $this->insert_order_action($order_id,0,$data['status'],'卖家发货!');
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }

    /**
     * 修改订单地址
     * @param array $data
     * @param int $order_id
     * @return mixed
     */
    public function saveAddress($data, $order_id)
    {
        // 保存订单数据
        Db::startTrans();
        try{
            $up = Db::name('shop_order')->where('id',$order_id)->update($data);
            // 添加进订单日志
            $this->insert_order_action($order_id,0,$data['status'],'修改订单地址!');
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }


    /**
     * 创建退款号
     * @return string
     */
    public function createAfterGoodsSn()
    {
        $str = 'SH';
        $rand = rand(100,999);
        $rand = from10_to36($rand);
        $rand .= date('ymdhis');
        $rand .= rand(100,999);
        return $str.$rand;
    }


    /**
     * 通过订单商品id获取退款信息
     * @param $order_goods
     * @return mixed
     */
    public function getReturnGoodsInfoFromOGid($order_goods)
    {
        $info = Db::name('shop_return_goods')->where('order_goods_id',$order_goods)->find();
        return $info;
    }

    /**
     * 获取退款信息
     * @param $id
     * @return mixed
     */
    public function getReturnGoodsInfo($id)
    {
        $info = Db::name('shop_return_goods')->where('id',$id)->find();
        return $info;
    }


    /**
     * 获取退款状态列表
     * @return array
     */
    public function returnGoodsStatus()
    {
        //退货
        $return_goods = [
            //'-2' => '退回买家',//未达到退货要求，退回买家
            //'-1' => '卖家拒绝',//卖家拒绝退货
            '0' => '用户取消',
            '1' => '待卖家处理',
            '2' => '待买家退货',
            '3' => '待卖家收货',
            '4' => '待卖家退款',
            '5' => '退货完成',
        ];
        return $return_goods;
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



}