<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 前台会员登录模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\member\mobile;

use think\Controller;
use think\Request;
use app\member\home\Homebase;
use think\Db;
use think\Session;

class Login extends Controller
{
	//set model
	protected $model;
	//set _initialie
	public function _initialize()
	{
		parent::_initialize();
		// $domain = Request::instance()->domain();
		// if( $domain == 'http://biz.m.com' ){
			//model name
			$this->model = Db::name('users');
		// }else{
		// 	//return error
		// 	return $this->error( error_msg(14011),'index/index' );
		// }
		//check user login status
	}

	/**
	 * 用户登录
	 * @return mixed
	 */
	public function index()
	{

		if( !empty(session('user.id')) ){
            $login_referer = empty(cookie('login_referer'))?'/':cookie('login_referer');
			$this->redirect($login_referer);
		}
		if( empty(cookie('login_referer')) ){
            $login_referer = empty($_SERVER['HTTP_REFERER'])?'/':$_SERVER['HTTP_REFERER'];
            cookie('login_referer',$login_referer);
        }

		return $this->fetch();		
	}

    /**
     * 用户注册页面
     * @return mixed
     */
    public function reg()
    {
        if( !empty(session('user.id')) ){
            $login_referer = empty(cookie('login_referer'))?'/':cookie('login_referer');
            $this->redirect($login_referer);
        }
        return $this->fetch();
    }

