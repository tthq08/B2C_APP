<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/7/6
 * Time: 16:11
 */

namespace app\member\mobile;

use think\Db;

class Withdrawals extends Homebase
{
    /**
     * 申请提现页面
     */
    public function withdrawals()
    {

        // 确认提现方式： 1：支付宝，2：银行卡
        $type = empty(request()->param('type')) ? 1: request()->param('type');
        if( $type > 2 || $type < 1 ){
            $type = 1;
        }
        $withdraw = Db::name('user_withdrawals_type')->where('user',session('user.id'))->find();
        $user_money = getTableValue('users','id='.session('user.id'),'user_money');
        $this->assign('user_money',$user_money);
        if( $type == 1 ){
            // 获取用户绑定的支付宝
            $alipay = '';
            if( !empty($withdraw['alipay']) ){
                $alipay = $withdraw['alipay'];
            }
            $user_name = $withdraw['alipay_rename'];
            $user_name = mb_substr($user_name,-1,1);
            $this->assign('user_name',$user_name);
            $this->assign('alipay',$alipay);
            return $this->fetch('w_ali');
        }
        $card = '';
        if( !empty($withdraw['card']) ){
            $card = $withdraw['card'];
        }
        $user_name = $withdraw['card_rename'];
        $user_name = mb_substr($user_name,-1,1);
        $this->assign('user_name',$user_name);
        $this->assign('card_rename',$withdraw['card_rename']);
        $this->assign('card',$card);

        $bank_deposit = $withdraw['card_deposit'];
        $this->assign('bank_deposit',$bank_deposit);
        return $this->fetch('w_card');
    }


