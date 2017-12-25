<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/12/20
 * Time: 上午10:48
 */

namespace app\member\api;

use think\Db;

class SmsCode
{


    /**
     * 发送验证码
     * @param $mobile
     * @return mixed
     */
    public function send($mobile,$type)
    {
        $smsCode = rand(100000,999999);
        $end_time = NOW_TIME+tb_config('sms_code_time',1);
        $codeData = [
            'mobile' => $mobile,
            'session_id' => session_id(),
            'add_time' => date('Y-m-d H:i:s'),
            'end_time' => $end_time,
            'code' => $smsCode,
            'type' => $type,
        ];
        // dump($codeData);
        Db::name('admin_sms_code') ->insert($codeData);
        // 发送验证码
        $send = sendMsg(1,$mobile,$type,['code'=>$smsCode,'time_min'=>30]);
        if( $send['code'] == 1 ){
            session('vail_email',$mobile);
            session('vail_email_code',$smsCode);
            session('vail_email_time',time()+1800);
            return true;
        }

        return $send;
    }



    /**
     * 验证
     * @param $mobile
     * @param $code
     * @return mixed
     */
    public function check($mobile,$code)
    {

        $code_serv = Db::name('admin_sms_code')->where(['mobile' => $mobile, 'is_used' => '0'])->where('end_time', '>', time())->order(['id' => 'DESC'])->find();

        // 不论验证码是否验证通过，均将该验证码弃用
        Db::name('admin_sms_code')->where('id', $code_serv['id'])->setField('is_used', 1);
        if (empty($code_serv) || $code != $code_serv['code']) {
            return false;
        } else {
            return true;
        }
    }

}