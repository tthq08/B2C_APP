<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 拼团管理类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\api;

use app\shop\model\ShopPiecesGroup as PiecesModel;
use think\Db;
use think\Log;
use app\member\api\User;

class Pieces
{
    public static $DB;

    public function __construct($is_model = false)
    {

    }


    private static function vModel($type = false,$model = '')
    {
        if( $type == false && $model == '' && empty(self::$DB) ){
            self::$DB = new PiecesModel();
        }else{
            if( $model == '' ){
                self::$DB = Db::name('shop_pieces_group');
            }else{
                self::$DB = Db::name($model);
            }
        }
        return self::$DB;
    }

    /**
     * 获取拼团列表
     * @param string $field 获取的字段
     * @return array
     */
    public static function lists($field = '',$page_row = 20)
    {
        $lists = Pieces::vModel()->field($field)->paginate($page_row);
        return $lists;
    }

    /**
     * 获取拼团信息
     * @param int $id
     * @param string $field
     * @return array
     */
    public static function get($id,$field = '')
    {
        $id = intval($id);
        try{
            $info = Db::name('shop_pieces_group')->field($field)->where('id',$id)->find();
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/get:'.$exception->getMessage());
            return $exception->getMessage();
        }
        if( !empty($field) ){
            if( count(explode(',',$field)) == 1 ){
                $info = $info[$field];
            }
        }
        return json_decode(json_encode($info),true);
    }

