<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 用户管理，用户资金管理类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\member\api;

use think\Db;
use think\Log;

class User
{
    //记录用户资金日志
    public static function account_log($user_id,$user_money,$feozen_money,$pay_points,$desc,$order_sn)
    {
        $accountData = [
            'user_id' => $user_id,
            'user_money' =>  $user_money,
            'frozen_money' =>  -$feozen_money,
            'pay_points' => $pay_points,
            'change_time' => NOW_TIME,
            'desc' => $desc,
            'order_sn' => $order_sn,
        ];
        Db::name('user_account_log') ->insert($accountData);
    }

    /**
     * 获取分成信息
     * @param int $id
     * @param string $field
     * @return array
     */
    public function distribution($id,$field = '')
    {
        $info = Db::name('user_distribution')->where('id',$id)->field($field)->find();
        return $info;
    }

    /**
     * 确认分成
     * @param int $id 分成ID
     * @param
     */
    function determine_distribution($id){
        //获取基本信息
        $distribution = Db::name('user_distribution')->where('id',$id)->find();
        if( empty($distribution) ){
            return false;
        }
        if( $distribution['is_divided_into'] == 1 ){
            $this->error('该奖励已经分成');
        }
        //记录到用户资金日志
        $account_log_data['user_id'] = $distribution['user_id'];
        $account_log_data['user_money'] = $distribution['divided_into_price'];
        $account_log_data['change_time'] = NOW_TIME;
        $account_log_data['desc'] = '订单分成奖励';
        $account_log_data['order_id'] = $distribution['order_id'];
        $account_log_data['order_sn'] = getTableValue('shop_order',$distribution['order_id'],'order_sn');
        Db::startTrans();
        try{
            //打款到用户账户
            Db::name('users')->where('id',$distribution['user_id'])->setInc('sale_account',$distribution['order_price']);
            Db::name('users')->where('id',$distribution['user_id'])->setInc('forzen_distribut_money',$distribution['divided_into_price']);
            //记录用户资金日志表
            Db::name('user_account_log')->insert($account_log_data);
            //更改状态
            Db::name('user_distribution')->where('id',$id)->update(['is_divided_into'=>1]);
            // 记录到用户分成记录
            $this->distribution_log($id,$distribution['divided_into_price'],'',1);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 收回分成
     * @param int $id 分成ID
     * @param
     */
    function take_back_distribution($id,$money,$remark){
        //获取基本信息
        $distribution = Db::name('user_distribution')->where('id',$id)->find();
        if( empty($distribution) ){
            return false;
        }
        //记录到用户资金日志
        $account_log_data['user_id'] = $distribution['user_id'];
        $account_log_data['user_money'] = $money;
        $account_log_data['change_time'] = NOW_TIME;
        $account_log_data['desc'] = '收回分成奖励';
        $account_log_data['order_id'] = $distribution['order_id'];
        $account_log_data['order_sn'] = getTableValue('shop_order',$distribution['order_id'],'order_sn');

        Db::startTrans();
        try{
            //打款到用户账户
            Db::name('users')->where('id',$distribution['user_id'])->setDec('forzen_distribut_money',$money);
            //记录用户资金日志表
            Db::name('user_account_log')->insert($account_log_data);
            Db::name('user_distribution')->where('id',$id)->setInc('is_take_back',1);
            // 记录到分成记录表
            $this->distribution_log($id,$money,$remark,2);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 记录分成记录
     * @param $ud_id
     * @param $money
     * @param $remark
     * @param $ior
     */
    public function distribution_log($ud_id,$money,$remark = '',$ior)
    {
        $distribution_log_data['ud_id'] = $ud_id;
        $distribution_log_data['money'] = $money;
        $distribution_log_data['ior'] = $ior;
        $distribution_log_data['remark'] = $remark;
        $distribution_log_data['time'] = NOW_TIME;
        Db::name('user_distribution_log')->insert($distribution_log_data);
    }

    /**
     * 获取已分成金额（减去已回收的金额）
     * @param $ud_id
     */
    public function getDistributionMoney($ud_id)
    {
        $where = '`ud_id` = '.$ud_id.' and `ior` = 2';
        $r_money = Db::name('user_distribution_log')->where($where)->sum('money');
        $iwhere = '`ud_id` = '.$ud_id.' and `ior` = 1';
        $i_money = Db::name('user_distribution_log')->where($iwhere)->sum('money');
        return $i_money-$r_money;
    }



    /**
     * 删除分成信息
     * @param int $id
     * @return boolean
     */
    public function delete_distribution($id)
    {
        $del = Db::name('user_distribution')->where('id',$id)->delete();
        return $del;
    }

    /**
     * 获取取现配置
     * @return array
     */
    public function enchash_config()
    {
        // 获取最近的一条配置
        $config = Db::name('user_withdrawals_config')->order('add_time desc')->find();
        // 解析json等级
        $config['level'] = json_decode($config['level'],1);
        $config['level_count'] = count($config['level']);
        return $config;
    }

    /**
     * 获取取现手续费
     * @param float $price 取现金额
     * @return float
     */
    public function getEnchashPrice($price)
    {
        $config = $this->enchash_config();
        $level = array_keys($config['level']);
        foreach ($level as $key=>$value)
        {
            $t_num = $level[$key];
            if( $t_num > $price ){
                if( $config['type'] == 2 ){
                    // 按比例计算手续费
                    $fee = $price*config['level'][$t_num]/100;
                    return $fee;
                }
                return $config['level'][$t_num];
            }
        }
        return 0;
    }

    /**
     * 获取新用户数量
     * @return int
     */
    public function getNewUserNum()
    {
        $num = Db::name('users')->where('new_user',1)->count();
        return $num;
    }


    /**
     * 获取当前user_id
     * @return string
     */
    public function getUserId()
    {
        if( empty(session('user.id')) ){
            return session_id();
        }else{
            return session('user.id');
        }
    }


    /**
     * 查找所有用户等级
     * @return array
     */
    public function selectLevel()
    {
        //查找所有用户等级
        $user_level = Db::name('user_level')->field('id,level_name')->where('trash',0)->select();
        return $user_level;
    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     */
    public function userInfo($user_id)
    {
        $user = Db::name('users')->where('id',$user_id)->find();
        return $user;
    }



    /**
     * 获取等级名称
     * @param $id
     * @return string
     */
    public function getLevelName($id)
    {
        $user_level = Db::name('user_level')->where('id',$id)->value('level_name');
        return $user_level;
    }



    /**
     * 判断是否是当前用户
     * @param $userId
     * @return mixed
     */
    public function isCurrentUser($userId)
    {
        if( empty(session('user.id')) ){
            return false;
        }
        if( $userId != session('user.id') ){
            return false;
        }
        return true;
    }



    /**
     * 通过邮箱获取用户信息
     * @param $email
     * @return mixed
     */
    public function userInfoFromEmail($email)
    {
        $user = Db::name('users')->where('transe',0)->where('email',$email)->find();
        return $user;
    }



    /**
     * 修改密码
     * @param $userId
     * @param $newPass
     * @param $reNewPass
     * @return mixed
     */
    public function updatePassword($userId,$newPass,$reNewPass)
    {
        $password = encrypt_pwd($newPass);
        if( $password !== encrypt_pwd($reNewPass) ){
            return ['code'=>0,'msg'=>'The new password is inconsistent with the confirmation password!'];
        }
        try{
            $update = Db::name('users')->where('transe',0)->where('id',$userId)->update(['password'=>$password]);
        }catch (\Exception $e) {
            Log::error($e);
            return ['code'=>0,'msg'=>'Unknown error. Please try again later!'];
        }
        return ['code'=>1];
    }


    /**
     * 获取用户收藏商品数量
     * @param $user
     * @return mixed
     */
    public function userCollectNum($user)
    {
        $num = Db::name('shop_goods_collect')->where('user_id',$user)->where('status',1)->count();
        if( empty($num) ){
            return 0;
        }
        return $num;
    }



    /**
     * 获取用户收藏商品数量
     * @param $user
     * @return mixed
     */
    public function userIsCollectGoods($user,$goods_id)
    {
        $num = Db::name('shop_goods_collect')->where(['user_id'=>$user,'goods_id'=>$goods_id,'status'=>1])->find();
        return $num;
    }



    /**
     * 获取用户订单数量
     * @param $user
     * @return mixed
     */
    public function userOrderNum($user)
    {
        $num = Db::name('shop_order')->where('user_id',$user)->where('status >= 0')->count();
        if( empty($num) ){
            return 0;
        }
        return $num;
    }



    /**
     * 用户安全验证数据
     * @param $user_id
     * @param $type [1:密码验证,2:手机验证,3:邮箱验证,4:支付密码]
     * @return boolean
     */
    public function verification($user_id,$type)
    {
        switch ($type){
            case 1:
                return $this->passwordVerfication($user_id);
                break;
            case 2:
                return $this->mobileVerfication($user_id);
                break;
            case 3:
                return $this->emailVerfication($user_id);
                break;
        }

    }

    /**
     * 获取用户密码是否验证
     * @param $user_id
     * @return mixed
     */
    public function passwordVerfication($user_id)
    {
        // 查看用户是否设置登录密码
        $pass = getTableValue('users','id='.$user_id,'password');
        if( $pass == '' || empty($pass) ){
            return false;
        }
        return true;
    }


    /**
     * 获取用户是否手机验证（绑定手机号码）
     * @param $user_id
     * @return mixed
     */
    public function mobileVerfication($user_id)
    {
        $mobileV = getTableValue('users','id='.$user_id,'mobile_validated');
        if( $mobileV == '' || empty($mobileV) || $mobileV != 1 ){
            return false;
        }
        return true;
    }


    /**
     * 获取用户是否邮箱验证（绑定邮箱地址）
     * @param $user_id
     * @return mixed
     */
    public function emailVerfication($user_id)
    {
        $emailV = getTableValue('users','id='.$user_id,'email');
        if( $emailV == '' || empty($emailV) ){
            return false;
        }
        return true;
    }


    /**
     * 获取实名认证信息
     * @param $user_id
     * @return mixed
     */
    public function realNameInfo($user_id)
    {
        // 查看用户是否实名认证
        $authentication = Db::name('user_authentication')->where('user',$user_id)->where('status <> -1')->order('add_time desc')->find();
        return $authentication;
    }


    /**
     * 隐藏真实手机号码
     * @param $mobile
     * @return integer
     */
    public function hideTrueMobile($mobile){

        // 取出前三位,后四位
        $before = substr($mobile,0,3);
        $after  = substr($mobile,7,4);

        // 拼接隐藏后的手机号码
        $hideMobile = $before.'****'.$after;
        return $hideMobile;
    }


    /**
     * 隐藏真实邮箱
     * @param $email
     * @return string
     **/
    public function hideTrueEmail($email)
    {

        // 计算邮箱名称长度
        $emailArray = explode('@', $email);
        $emailLength = strlen($emailArray[0]);

        if ($emailLength >= 0 && $emailLength <= 3) {
            // 全部隐藏
            $reEmail = str_pad('', $emailLength, '*');
        } elseif ($emailLength > 3 && $emailLength <= 5) {
            // 显示前后一个
            $before = substr($emailArray[0], 0, 1);
            $after = substr($emailArray[0], strlen($emailArray[0]) - 2, 1);
            $reEmail = $before . str_pad('', strlen($emailArray['0']) - 2, '*') . $after;
        } elseif ($emailLength > 5 && $emailLength <= 9) {
            // 显示前后两个
            $before = substr($emailArray[0], 0, 2);
            $after = substr($emailArray[0], strlen($emailArray[0]) - 3, 2);
            $reEmail = $before . str_pad('', strlen($emailArray['0']) - 4, '*') . $after;
        } elseif ($emailLength > 9 && $emailLength <= 11) {
            // 显示前后三个
            $before = substr($emailArray[0], 0, 3);
            $after = substr($emailArray[0], strlen($emailArray[0]) - 4, 3);
            $reEmail = $before . str_pad('', strlen($emailArray['0']) - 6, '*') . $after;
        } else {
            // 大于11位,显示前后4个
            $before = substr($emailArray[0], 0, 4);
            $after = substr($emailArray[0], strlen($emailArray[0]) - 5, 4);
            $reEmail = $before . str_pad('', strlen($emailArray['0']) - 8, '*') . $after;
        }
        return $reEmail . '@' . $emailArray[1];
    }


    /**
     * 隐藏真实身份证号码
     * @param $id_number
     * @return string
     */
    public function hideTrueIdNumber($id_number)
    {
        // 仅显示前4位,后三位
        $before = substr($id_number,0,4);
        $after = substr($id_number,14,3);
        $reIdNumber = $before.str_pad('',11,'*').$after;
        return $reIdNumber;
    }


    /**
     * 隐藏真实姓名
     * @param $name
     * @return string
     */
    public function hideTrueName($name)
    {
        if( mb_strlen($name) > 3 ){
            // 显示前一位,后一位
            $before = mb_substr($name,0,1);
            $after = mb_substr($name,mb_strlen($name)-2,1);
            return $before.str_pad('',mb_strlen($name)-2,'*').$after;
        }else{
            // 显示最后一位
            $after = mb_substr($name,mb_strlen($name)-2,1);
            return str_pad('',mb_strlen($name)-1,'*').$after;
        }
    }


    /**
     * 修改用户信息
     * @param $user_id
     * @param $data
     * @return bool
     */
    public function update_user($user_id,$data)
    {
        // 保存信息
        $update = Db::name('users')->where('id',$user_id)->update($data);
        if( $update === false )
        {
            return false;
        }
        return true;
    }


}