<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 优惠券管理类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\api;

use think\Db;
use think\Log;

class Coupon
{
    protected $_DB;
    protected static $condition;

    public function __construct(){
        //定义，连接数据表
        $this->_DB = Db::name('shop_coupon');
        self::$condition = '`status` <> -1';
    }

    /**
     * 查询用户所有可使用优惠券
     * @param int $user_id  用户ID
     * @return string
     */
    public function userCoupon($user_id,$is_use = true){
        intval($user_id);
        if($user_id){
            if( $is_use == true ){
                $nowDate = time();
                $where = 'uc.`status` = 1 and uc.`start_time` < "'.$nowDate.'" and uc.`end_time` > "'.$nowDate.'" and uc.`is_use` = 0';
            }else{
                $where = '`status` = 1';
            }
            $field = 'uc.id as uc_id,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.discount_type,sc.quota,sc.money,sc.goods,sc.goods_category,sc.promotion,sc.shop_id';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('sc.status <> -1')->where('user',$user_id)->order('is_use desc')->select();

            return $userCoupon;
        }else{
            return false;
        }
    }

    /**
     * 查询用户领取的优惠券详情
     * @param $user_id
     * @param $coupon_id
     * @return array
     */
    public function userCouponInfo($user_id,$coupon_id)
    {
        $info = Db::name('user_coupon') ->where('user',$user_id)->where('coupon',$coupon_id)->where('status <> -1')->find();
        return $info;
    }


    /**
     * 查询用户领取的优惠券详情
     * @param $user_coupon_id
     * @return array
     */
    public function userCouponInfoFromId($user_coupon_id)
    {
        $info = Db::name('user_coupon') ->where('id',$user_coupon_id)->where('status <> -1')->find();
        return $info;
    }


    /**
     * 获取用户所有biz券
     * @param $user
     * @return array
     */
    public function userBizCoupons($user)
    {
        $time = time();
        $where = [
            'coupon_level' => 1,
            'user' => $user,
        ];
        $whereStr = '`start_time` <= ' . $time . ' and `end_time` >= ' . $time . ' and `used_num` < `num` and `status` = 1';

        $couponList = Db::name('user_coupon')->where($where)->where($whereStr)->select();
        foreach ($couponList as $k=>$coupon){
            $couponList[$k]['end_date'] = date('Y-m-d',$coupon['end_time']);
        }
        return $couponList;
    }



    /**
     * 获取优惠券的使用范围
     * @param int $id 优惠券ID
     * @return string
     */
    public function getUseScope($id){
        //获取优惠券信息
        intval($id);
        $couponInfo = Db::name('shop_coupon')->where(self::$condition)->where('id',$id)->where('status',1)->field('cid,shop_id,goods,goods_category')->find();
        if( $couponInfo =='' ){
            return '优惠券不存在';
        }
        if( $couponInfo['goods'] > 0 ){
            return '指定商品使用';
        }elseif($couponInfo['goods_category']){
            if( $couponInfo['shop_id'] > 0 ){
                return '指定店铺下商品分类使用';
            }
            return '指定全场商品分类使用';
        }elseif ($couponInfo['shop_id'] > 0){
            return '指定店铺使用';
        }elseif ($couponInfo['cid'] > 0){
            return '指定商户使用';
        }else{
            return  '全场通用';
        }
    }


    /**
     * 查询用户已经使用的优惠券
     * @param int $user_id  用户ID
     * @return string
     */
    public function userUsedCoupon($user_id){
        intval($user_id);
        if($user_id){
            $nowDate = time();
            $where = 'uc.`status` = 1 and uc.`start_time` < "'.$nowDate.'" and uc.`end_time` > "'.$nowDate.'" and uc.`is_use` = 1';

            $field = 'uc.id as uc_id,uc.use_time,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.discount_type,sc.quota,sc.money,sc.goods,sc.goods_category,sc.promotion,sc.shop_id';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('sc.status <> -1')->where('user',$user_id)->select();

            return $userCoupon;
        }else{
            return false;
        }
    }