    public static function delete($id)
    {
        try{
            Pieces::vModel()->where('id in('.$id.')')->delete();
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/delete:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return true;
    }

    /**
     * 保存拼团信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function save($id,$postData)
    {
        $id = intval($id);
        $data['title'] = trim($postData['title']);
        $data['single_num'] = intval($postData['single_num']);
        $data['user_group'] = $postData['user_group'];
        $data['start_time'] = $postData['start_time'];
        $data['end_time'] = $postData['end_time'];
        $data['price'] = $postData['price'];
        $data['goods'] = $postData['goods'];
        $goods_spec = empty($postData['goodsspec']) ? '' : $postData['goodsspec'];

        $spec_json = htmlspecialchars_decode($goods_spec);
        if( !empty($spec_json) ){
            $spec_arr = json_decode($spec_json,true);
            $data['goods_spec'] = implode(',',array_keys($spec_arr));
            $spec_serialize = serialize($spec_arr);
            $data['goods_spec_price'] = $spec_serialize;
        }
        $data['description'] = $postData['description'];
        try{
            $save = Pieces::vModel()->where('id',$id)->update($data);
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/save:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return true;

    }

    /**
     * 添加拼团信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function add($postData)
    {
        $data['title'] = trim($postData['title']);
        $data['single_num'] = intval($postData['single_num']);
        $data['start_time'] = $postData['start_time'];
        $data['user_group'] = $postData['user_group'];
        $data['end_time'] = $postData['end_time'];
        $data['price'] = $postData['price'];
        $data['goods'] = $postData['goods'];
        $goods_spec = empty($postData['goodsspec']) ? '' : $postData['goodsspec'];
        if( !empty($goods_spec) ){
            $data['goods_spec'] = implode(',',array_keys(json_decode(htmlspecialchars_decode($goods_spec),true)));
            $data['goods_spec_price'] = self::spec_serialize($goods_spec);
        }
        $data['description'] = $postData['description'];
        try{
            $save = Pieces::vModel()->insert($data);
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/add:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return true;
    }

    /**
     * 获取当前用户的订单
     * @param int $user_id
     * @param int $pieces_id
     * @param sting $field
     * @return array
     */
    public static function getUserOrder($user_id,$pieces_id,$field = '')
    {
        //检测用户订单
        $condition = [
            'user_id' => $user_id,
            'pieces_id' => $pieces_id,
            'status' => 1,
        ];
        $data = Pieces::vModel(true,'shop_pieces_order')->where($condition)->field($field)->order('id desc')->find();
        if( empty($data) ) {
            return false;
        } else{
            if( !empty($field) ){
                if( count(explode(',',$field)) == 1 ){
                    $data = $data[$field];
                }
            }
            return json_decode(json_encode($data),true);
        }
    }


    /**
     * 添加拼团订单
     * @param array $data
     * @param int $getID
     */
    public static function addPiecesOrder($data,$getID = false)
    {
        //查看当前团是否已满
        if( $data['head_id'] != $data['user_id'] ){
            //团长不是当前用户，检测当前团是否已满
            $D_value = Pieces::getDvalue($data['pieces_id']);
            if( $D_value == 0 ){
                return '当前拼团已满';
            }
        }
        //检测当前团是否过期
        $end_time = Pieces::vModel()->where('id',$data['pieces_id'])->where(Pieces::condition())->value('end_time');
        if( $end_time < date('Y-m-d H:i:s') ){
            return '当前拼团已过期';
        }
        //保存拼团信息
        try{
            if( $getID == true ){
                $save = Pieces::vModel(true,'shop_pieces_order')->insertGetId($data);
            }else{
                $save = Pieces::vModel(true,'shop_pieces_order')->insert($data);
            }
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/addPiecesOrder:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return $save;
    }

    /**
     * 序列化$goods_spec
     * @param string $goods_spec
     * @return bool|serialize_data
     */
    public static function spec_serialize($goods_spec)
    {
        //获取规格json字符串,将json字符串转化为序列化数据
        $spec_json = htmlspecialchars_decode($goods_spec);
        if( !empty($spec_json) ){
            $spec_arr = json_decode($spec_json,true);
            $spec_serialize = serialize($spec_arr);
            return $spec_serialize;
        }
        return false;
    }

    /**
     * 获取最新可参团的拼团列表
     * @param int $limit
     * @return array
     */
    public static function availableList($limit = '')
    {
        $page_row = tb_config('list_row',1,getLang());
        try{
            if( $limit == '' ){
                $list = Pieces::vModel(true)->alias('pg')->join('__SHOP_GOODS__ g','pg.goods = g.id')->where('pg.`status` = 1 and g.`status` = 1 and pg.`start_time` < "'.self::nowDate().'" and pg.`end_time` > "'.self::nowDate().'" ')->field('g.id,g.thumb,pg.price,pg.title,pg.group_num,pg.single_num,pg.people_num')->order('pg.group_num')->paginate($page_row);
            }else{
                $list = Pieces::vModel(true)->alias('pg')->join('__SHOP_GOODS__ g','pg.goods = g.id')->where('pg.`status` = 1 and g.`status` = 1 and pg.`start_time` < "'.self::nowDate().'" and pg.`end_time` > "'.self::nowDate().'" ')->field('g.id,g.thumb,pg.title,pg.price,pg.title,pg.single_num')->limit($limit)->order('pg.group_num')->select();
            }

        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/availableList:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return $list;
    }

    /**
     * 获取最新发布的拼团
     * @return array
     */
    public static function newPieces()
    {
        try{
            $new_pieces = Pieces::vModel(true)->alias('pg')->join('__SHOP_GOODS__ g','pg.goods = g.id')->where('pg.`status` = 1 and g.`status` = 1 and pg.`start_time` < "'.self::nowDate().'" and pg.`end_time` > "'.self::nowDate().'" ')->field('g.id,g.thumb,pg.price,pg.title,pg.group_num,pg.single_num,pg.people_num')->order('pg.start_time')->find();
        }catch (\Exception $exception){
            Log::error('shop/api/Pieces/newPieces:'.$exception->getMessage());
            return $exception->getMessage();
        }
        return $new_pieces;
    }


    /**
     * 获取当前团团长
     * @parma int $user_id
     * @param int $pieces_id
     * @return bool
     */
    public static function getHead($user_id,$pieces_id)
    {
        $user = intval($user_id);
        $pieces = intval($pieces_id);
        $condition = [
            'user_id' => $user,
            'pieces_id' => $pieces,
            'is_pay' => 1,
            'status' => 1,
        ];
        $head = Pieces::vModel(true,'shop_pieces_order')->where($condition)->value('head_id');
        if( empty($head) ){
            return false;
        }
        return $head;
    }

    /**
     * 查看还有多少人成团
     * @param int $id
     * @return int
     */
    public static function getDvalue($id)
    {
        //获取一共需要多少成团数
        $single_num = Pieces::get($id,'single_num');
        //查看当前成团数
        $condition = [
            'pieces_id' => $id,
            'is_pay' => 1,
            'status' => 1,
        ];
        $now_sin = Pieces::vModel(true,'shop_pieces_order')->where($condition)->count();
        return $single_num-$now_sin;
    }

    /**
     * 获取当前规格的价格
     * @param int $goods_id
     * @param int $spec_id
     */
    public static function getSpecPrice($goods_id,$spec_id)
    {
        //获取所有价格
        $condition = [
            'goods'=>$goods_id,
            'status' => 1,
        ];
        $spec_ser = Pieces::vModel()->where($condition)->value('goods_spec_price');
        if( !empty($spec_ser) ){
            $spec_arr = unserialize($spec_ser);
            if( !empty($spec_arr[$spec_id]) ){
                return $spec_arr[$spec_id];
            }
        }
        //没有规格，输入商品价格
        $price = Pieces::vModel()->where(['goods'=>$goods_id,'status'=>1])->value('price');
        return $price;
    }

    /**
     * 修改拼团号
     * @param int $pieces_order_id
     * @return mixed
     */
    public static function uPiecesSn($pieces_order_id)
    {
        $pieces_sn = Pieces::createPiecesSn();
        //修改
        $update = Pieces::vModel(true,'shop_pieces_order')->where('id',$pieces_order_id)->update(['pieces_sn'=>$pieces_sn]);
        if( $update == true ){
            return $pieces_sn;
        }else{
            return false;
        }

    }

    /**
     * 修改拼团数据
     * @param $pieces_order_id
     * @param $data
     * @return mixed
     */
    public static function uPiecesOrder($pieces_order_id,$data)
    {
        $update = Pieces::vModel(true,'shop_pieces_order')->where('id',$pieces_order_id)->update($data);
        if( $update == true ){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 创建拼团码
     *
     */
    public static function createPiecesSn()
    {
        //取当前时间戳
        $now_time  = date('ymdHis',time());
        //取随机数
        $rand = rand(12345,98765);
        $re_str = 'pt';
        return $re_str.$now_time.$rand;
    }

    /**
     * 获取基础查询条件
     */
    private static function condition()
    {
        $now_time = date('Y-m-d H:i:s',time());
        return ' end_time > "'.$now_time.'" ';
    }

    /**
     * 获取拼团ID
     * @param int $goods_id
     * @return mixed
     */
    public static function getPiecesId($goods_id)
    {
        //
        $pieces_id = Pieces::vModel()->where(Pieces::condition())->where('goods',$goods_id)->value('id');
        if( empty($pieces_id) ){
            return false;
        }
        return $pieces_id;
    }


    /**
     * 异步回调修改数据====================================================
     * ===================================================================
     */
    /**
     * 支付完成回调
     * @param $pieces_sn
     * @param $trade_no
     * @return mixed
     */
    public static function pieces_order_success($pieces_sn,$trade_no)
    {
        //检测当前拼团号是否正确
        $pieces = Pieces::vModel(true,'shop_pieces_order')->where('pieces_sn',$pieces_sn)->find();
        if( null === $pieces  ){
            //记录日志
            Log::log('shop/api/Pieces/pieces_order_success:拼团信息获取为null');
            return false;
        }elseif ( $pieces['is_pay'] == 1 ){
            //记录日志
            Log::log('shop/api/Pieces/pieces_order_success:ID为'.$pieces['id'].'的拼团信息为已支付');
            return false;
        }else{
            //操作订单
            Db::startTrans();
            try{
                Pieces::reduce_stock($pieces['goods'],$pieces['goods_num'],$pieces['spec']);
                Pieces::update_pieces_group($pieces['pieces_id'],$pieces['head_id'],$pieces_sn,$trade_no);
                Pieces::update_pieces_order($pieces['id'],$trade_no);
                Pieces::update_user_money($pieces);
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                //记录日志
                Log::error('shop/api/Pieces/pieces_order_success:'.$exception->getMessage());
                return $exception->getMessage();
            }
            return true;
        }
    }

    private static function update_user_money($pieces)
    {
        //修改用户金额
        $dec_user_money = $pieces['balance_price'];
        $inc_total_amount = $pieces['payable_price']+$pieces['postage']-$pieces['change_mny'];
        $condition = [
            'id' => $pieces['user_id'],
        ];
        Pieces::vModel(true,'users')->where($condition)->setInc('total_amount',$inc_total_amount);
        Pieces::vModel(true,'users')->where($condition)->setDec('user_money',$dec_user_money);
        //修改最近购买量
        Pieces::vModel(true,'users')->where($condition)->setInc('new_order',1);
        //修改总购买量
        Pieces::vModel(true,'users')->where($condition)->setInc('total_order',1);
        //记录用户资金日志
        $pieces_title = Pieces::vModel()->where('id',$pieces['pieces_id'])->value('title');
        User::account_log($pieces['user_id'],$inc_total_amount,0,$pieces['points_price'],'参与【'.$pieces_title.'】拼团',$pieces['pieces_sn']);
    }
    private static function update_pieces_order($order_id,$trade_no){
        //修改为已支付
        $condition = [
            'id'=>$order_id,
        ];
        Pieces::vModel(true,'shop_pieces_order')->where($condition)->update(['is_pay'=>1,'pay_sn'=>$trade_no]);
    }

    private static function update_pieces_group($pieces_id,$head_id,$pieces_sn,$trade_no){
        // 检测是否已经满了一个团
        // 获取当前head下面的所有购买记录
        $condition = [
            'pieces_id' => $pieces_id,
            'head_id' => $head_id,
            'is_pay' => 1,
            'is_return' => 0,
            'status' => 1,
        ];
        $order_sum = Pieces::vModel(true,'shop_pieces_order')->where($condition)->count();
        $order_sum += 1;
        // 获取成团数
        $single_num = Pieces::vModel()->where('id',$pieces_id)->value('single_num');
        if( $order_sum == $single_num ){
            Pieces::vModel()->where('id',$pieces_id)->setInc('group_num');
            //将当前团的所有信息存入到订单表
            //获取所有记录
            $list = Pieces::vModel(true,'shop_pieces_order')->where('pieces_sn<>"'.$pieces_sn.'"')->where($condition)->select();
            $pieces = Pieces::vModel(true,'shop_pieces_order')->where(['pieces_sn'=>$pieces_sn])->find();
            $list[] = $pieces;
            $saveData = [];
            //获取商品cid和shopID
            $goods = Pieces::vModel(true,'shop_goods')->where('id',$pieces['goods'])->field('id,title,shop_price,cid,shop_id')->find();
            foreach ($list as $v){
                if( $pieces_sn == $v['pieces_sn'] ){
                    $pay_sn = $trade_no;
                    $pay_time = date('Y-m-d H:i:s');
                }else{
                    $pay_sn = $v['pay_sn'];
                    $pay_time = $v['pay_time'];
                }
                $saveData = [
                    'pieces_id' => $v['id'],
                    'cid' => $goods['cid'],
                    'shop_id' => $goods['shop_id'],
                    'province' => $v['province'],
                    'city' => $v['city'],
                    'district' => $v['district'],
                    'address' => $v['address'],
                    'consignee' => $v['consignee'],
                    'phone' => $v['phone'],
                    'pay_code' => $v['pay_code'],
                    'order_sn' => $v['pieces_sn'],
                    'pay_sn' => $pay_sn,
                    'user_id' => $v['user_id'],
                    'add_time' => date('Y-m-d H:i:s'),
                    'pay_time' => $pay_time,
                    'payable_price' => $v['payable_price'],
                    'total_price' => $v['total_price'],
                    'balance_price' => $v['balance_price'],
                    'points_price' => $v['points_price'],
                    'postage' => $v['postage'],
                    'change_mny' => $v['change_mny'],
                    'is_pay' => 1,
                    'status' => 3,
                ];
                //插入到订单表中
                $order_id = Pieces::vModel(true,'shop_order')->insertGetId($saveData);
                //订单ID插入到拼团订单表中
                Pieces::vModel(true,'shop_pieces_order')->where('id',$v['id'])->update(['order_id'=>$order_id]);
                //插入到订单商品表中
                $spec = Db::name('shop_spec_price')->where('id',$v['spec'])->field('key_sign,key_name')->find();
                $order_goods_data = [
                    'order_id' => $order_id,
                    'goods_id' => $goods['id'],
                    'goods_name' => $goods['title'],
                    'spec_id' => $v['spec'],
                    'spec_key' => $spec['key_sign'],
                    'spec_title' => $spec['key_name'],
                    'shop_id' => $goods['shop_id'],
                    'shop_price' => $goods['shop_price'],
                    'pay_price' => $v['payable_price']/$v['goods_num'],
                    'total_price' => $v['total_price'],
                    'payable_price' => $v['payable_price'],
                    'goods_num' => $v['goods_num'],
                    'postage' => $v['postage'],
                ];
                //插入
                Db::name('shop_order_goods')->insert($order_goods_data);
            }
        }
        //修改购买人数
        Pieces::vModel()->where('id',$pieces_id)->setInc('people_num');

    }
    private static function reduce_stock($goods_id,$goods_num,$spec_id = ''){
        //使用文件锁，锁定商品，防止高并发下库存错误
        $lock_file = Pieces::getGoodsLockFile($goods_id,$spec_id);
        $fp = fopen($lock_file,"w+");
        if( flock($fp , LOCK_EX) ){
            if( $spec_id != '' ){
                Db::name('shop_spec_price')->where('id',$spec_id)->setDec('store_count',$goods_num);
            }else{
                Db::name('shop_goods')->where('id',$goods_id)->setDec('stock',$goods_num);
            }
            flock($fp , LOCK_UN);
        }
        fclose($fp);
    }
    private static function getGoodsLockFile($goods_id,$spec_id = ''){
        $file  = ROOT_PATH . 'public/lock/shop/goods/'.$goods_id.$spec_id.'.lock';
        return $file;
    }
    /**
     * 异步回调修改数据========================================
     * =======================================================
     */



    /**
     * 当前时间
     */
    private static function nowDate()
    {
        return date('Y-m-d H:i:s',time());
    }

}