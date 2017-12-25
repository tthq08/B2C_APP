<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 前台公用基础控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\shop\home;

use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use think\Request;

/**
 * 前台公用基础控制器
 * Class HomeBase
 * @package app\Sys\controller
 */
class HomeBase extends Controller
{
	public $session_id;

	public function _initialize()
    {
    	if(!session_id()){
    	    session_start();
        }
//        if (isMobile()) {
//            $this->redirect(url('mshop/'.request()->controller().'/'.request()->action()));
//        }
    	$this->session_id = session_id();
    	$lang = cookie('think_var');
    	$this->assign('lang',$lang);
    }


}