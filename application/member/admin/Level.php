<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 会员模块会员等级控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\member\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Level extends AdminBase
{

	protected function _initialize()
    {
    	parent::_initialize();
    }

    public function lists()
    {
    	return $this->fetch();
    }

    public function add()
    {
    	return $this->fetch();
    }

    public function save()
    {
    	if (request()->isPost()) {
    		#code
       	}else{
    		#code
       	} 
    }

    public function edit($id)
    {
    	return $this->fetch();
    }

    public function update($id)
    {
    	
    }

    public function del($id)
    {
    	
    }

    public function dels()
    {
    	
    }

    public function setval()
    {
    	
    }
}
?>