    /**
     * 查询用户已经过期的优惠券
     * @param int $user_id  用户ID
     * @return string
     */
    public function userTimedCoupon($user_id){
        intval($user_id);
        if($user_id){
            $nowDate = time();
            $where = 'uc.`status` = 1 and uc.`end_time` < "'.$nowDate.'" and uc.`is_use` = 0 ';

            $field = 'uc.id as uc_id,uc.use_time,uc.is_use,uc.user,uc.start_time,uc.end_time,sc.id as id,sc.name,sc.discount_type,sc.quota,sc.money,sc.goods,sc.goods_category,sc.promotion,sc.shop_id';
            //获取所有优惠券
            $userCoupon = Db::name('user_coupon')->alias('uc')->join('tb_shop_coupon sc','uc.coupon = sc.id')->field($field)->where($where)->where('sc.status <> -1')->where('user',$user_id)->select();

            return $userCoupon;
        }else{
            return false;
        }
    }


    /**
     * 查看优惠券的种类
     * 1/折扣券 2/金额券 3/积分券 4/商品券
     * @param int $id 优惠券ID
     * @return string
     */
    function getTypeStr($id){
        $type = getTableValue('shop_coupon','id='.$id,'type');
        $discount_type = config('discount_type');
        return $discount_type[$type];
    }


