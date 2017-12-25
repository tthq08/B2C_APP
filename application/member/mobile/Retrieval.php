<?php
/**
 * 找回密码
 * User: iconblog
 * Date: 2017/10/26
 * Time: 上午9:21
 */

namespace app\member\mobile;


class Retrieval extends Homebase
{

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        cookie('login_referer','/');
    }

    /**
     * 找回密码
     * @return mixed
     */
    public function index()
    {
        if( !empty(request()->post('email')) ){
            $rule = ['email'=>'require|email'];
            $message = ['email.require'=>'Please enter your email account!','email.email'=>'Your email account is wrong!'];
            $check = api('sys','Verification','valiCheck',[$rule,request()->post(),$message]);
            if( $check['code'] == 0 ){
                $this->error($check['error']);
            }
            $email = request()->post('email');
            $emailExist = api('member','User','userInfoFromEmail',$email);
            if( empty($emailExist) ){
                $this->error('The mailbox address does not exist!');
            }
            // 发送验证码
            $code = rand(123456,987654);
            $send = sendMsg(0,$email,'varify_email',['code'=>$code,'time_min'=>30]);
            if( $send['code'] == 1 ){
                session('vail_email',$email);
                session('vail_email_code',$code);
                session('vail_email_time',time()+1800);
                $this->redirect('step2');
            }
            $this->error($send['msg']);
        }
        $this->clearCheckData();
        return $this->fetch();
    }


    /**
     * 提交邮箱,发送验证数据
     * @return mixed
     */
    public function step2()
    {
        if( !empty(session('vail_email_time')) || session('vail_email_time') > time() ){
            session('vail_email_time',time()+180);
            if( empty(request()->post('code')) ){
                $this->assign('email',session('vail_email'));
                return $this->fetch();
            }
            $code = request()->post('code');
            if( $code == session('vail_email_code') ){
                // 跳转到修改密码
                session('vail_email_checked',true);
                $this->redirect('step3');
            }else{
                $this->error('Verification code error!');
            }
        }else{
            $this->clearCheckData();
            $this->redirect('index');
        }
    }


    /**
     * 修改密码
     * 提交验证码数据,修改密码
     * @return mixed
     */
    public function step3()
    {
        if( empty(session('vail_email')) ){
            $this->redirect('index');
        }
        if( empty(session('vail_email_checked'))){
            $this->redirect('step2');
        }
        if( session('vail_email_time') < time() ){
            $this->error('The authentication has expired. Please re operate','index');
        }
        if( empty(request()->isPost()) ){
            return $this->fetch();
        }
        $rule = ['newPass'=>'require','reNewPass'=>'require'];
        $message = ['newPass.require'=>'Please enter a new password!','reNewPass.require'=>'Please enter the confirmation password!'];
        $check = api('sys','Verification','valiCheck',[$rule,request()->post(),$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        $data = request()->post();
        if( encrypt_pwd($data['newPass']) !== encrypt_pwd($data['reNewPass']) ){
            $this->error('The new password is inconsistent with the confirmation password!');
        }
        $user = api('member','User','userInfoFromEmail', [session('vail_email')]);
        // 执行修改操作
        $updatePass = api('member','User','updatePassword',[$user['id'],$data['newPass'],$data['reNewPass']]);
        if( $updatePass['code'] == 1 ){
            $this->redirect('step4');
        }
        $this->error($updatePass['msg']);
    }


    /**
     * 修改密码,返回修改成功
     * @return mixed
     */
    public function step4()
    {
        if( session('vail_email_time') < time() ){
            $this->redirect('index');
        }
        $this->clearCheckData();
        return $this->fetch();
    }


    private function clearCheckData()
    {
        session('vail_email_time',null);
        session('vail_email_code',null);
    }

}