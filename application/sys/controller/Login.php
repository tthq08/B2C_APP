<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 登录控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\sys\controller;

use think\Config;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use geetest\GeetestLib;

class Login extends Controller
{
    public function index()
    {
        if (session('admin_id')) {
            $this->redirect('sys/index/index');
        }
        $lang = cookie('think_var');
        $lang_info = Db::name('lang')->where('lang', $lang)->find();

        $this->assign('lang', $lang_info['lang']);
        $this->assign('lang_id', $lang_info['id']);
        $this->assign('lang_now', $lang_info['title']);
        $lang_list = Db::name('lang')->where('status', 1)->select();
        $this->assign('lang_list', $lang_list);
        $this->assign('valid_open', tb_config('captcha_signin', 1, $lang_info['id']));
        $this->assign('valid_type', tb_config('sys_valid_type', 1, $lang_info['id']));
        return $this->fetch('');
    }

    /**
     * 登录验证
     * @return string
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $lang = cookie('think_var');
            $lang_info = Db::name('lang')->where('lang', $lang)->find();

            // 过滤非必要参数，防止SQL注入
            $data = $this->request->only(['username', 'password', 'code', 'NECaptchaValidate', 'geetest_challenge', 'geetest_validate', 'geetest_seccode']);

            $where['username'] = $data['username'];
            $where['password'] = encrypt($data['password']);
            $admin_user = Db::name('admin_user')->field('id,head,username,status')->where($where)->find();
            if (!empty($admin_user)) {
                if ($admin_user['status'] != 1) {
                    $this->error(lang('user_is_disable'));
                } else {
                    Session::set('admin_id', $admin_user['id']);
                    Session::set('admin_name', $admin_user['username']);
                    Session::set('admin_head', $admin_user['head']);
                    Db::name('admin_user')->update(
                        [
                            'last_login_time' => date('Y-m-d H:i:s', NOW_TIME),
                            'last_login_ip' => $this->request->ip(),
                            'id' => $admin_user['id']
                        ]
                    );
                    sys_log(lang('login_success'), 1);  //操作结果写入系统日志
                    $this->success(lang('login_success'), 'Sys/index/index');
                }
            } else {
                sys_log(lang('user_is_error'), 0, $data['username']);  //操作结果写入系统日志
                $this->error(lang('user_is_error'));
            }
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        sys_log(lang('logout_success'), 0);  //操作结果写入系统日志
        Session::delete('admin_id');
        Session::delete('admin_name');
        $this->success(lang('logout_success'), 'sys/login/index');
    }

}