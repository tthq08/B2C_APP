<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/7/19
 * Time: 下午3:10
 */

namespace app\shop\api;

use think\Db;
use think\Log;

class CouponSend
{

    /**
     * 平台发放,手动发放
     * 优惠券发放
     * @param int $user_id 用户ID
     * @param int $coupon_id 优惠券ID
     * @return string
     */
    public function send_coupon($user_id,$coupon_id,$logInfo = ''){
        intval($user_id);
        //获取优惠券详细信息
        if( is_numeric($coupon_id) ){
            $couponInfo = api('shop','Coupon','couponInfo',[$coupon_id]);
        }else{
            $couponInfo = $coupon_id;
        }
        $sendData['user'] = $user_id;
        $sendData['money'] = $couponInfo['money'];
        $sendData['coupon'] = $coupon_id;
        $sendData['coupon_name'] = $couponInfo['title'];
        $sendData['coupon_level'] = $couponInfo['coupon_level'];
        $sendData['discount_type'] = $couponInfo['discount_type'];
        $sendData['quota'] = $couponInfo['quota'];
        $sendData['max_quota'] = $couponInfo['max_quota'];
        $sendData['shop_id'] = $couponInfo['shop_id'];
        $sendData['num'] = $couponInfo['one_use_num'] ? $couponInfo['one_use_num'] : 1;
        $sendData['is_use'] = 0;
        $sendData['start_time'] = $couponInfo['use_start_time'];
        if( !empty($couponInfo['use_days']) )
        {
            $end_use_date = date('Y-m-d H:i:s',time());
            $sendData['end_time'] = strtotime($end_use_date . ' +'.$couponInfo['use_days'].' days');
        }else{
            $sendData['end_time'] = $couponInfo['use_end_time'];
        }

        Db::startTrans();
        try{
            $insert = Db::name('user_coupon')->insertGetId($sendData);
            // 增加已发放数量
            Db::name('shop_coupon')->where('id',$couponInfo['id'])->setInc('send_num',$sendData['num']);
            // 插入用户优惠券绑定
            $this->insertUserCouponBind($couponInfo,$insert);
            // 插入优惠券日志
            $admin = empty(session('admin_id')) ? '' : session('admin_id');
            $logInfo = empty($logInfo) ? '优惠券发放!' : $logInfo;
            $log = [
                'coupon' => $couponInfo['id'],
                'user' => $user_id,
                'type' => 3,
                'description' => $logInfo,
                'admin' => $admin,
            ];
            api('shop','Coupon','log',[$log]);
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            return $e->getMessage();
        }

        if( $insert === false ){
            return false;
        }else{
            return $insert;
        }
    }


    /**
     * 插入用户优惠券绑定信息
     * @param $coupon_info
     * @param $user_coupon_id
     * @return bool
     */
    protected function insertUserCouponBind($coupon_info,$user_coupon_id)
    {
        if( !empty($coupon_info['goods']) ){
            $goods = explode(',',$coupon_info['goods']);
            $insertBindData = [];
            foreach ($goods as $k=>$value){
                $insertBindData[$k]['user_coupon'] = $user_coupon_id;
                $insertBindData[$k]['goods'] = $value;
            }
            Db::name('user_coupon_bind')->insertAll($insertBindData);
        }elseif ( !empty($coupon_info['goods_category']) ){
            $goods_category = explode(',',$coupon_info['goods_category']);
            $insertBindData = [];
            foreach ($goods_category as $k=>$value){
                $insertBindData['user_coupon'] = $user_coupon_id;
                $insertBindData['goods_category'] = $value;
            }
            Db::name('user_coupon_bind')->insertAll($insertBindData);
        }
    }


    /**
     * 注册发放
     * @param $user
     * @return bool
     */
    public function registerSend($user)
    {
        // 获取所有注册发放的优惠券
        $time = time();
        $condition = '`send_start_time` < "'.$time.'" and `send_end_time` > "'.$time.'" and `status` = 1 and `send_num` < `num` and `send_type` = 1 ';
        $list = Db::name('shop_coupon')->where($condition)->field('id')->select();
        foreach ($list as $key=>$coupon){
            $this->send_coupon($user,$coupon['id']);
        }

    }


    /**
     * 随机发放
     * @param $coupon
     * @param $num
     * @return bool
     */
    public function randomSend($coupon)
    {
        $couponInfo = api('shop','Coupon','couponInfo',[$coupon]);
        // 获取发放数量
        $num = $couponInfo['num'];
        // 获取最后一个用户id
        $randUserS = $this->randSelect('tb_users',$num);
        foreach ($randUserS as $user){
            $this->send_coupon($user['id'],$couponInfo['id']);
        }
    }


    /**
     * 随机查询数据
     * @param $table
     * @param string $limit
     */
    public function randSelect($table,$limit = 10)
    {
        $userList = [];
        for ($i =0;$i<$limit;$i++){
            $query = 'SELECT t1.id FROM `'.$table.'` AS t1 JOIN (SELECT ROUND(RAND() * (SELECT MAX(id) FROM `'.$table.'`)) AS id) AS t2 WHERE t1.id >= t2.id ORDER BY t1.id ASC LIMIT 1';
            $list = Db::query($query);
            $list = $list[0];
            $userList[] = $list;
        }

        return $userList;
    }


