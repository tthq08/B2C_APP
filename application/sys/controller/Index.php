<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 后台主控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\sys\controller;
use \think\Request;
use think\Db;

class Index extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $lang = Db::name('lang') ->where('status',1) ->select();
        $this->assign('lang_list',$lang);
    	return $this->fetch();
    }

    public function main()
    {
        return $this->fetch();
    }

    public function form_test()
    {
        return $this->fetch();
    }
}