<?php
namespace app\member\mobile;

use think\Controller;
use think\Request;
use think\Db;

class Homebase extends Controller
{

	public function _initialize()
	{
	    if( empty(session('user.id')) ){
	        $this->redirect('login/index');
        }
        $this->assign('user',session('user'));
        // 输出当前页面的Action
        $action = request()->action();
        $this->assign('curr_act',$action);

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


}