	/**
	 * dologin method
	 * @param post
	 * @return mixed
	 */
	public function dologin()
	{
		$loginAccount = trim(input('username'));
		if( empty($loginAccount) ){
            $this->error( error_msg(14003) );
		};
		//login_pass
		$loginPassTrue = trim(input('password'));
		//encrypt password
		$loginPass = encrypt_pwd($loginPassTrue);
		if( $loginPass == '' ){
            return $this->error( '请输入密码' );
        }
		$user = $this->model->where("password='{$loginPass}'")->where("`email`='{$loginAccount}' or `mobile`='{$loginAccount}' or `username`='{$loginAccount}' ")->find();

		if( !empty( $user['id'] )) {

			//check user lock status
			if( $user['status'] == 2 ){
                $this->error( error_msg(14004) );
			}
			//update token
			$user['token'] = create_token();
			//插入信息token,将购物车中所有sessionID为当前用户的商品移动到当前用户下

            Db::startTrans();
            try{
                Db::name('shop_cart')->where('session_id',session_id())->update(['user_id'=>$user['id'],'session_id'=>0]);
                Db::name('users')->where('id',$user['id'])->update(['token'=>$user['token'],'last_login'=> NOW_TIME]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            // 检测是否记住密码
            if( !empty( request()->param('remember') && intval(request()->param('remember')) == 1 )){
                //创建cookie
                cookie('username',$loginAccount);
                cookie('password',$loginPassTrue);
                cookie('remember',request()->param('remember'));
            }else{
                cookie('username',null);
                cookie('password',null);
                cookie('remember',null);
            }

			session('user',$user);
			$successData = '登录成功';
			$login_referer = cookie('login_referer');
			$login_referer = empty($login_referer)?'/':$login_referer;
			cookie('login_referer',null);
			if( request()->isAjax() ){
                return $this->success($successData,$login_referer);
            }
			return $this->redirect($login_referer);
		} else {
			return $this->error( '登录失败，请检测用户名或密码是否输入正确' );
		}
	}


	/**
	 * register method
	 * @param $request
	 * @return mixed
	 */
	public function register(Request $request)
	{
		$this->isPost($request);
		//set postData
		$postData = input();
        $rule = [
            'username' => '/^[a-zA-Z]+\w*$/',
            'nickname' => 'require',
            'mobile'=>'require|number|length:11',
            'password'=>'require',
            'repassword' => 'require',
            'code' => 'require',
        ];

        $checkData = api('sys','Verification','valiCheck',[$rule,$postData]);
        if( $checkData['code'] == 0 )
        {
            $this->error($checkData['error']);
        }

		$is_sms_valid = tb_config('web_regis_sms_enable',1);
		if ($is_sms_valid==1) {
			// 验证手机验证码是否正确
			$check_sms_code = $this->check_sms_code($postData['mobile'],$postData['code']);
			if (!$check_sms_code) {
				$this->error('手机验证码验证失败，请重试');
			}
		}

        $insertData = [];
		$user_exist = false;
		//user registration mode : email or mobile
		if( !empty($postData['email']) ){
			$insertData['email'] = $postData['email'] ? $postData['email'] : '';
			$user_exist = Db::name('users') ->where('email',$postData['email']) ->find();
		}elseif( !empty($postData['mobile']) ){
			$insertData['mobile'] = $postData['mobile'] ? $postData['mobile'] : '';
			$user_exist = Db::name('users') ->where('mobile',$postData['mobile']) ->find();
		}else{
            $this->error( error_msg(14008) ,'index/index');
		}
		if ($user_exist) {
			$this->error('当前手机号已经注册过，请更换手机号注册。');
		}

		$insertData['nickname'] = trim($postData['nickname']);
		$insertData['password'] = trim($postData['password']);
		$insertData['password'] = encrypt_pwd($insertData['password']);
        $insertData['username'] = trim($postData['username']);
        $insertData['reg_time'] = NOW_TIME;
        $insertData['sysid'] = NOW_TIME;
		$insertData['status'] = 1;

		// insert user data
        try{
            $user_id = Db::name('users')->insertGetId($insertData);
            // 保存用户的关系树信息
            if ($user_id !== false) {
            	if (isset($user_tree)) {
					$treeData = [
						'uid' => $user_id,
						'pid' => $user_tree['uid'],
						'tree_path_id' => $user_tree['tree_path_id'].','.$user_tree['uid']
					];
				}else{
					$treeData = [
						'uid' => $user_id,
						'pid' => 0,
						'tree_path_id' => 0
					];
				}
				Db::name('users_tree') ->insert($treeData);
            }
        }catch (\Exception $e){
            $this->error( $e->getMessage(),'member/login/reg' );
        }

        $this->success( error_msg(14009),'member/login/index');
	}

	public function reset_pwd()
	{
		if (request()->isPost()){
			$data = input();
	        $is_sms_valid = tb_config('web_regis_sms_enable',1);
	        if ($is_sms_valid==1) {
	            // 验证手机验证码是否正确
	            $check_sms_code = $this->check_sms_code($data['mobile'],$data['code']);
	            if (!$check_sms_code) {
	                $this->error('手机验证码验证失败，请重试');
	            }
	        }

	        $user = Db::name('users') ->where('email',$data['email']) ->find();
	        if (!$user) {
	            $this->error('用户不存在，请先注册。');
	        } else {
	            $res = Db::name('users') ->where('id',$user['id']) ->setField('password',encrypt_pwd($data['pass']));
	            if ($res !== false) {
	                $this->success('重置成功。');
	            } else {
	                $this->error('密码重置失败，请重试');
	            }                
	        }
		}else{
			$this->error('参数错误');
		}
	}

	public function logout()
	{
		// dump($_SERVER['HTTP_REFERER']);die;
		Session::delete('user');
        $login_referer = cookie('login_referer');
        $login_referer = empty($login_referer)?'/':$login_referer;
        cookie('login_referer',null);
        $this->redirect($login_referer,$login_referer);
	}

    public function send_sms_reg_code()
    {
        $data = input('post.');
        if( empty($data['mobile']) ){
            $this->error('请输入手机号码');
        }elseif( empty($data['code']) ){
            $this->error('请输入图形验证码');
        }
        // 验证手机号码正确性
        $mobile = $data['mobile'];
        $checkM = '/^1[3578]\d{9}$/';
        if( !preg_match($checkM,$mobile) ){
            $this->error('手机号码格式错误');
        }
        if(!captcha_check($data['code'])){
            $this->error('图形验证码验证失败，请重试');
        };
        $sess_id = session_id();
        $mins = tb_config('sms_code_time',1)/60;
//		$the_last = Db::name('admin_sms_code') ->where(['mobile'=>$data['mobile'],'session_id'=>$sess_id,'is_used'=>'0']) ->where('end_time','>', NOW_TIME) ->find();
//		if ($the_last) {
//			$this->error('验证码请求过于频繁，请'.$mins.'分钟后再试。');
//		}
        $smsCode = rand(100000,999999);
        $end_time = NOW_TIME+tb_config('sms_code_time',1);
        $codeData = [
            'mobile' => $data['mobile'],
            'session_id' => $sess_id,
            'add_time' => date('Y-m-d H:i:s'),
            'end_time' => $end_time,
            'code' => $smsCode
        ];
        // dump($codeData);
        $res = Db::name('admin_sms_code') ->insert($codeData);
        if ($res!==false) {
            //$res = sendSMS($data['mobile'],$sms);
            $res = true;
            if ($res===true) {
                $this->success('验证码发送成功，请注意查收。'.$smsCode);
            } else {
                $this->error($res);
            }
        } else {
            $this->error('验证码发送失败，请重试');
        }
    }

	public function check_sms_code($mobile='',$code='')
	{		
		$sess_id = session_id();
		$code_serv = Db::name('admin_sms_code') ->where(['mobile'=>$mobile,'session_id'=>$sess_id,'is_used'=>'0']) ->where('end_time','>', NOW_TIME) ->order(['id'=>'DESC']) ->find();
		// 不论验证码是否验证通过，均将该验证码弃用
		if (empty($code_serv) || $code!=$code_serv['code']) {
			return false;
		}else{
            Db::name('admin_sms_code') ->where('id',$code_serv['id']) ->setField('is_used',1);
			return true;
		}
	}

	// 第三方登录回调函数
	public function app_login()
	{
		$data = input('');
	}


	public function _empty()
    {
        $this->redirect('index');
    }


    /**
     * isPost : check is not post data
     */
    protected function isPost($request)
    {
        if(!request()->isPost()){
            //error data
            $error_data = 'Illegal operation!!';
            //redirect
            return $this->error( $error_data ,'index/index');
        }
    }


    // 发送动态验证码邮件
    public function send_email_code($act = '')
    {
        $data = input('post.');
        if( empty($data['email']) ){
            $this->error('Please enter the mailbox!');
        }elseif( empty($data['code']) ){
            $this->error('Please enter the graphic verification code!');
        }
        // 验证手机号码正确性
        $mail = $data['email'];
        $checkM = '/^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/';

        if( !preg_match($checkM,$mail) ){
            $this->error('Mailbox format error!');
        }
        if(!captcha_check($data['code'])){
            $this->error('Graphic verification code verification failed, please try again!');
        };

        $code = rand(100000,999999);
        $code_arr = ['mail'=>$mail,'code'=>$code,'use_time'=>time()+tb_config('mail_code_time',1)];
        session('mail_code',$code_arr);     //session中保存邮件验证码相关信息，包含收件地址，验证码及有效截止时间
        $time_min = tb_config('mail_code_time',1)/60;
        $act = empty($act) ? 'varify_email' : $act;
        if ($act == 'change') {
            $res = sendMsg(0,$mail,'change_email',['code'=>$code,'time_min'=>$time_min]);
        } else {
            $res = sendMsg(0, $mail, 'varify_email', ['code' => $code, 'time_min' => $time_min]);
        }
        // $res = sendMail($mail,$mail_title,$mail_model);
        if ($res) {
            $this->success('Identifying code send success!');
        } else {
            $this->error('Identifying code send failed!');
        }

    }
}