    /**
     * 处理提现申请
     */
    public function withdrawals_do()
    {
        // 查看用户是否实名认证
//        if( session('user.identity_certificate') == 0 ){
//            return $this->error('请先通过实名认证后再申请提现哦！');
//        }

        $postData = request()->post();
        if( empty($postData['type']) || $postData['type'] < 1 || $postData['type'] > 2){
            $this->error('请先选择提现地址！');
        }
        $rule = [
            'money' => 'require',
            'type' => 'require|number',
        ];
        $validate = new Validate($rule);
        $check = $validate->check($postData);
        if( $check == false ){
            $this->error($validate->getError());
        }
        $postData['money'] = number_format($postData['money'],2);
        $postData['money'] = str_replace(',','',$postData['money']);
        if( empty($postData['money']) || $postData['money'] <= 0 ){
            $this->error('请输入正确的提现金额!');
        }
        // 获取用户金额
        $userInfo = Db::name('users')->where('id',session('user.id'))->find();

        if( $postData['money'] > $userInfo['user_money'] ){
            $this->error('提现金额不可大于当前金额，请重新输入!');
        }
        // 获取用户提现地址
        $withdrawals = Db::name('user_withdrawals_type')->where('user',session('user.id'))->find();
        if( $postData['type'] == 1 ){
            $insertData['type'] = 1;
            $insertData['account'] = $withdrawals['alipay'];
            $insertData['rename'] = $withdrawals['alipay_rename'];
        }else{
            $insertData['type'] = 2;
            $insertData['account'] = $withdrawals['card'];
            $insertData['account_name'] = $withdrawals['card_name'];
            $insertData['rename'] = $withdrawals['card_rename'];
        }
        // 获取提现手续费
        $insertData['fee'] = api('member','User','getEnchashPrice',[$postData['money']]);
        // 保存数据
        $insertData['money'] = $postData['money'];
        $insertData['user_id'] = session('user.id');
        $insertData['create_time'] = NOW_TIME;
        $insertData['status'] = 0;
        Db::startTrans();
        try{
            // 修改用户金额
            Db::name('users')->where('id',session('user.id'))->setDec('user_money',$insertData['money']);
            Db::name('users')->where('id',session('user.id'))->setInc('frozen_money',$insertData['money']);
            // 插入提现申请
            Db::name('user_withdrawals')->insert($insertData);
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
        $this->success('提现申请提交成功!',U('mshop/user/withdraw_success'));
    }



    /**
     * 添加支付宝
     */
    public function alipay_edit()
    {
        // 获取用户已经绑定的支付宝
        $alipay = getTableValue('user_withdrawals_type','user='.session('user.id'),'alipay');
        $alipay_rename = getTableValue('user_withdrawals_type','user='.session('user.id'),'alipay_rename');
        $this->assign('alipay',$alipay);
        $this->assign('rename',$alipay_rename);
        return $this->fetch();
    }

    /**
     * 添加银行卡
     */
    public function card_edit()
    {
        // 获取用户已经绑定的支付宝
        $cardInfo = Db::name('user_withdrawals_type')->where('user',session('user.id'))->field('card,card_name,card_rename,card_deposit')->find();
        $this->assign('card',$cardInfo);
        return $this->fetch();
    }


    /**
     * 保存提现支付宝账号
     */
    public function savealipay()
    {
        $postData = request()->post();
        $rule = [
            'alipay' => 'require|token',
            'alipay_rename' => 'require',
        ];
        $validate = new Validate($rule);
        $check = $validate->check($postData);
        if( $check ===false ){
            $this->error($validate->getError());
        }
        // 保存数据
        $id = getTableValue('user_withdrawals_type','user='.session('user.id'),'id');
        $data['alipay'] = $postData['alipay'];
        $data['alipay_rename'] = $postData['alipay_rename'];
        $data['alipay_time'] = NOW_TIME;
        try{
            if( !empty($id) && $id > 0 ){
                // 修改
                Db::name('user_withdrawals_type')->where('id',$id)->update($data);
            }else{
                // 添加
                $data['user'] = session('user.id');
                Db::name('user_withdrawals_type')->insert($data);
            }
        }catch (\Exception $exception)
        {
            $this->error($exception->getMessage());
        }
        $this->success('保存成功');
    }


    /**
     * 保存提现银行卡
     */
    public function savecard()
    {
        $postData = request()->post();
        $rule = [
            'card' => 'require|token',
            'card_deposit' => 'require',
            'card_name' => 'require',
            'card_rename' => 'require',
        ];
        $validate = new Validate($rule);
        $check = $validate->check($postData);
        if( $check ===false ){
            $this->error($validate->getError());
        }
        // 保存数据
        $id = getTableValue('user_withdrawals_type','user='.session('user.id'),'id');
        $data['card'] = $postData['card'];
        $data['card_rename'] = $postData['card_rename'];
        $data['card_deposit'] = $postData['card_deposit'];
        $data['card_name'] = $postData['card_name'];
        $data['card_time'] = NOW_TIME;
        try{
            if( !empty($id) && $id > 0 ){
                // 修改
                Db::name('user_withdrawals_type')->where('id',$id)->update($data);
            }else{
                // 添加
                $data['user'] = session('user.id');
                Db::name('user_withdrawals_type')->insert($data);
            }
        }catch (\Exception $exception)
        {
            $this->error($exception->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * 申请成功页面
     */
    public function withdraw_success()
    {
        // 获取用户最近的一条提现记录
        $withdrawals = Db::name('user_withdrawals')->where('user_id',session('user.id'))->order('id desc')->find();
        if( $withdrawals['type'] == 1 ){
            $withdrawals['type_name'] = '支付宝';
        }else{
            $withdrawals['type_name'] = $withdrawals['account_name'];
        }
        $this->assign('w',$withdrawals);
        return $this->fetch();
    }


    /**
     * 提现状态
     */
    public function withdraw_status()
    {
        // 获取最近的一条提现记录
        $withdrawals = Db::name('user_withdrawals')->where('user_id',session('user.id'))->order('id desc')->find();
        if( $withdrawals['type'] == 1 ){
            $withdrawals['type_name'] = '支付宝';
        }else{
            $withdrawals['type_name'] = $withdrawals['account_name'];
        }
        // 获取提现状态文字
        if( $withdrawals['status'] == 0 ){
            $withdrawals['status_str'] = '申请中';
        }elseif ( $withdrawals['status'] == 1 ){
            $withdrawals['status_str'] = '提现成功';
        }elseif ( $withdrawals['status'] == 2 ){
            $withdrawals['status_str'] = '提现失败';
        }
        $this->assign('w',$withdrawals);
        return $this->fetch();
    }

    function _empty()
    {
        $this->redirect('withdrawals');
    }

}