    /**
     * 查询该会员是否领取当前优惠券
     * @param int $coupon_id 优惠券ID
     * @param int $user_id 用户ID
     * @return boolean
     */
    public function obtainCoupon($coupon_id,$user_id){

        $exist = Db::name('user_coupon')->where(['user'=>$user_id,'coupon'=>$coupon_id])->value('id');
        if( empty($exist) ){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 查询该优惠券用户是否可以
     * @param int $coupon_id 优惠券ID
     * @param int $user_id 用户ID
     * @param int $goods_id default null 商品ID，加入商品ID后判断用户能否对该商品使用
     * @return boolean
     */
    public function userUse($coupon_id,$user_id,$goods_id = ''){

        //查找优惠券详细信息
        $couponInfo = $this->couponInfo($coupon_id,'num,used_num,user_group');
        if( $couponInfo['num'] > $couponInfo['used_num'] ){
            // 查看优惠券的过期时间
            // 查看优惠券获取的时间
            $uCoupon = $this->userCouponInfo($user_id,$coupon_id);
            if( empty($uCoupon['num']) ){
                return false;
            }
            if( $uCoupon['used_num'] == 0 ){
                return false;
            }
            $date = date('Y-m-d H:i:s',time());
            if( $uCoupon['start_time'] < $date ){
                return false;
            }
            if( $uCoupon['end_time'] > $date ){
                return false;
            }
            return true;
        }
        return false;
    }


    /**
     * 查看用户是否能领取该优惠券
     * @param $coupon_id
     * @param $user_id
     * @return bool
     */
    public function isReceiveCoupon($coupon_id, $user_id)
    {
        $time = time();
        $couponInfo = $this->couponInfo($coupon_id);
        if( $couponInfo['send_start_time'] > $time || $couponInfo['send_end_time'] < $time || $couponInfo['send_num'] >= $couponInfo['num'] ){
            return false;
        }
        $user = api('member','User','userInfo',[$user_id]);
        $userGroup = explode(',',$couponInfo['user_group']);
        if( !in_array($user['level'],$userGroup) ){
            return false;
        }
        return true;
    }


    /**
     * 查询优惠券详细信息
     * @param int $coupon_id  优惠券ID
     * @return mixed
     */
    public function couponInfo($coupon_id,$field = ''){
        intval($coupon_id);
        if( $coupon_id ){
            if( empty(cache('coupon:'.$coupon_id)) ){
                $coupon = Db::name('shop_coupon')->field($field)->where(self::$condition)->where('id',$coupon_id)->find();
                cache('coupon:'.$coupon_id,$coupon,'','shop-coupon');
            }else{
                $coupon = cache('coupon:'.$coupon_id);
            }
            return $coupon;
        }else{
            return false;
        }
    }

    /**
     * 通过优惠码查询优惠券详细信息
     * @param int $coupon_code 优惠码
     * @return array
     *
     */
    public function couponCodeInfo($coupon_code,$field = ''){
        // 查询优惠券id
        $coupon = Db::name('shop_coupon_code')->where('code',$coupon_code)->find();
        if( empty($coupon) ){
            return null;
        }
        $coupon_id = $coupon['coupon'];
        $where = '`id` = '.$coupon_id;
        if( $field !== '' ){
            $field_num = count(explode(',',$field));
            if( $field_num == 1 ){
                $userCoupon = Db::name('shop_coupon')->where(self::$condition)->where($where)->value($field);
            }else{
                $userCoupon = Db::name('shop_coupon')->where(self::$condition)->field($field)->where($where)->find();
            }
        }else{
            $userCoupon = Db::name('shop_coupon')->where(self::$condition)->where($where)->find();
        }
        if( !empty($userCoupon) ){
            $userCoupon['code'] = $coupon['code'];
            $userCoupon['user'] = $coupon['user'];
            $userCoupon['is_receive'] = $coupon['is_receive'];
        }
        return $userCoupon;
    }


    /**
     * 查找所有优惠券
     * @param string $field
     * @return string
     */
    public function selectCoupon($field = '')
    {
        $list_row = tb_config('list_row',1,1);
        $coupon = $this->_DB->where(self::$condition)->order('add_time desc')->field($field)->paginate($list_row);

        return $coupon;
    }


    /**
     * 查找所有首页显示的优惠券
     * @return mixed
     */
    public function indexCouponList($limit = 30,$userLevel = '',$sort = '', $couponLevel = '')
    {
        $time = time();
        $where = '`send_start_time` < '.$time.' and `send_end_time` > '.$time.' and `status` = 1 and `index_show` = 1 and `send_type` <> 1 ';
        if( !empty($userLevel) ){
            $where .= 'and FIND_IN_SET('.$userLevel.',user_group)';
        }
        if( !empty($couponLevel) ){
            $where .= 'and `coupon_level` = '.$couponLevel;
        }
        $coupon = $this->_DB->where(self::$condition)->order($sort)->order('add_time desc')->cache(true)->where($where)->paginate($limit);
        return $coupon;
    }


    /**
     * 获取优惠方式
     * @return mixed
     */
    public function discountType()
    {
        // 获取所有优惠方式
        if( empty(cache('coupon_discount_type')) ) {
            $discountType = Db::name('shop_coupon_discount_type')->where(self::$condition)->select();
            cache('coupon_discount_type',$discountType,'','shop-coupon');
        }else{
            $discountType = cache('coupon_discount_type');
        }
        return $discountType;
    }


    /**
     * 获取优惠方式名称
     * @param $id
     * @return mixed
     */
    public function discountTypeName($id)
    {
        // 获取所有优惠方式
        if( empty(cache('coupon_discount_type:'.$id)) ) {
            $discountType = Db::name('shop_coupon_discount_type')->where('id',$id)->where(self::$condition)->find();
            cache('coupon_discount_type:'.$id,$discountType,'','shop-coupon');
        }else{
            $discountType = cache('coupon_discount_type:'.$id);
        }
        return $discountType['name'];
    }


    /**
     * 获取发放方式
     * @return mixed
     */
    public function sendType($getFalse = 0)
    {
        // 获取发放方式
        if( empty(cache('coupon_send_type')) ) {
            $status = $getFalse ? '`status` <> -1' : '`status` = 1';
            $sendType = Db::name('shop_coupon_send_type')->where($status)->select();
            cache('coupon_send_type',$sendType,'','shop-coupon');
        }else{
            $sendType = cache('coupon_send_type');
        }
        return $sendType;
    }

    /**
     * 获取发放方式名称
     * @param $id
     * @return string
     */
    public function sendTypeName($id)
    {
        // 获取发放方式
        if( empty(cache('coupon_send_type:'.$id)) ) {
            $sendType = Db::name('shop_coupon_send_type')->where('id',$id)->where(self::$condition)->find();
            cache('coupon_send_type:'.$id,$sendType,'','shop-coupon');
        }else{
            $sendType = cache('coupon_send_type:'.$id);
        }
        return $sendType['name'];
    }



    /**
     * 优惠券等级
     * @return mixed
     */
    public function couponLevel()
    {
        // 获取所有等级
        if( empty(cache('coupon_level')) ){
            $couponLevel = Db::name('shop_coupon_level')->where(self::$condition)->select();
            cache('coupon_level',$couponLevel,'','shop-coupon');
        }else{
            $couponLevel = cache('coupon_level');
        }
        return $couponLevel;
    }

    /**
     * 获取等级信息
     * @param $id
     * @return mixed
     */
    public function couponLevelInfo($id)
    {
        if( empty(cache('coupon_level:'.$id)) ){
            $couponLevel = Db::name('shop_coupon_level')->where('id',$id)->where(self::$condition)->find();
            cache('coupon_level:'.$id,$couponLevel,'','shop-coupon');
        }else{
            $couponLevel = cache('coupon_level:'.$id);
        }
        return $couponLevel;
    }


    /**
     * 插入优惠券
     * @param $data
     * @return mixed
     */
    public function insert($data)
    {
        // 过滤时间
        $data['use_start_time'] = intval($data['use_start_time']) == $data['use_start_time'] ? $data['use_start_time'] : strtotime($data['use_start_time']);
        $data['send_start_time'] = intval($data['send_start_time']) == $data['send_start_time'] ? $data['send_start_time'] : strtotime($data['send_start_time']);
        $data['use_end_time'] = intval($data['use_end_time']) == $data['use_end_time'] ? $data['use_end_time'] : strtotime($data['use_end_time']);
        $data['send_end_time'] = intval($data['send_end_time']) == $data['send_end_time'] ? $data['send_end_time'] : strtotime($data['send_end_time']);

        // 过滤额度
        if( !empty($data['discount_type']) ){
            if( $data['discount_type'] == 2  ){
                // 使用当前汇率进行转化
                if( $data['quota_type'] == 1 ){
                    $data['quota'] = api('shop','Compute','getWonFormRmb',[$data['cn_quota']]);
                }else{
                    $data['quota'] = $data['cn_quota'];
                }
            }else{
                if (!empty($data['cn_quota'])){
                    $data['quota'] = $data['cn_quota'];
                }
            }
        }
        unset($data['quota_type']);

        $data['add_time'] = time();
        if( empty($data['id']) ){
            $coupon_id = Db::name('shop_coupon')->insertGetId($data);
            // 检测是否为随机发放
            if( $data['send_type'] == 5 ){
                // 随机发放
                api('shop','CouponSend','randomSend',[$coupon_id]);
            }

            if( $data['send_type'] == 9 )
            {
                // 创建优惠码
                $this->create_code($coupon_id,$data['code_type'],$data['num']);
            }
            // 存入日志
            $this->log([
                'admin' => session('admin_id'),
                'coupon' => $coupon_id,
                'type' => 1,
                'description' => '后台管理员创建优惠券',
            ]);
        }else{
            $shop_array = $data['shop_id'];
            unset($data['shop_id']);
            foreach ($shop_array as $k=>$value )
            {
                $data['shop_id'] = $value;
                $coupon_id = Db::name('shop_coupon')->insert($data);
                $this->log([
                    'admin' => session('admin_id'),
                    'coupon' => $coupon_id,
                    'type' => 2,
                    'description' => '后台管理员修改优惠券',
                ]);
            }
        }
    }


    /**
     * 更新优惠券信息
     * @param $data
     * @return mixed
     */
    public function update($data)
    {
        // 过滤时间
        $data['use_start_time'] = intval($data['use_start_time']) == $data['use_start_time'] ? $data['use_start_time'] : strtotime($data['use_start_time']);
        $data['send_start_time'] = intval($data['send_start_time']) == $data['send_start_time'] ? $data['send_start_time'] : strtotime($data['send_start_time']);
        $data['use_end_time'] = intval($data['use_end_time']) == $data['use_end_time'] ? $data['use_end_time'] : strtotime($data['use_end_time']);
        $data['send_end_time'] = intval($data['send_end_time']) == $data['send_end_time'] ? $data['send_end_time'] : strtotime($data['send_end_time']);

        $data['update_time'] = time();

        Db::name('shop_coupon')->where('id',$data['id'])->update($data);
        $this->log([
            'admin' => session('admin_id'),
            'coupon' => $data['id'],
            'type' => 1,
            'description' => '更新优惠券信息',
        ]);
    }


    /**
     * 修改优惠券状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function update_status($id,$status)
    {
        Db::startTrans();
        try{
            Db::name('shop_coupon')->where('id',$id)->update(['status'=>$status]);
            $this->log([
                'admin' => session('admin_id'),
                'coupon' => $id,
                'type' => 1,
                'description' => '更新优惠券状态',
            ]);
        }catch (\Exception $e){
            Log::error($e);
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }


    /**
     * 修改优惠券首页显示状态
     * @param $id
     * @param $index_show
     * @return mixed
     */
    public function update_index_show($id,$index_show)
    {
        Db::startTrans();
        try{
            Db::name('shop_coupon')->where('id',$id)->update(['index_show'=>$index_show]);
            $this->log([
                'admin' => session('admin_id'),
                'coupon' => $id,
                'type' => 1,
                'description' => '更新优惠券首页显示状态',
            ]);
        }catch (\Exception $e){
            Log::error($e);
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }


    /**
     * 创建优惠码
     * @param
     */
    public function create_code($coupon,$code_type,$num)
    {
        $codeArray = [];
        if ( $code_type == 1 ){
            for ($i=0;$i<$num;$i++){
                $code = from10_to62(rand(12345,98765).time().rand(12345,98765));
                $codeArray[$i] = [
                    'coupon' => $coupon,
                    'code' => $code,
                    'create_time' => time(),
                ];
            }
        }else{
            $code = from10_to62(rand(12345,98765).time().rand(12345,98765));
            for ($i=0;$i<$num;$i++){
                $codeArray[$i] = [
                    'coupon' => $coupon,
                    'code' => $code,
                    'create_time' => time(),
                ];
            }
            Db::name('shop_coupon')->where('id',$coupon)->update(['coupon_code'=>$code]);
        }

        // 插入数据库
        Db::name('shop_coupon_code')->insertAll($codeArray);
    }


    /**
     * 获取当前可发放的
     */
    public function InvitationCoupons()
    {
        // 发放类型,是否自动发放,已到达发放结束时间
        $where = ['send_type' => 8,'is_auto_send' => 0,'status'=>1];
        $whereVar = '`send_end_time` < '.time();
        // 查询
        $list = Db::name('shop_coupon')->where($where)->where($whereVar)->select();
        return $list;
    }


    /**
     * 优惠码列表
     * @param $coupon_id
     * @return mixed
     */
    public function code_list($coupon_id)
    {
        $code_list = Db::name('shop_coupon_code')->where('coupon',$coupon_id)->paginate(20);
        return $code_list;
    }


    /**
     * 获取优惠券优惠额度(加上符号)
     * @param $discount_type
     * @param $quota
     * @return mixed
     */
    public function getQuota($discount_type,$quota)
    {
        if( $discount_type == 1 ){
            $quota = $quota.'%';
        }elseif( $discount_type == 2 ){
            $quota = $quota.tb_config('web_coupon_currency',1);
        }
        return $quota;
    }


    /**
     * 日志记录
     * @param $data
     */
    function log($data)
    {
        $data['time'] = empty($data['time']) ? time() : $data['time'];
        $data['status'] = empty($data['status']) ? 1 : $data['status'];
        Db::name('shop_coupon_log')->insert($data);
    }



}