    /**
     * 邀请发放
     * @param $coupon
     * @param $user
     * @return bool
     */
    public function invitationSend($coupon,$user)
    {
        // 查看用户时间段内邀请的人数
        if( is_numeric($coupon) ){
            $couponInfo = api('shop','Coupon','couponInfo',[$coupon]);
        }else{
            $couponInfo = $coupon;
        }
        // 解析发放层级
        $sendHierarchy = json_decode($couponInfo['hierarchy'],1);
        // 获取邀请人数
        $where = '`add_time` >= "'.date('Y-m-d H:i:s',$couponInfo['send_start_time']).'" and `add_time` <= "'.date('Y-m-d H:i:s',$couponInfo['send_end_time']).'"';
        $count = Db::name('users_tree')->where('pid',$user)->where($where)->count();
        if(empty($count)){
            return true;
        }
        $quota = 0;
        foreach ($sendHierarchy as $k=>$hierarchy) {
            if( $hierarchy['peopel_num'] >= $count ){
                $quota = $hierarchy['quota'];
            }else{
                continue;
            }
        }
        if( empty($quota) ){
            return true;
        }
        $sendData['user'] = $user;
        $sendData['money'] = $couponInfo['money'];
        $sendData['coupon'] = $couponInfo['id'];
        $sendData['coupon_name'] = $couponInfo['title'];
        $sendData['coupon_level'] = $couponInfo['coupon_level'];
        $sendData['discount_type'] = $couponInfo['discount_type'];
        $sendData['quota'] = $quota;
        $sendData['max_quota'] = $couponInfo['max_quota'];
        $sendData['shop_id'] = $couponInfo['shop_id'];
        $sendData['goods'] = $couponInfo['goods'];
//        $sendData['spec'] = $couponInfo['spec'];
        $sendData['goods_category'] = $couponInfo['goods_category'];
        $sendData['num'] = $couponInfo['one_use_num'];
        $sendData['is_use'] = 0;
        $sendData['start_time'] = $couponInfo['use_start_time'];
        if( !empty($couponInfo['use_days']) )
        {
            $end_use_date = date('Y-m-d H:i:s',time());
            $sendData['end_time'] = strtotime($end_use_date . ' +'.$couponInfo['use_days'].' days');
        }else{
            $sendData['end_time'] = $couponInfo['use_end_time'];
        }
        // 发放优惠券
        Db::startTrans();
        try{
            $insert = Db::name('user_coupon')->insertGetId($sendData);
            // 增加已发放数量
            Db::name('shop_coupon')->where('id',$couponInfo['id'])->setInc('send_num',$sendData['num']);
            // 插入用户优惠券绑定
            $this->insertUserCouponBind($sendData,$insert);
            // 插入优惠券日志
            $admin = empty(session('admin_id')) ? '' : session('admin_id');
            $log = [
                'coupon' => $couponInfo['id'],
                'user' => $user,
                'type' => 3,
                'description' => '邀请优惠券发放!',
                'admin' => $admin,
            ];
            api('shop','Coupon','log',[$log]);
            Db::commit();
        }catch (\ErrorException $e)
        {
            Db::rollback();
            Log::error($e);
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 通过优惠码领取
     * @param $coupon_code
     * @oaram $user
     * @return bool
     */
    public function codeSend($coupon_code,$user)
    {
        // 获取优惠券信息
        $coupon_id = getTableValue('shop_coupon_code','code="'.$coupon_code.'"','coupon');

        $send = $this->send_coupon($user,$coupon_id,'通过优惠码领取!');

        if( $send == true ){
            Db::startTrans();
            try{
                // 修改优惠码信息
                $codeInfo = [
                    'user' => $user,
                    'user_coupon' => $send,
                    'is_receive' => 1,
                    'receive_time' => time(),
                ];
                Db::name('shop_coupon_code')->where('code',$coupon_code)->update($codeInfo);
                Db::commit();
            }catch (\ErrorException $e){
                Db::rollback();
                Log::error(json_encode($e));
                return false;
            }
            return true;
        }
        return false;
    }



    /**
     * 新用户第一次购买发放优惠券
     * @parma $orderInfo 订单信息
     * @return mixed
     */
    public function newUserSend($orderInfo,$user)
    {
        // 检测是否有优惠券
        $coupon = api('shop','Coupon','getNewUserCoupon',$orderInfo['payable_price']-$orderInfo['change_mny']);
        if( !empty($coupon) ){
            // 发送优惠券
            $this->send_coupon($user,$coupon['id'],'第一次购物奖励!');
        }
        Db::name('users')->where('id',$user)->update(['new_user'=>1]);
    }


    /**
     * 购物发放,随机选一张购物发放的优惠券发放给用户
     * @return mixed
     */
    public function buySend($orderInfo,$user)
    {

        $payable_price = $orderInfo['payable_price']-$orderInfo['change_mny'];
        $coupon = Db::name('shop_coupon')->where('send_type',7)->where('status = 1')->where('`send_money`<='.$payable_price)->order('rand()')->find();
        if( !empty($coupon) ){
            // 发送优惠券
            $this->send_coupon($user,$coupon['id'],'购物完成后领取!');
        }
    }


    /**
     * 发放升级奖励优惠券
     * @param $userId
     * @return mixed
     */
    public function upgrade_bonus($userId)
    {
        $userInfo = api('member','User','userInfo',$userId);
        $userLevel = $userInfo['level'];
        $where = 'FIND_IN_SET('.$userLevel.',send_user_level)';
        $time = time();
        $condition = '`send_start_time` < "'.$time.'" and `send_end_time` > "'.$time.'" and `status` = 1 and `send_num` < `num` and `send_type` = 10 ';
        $couponList = Db::name('shop_coupon')->where($where)->where($condition)->select();
        foreach ($couponList as $key=>$coupon){
            $this->send_coupon($userInfo['id'],$coupon['id'],'用户升级奖励!');
        }
    }

}