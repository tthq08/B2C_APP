<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 官网模块前台基础控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\web\home;

use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;

class HomeBase extends Controller
{
    public function _initialize()
    {
    	$user = session('user');
    	$this->assign('user',$user);
    	$cate = Db::name('web_cate') ->where('is_show',1) ->order(['sort'=>'ASC','id'=>'DESC'])->column('*','id');  //取出当前语言版本下的所有记录
        $cate = array2tree($cate);
        // dump($cate);
        $this->assign('cateTree',$cate);
    }
}