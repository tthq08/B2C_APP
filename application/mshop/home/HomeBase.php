<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 前台公用基础控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\mshop\home;

use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use think\Request;
use app\mshop\api\Jssdk;

/**
 * 前台公用基础控制器
 * Class HomeBase
 * @package app\Sys\controller
 */
class HomeBase extends Controller
{
    public $session_id;
	public $wexConfing;

	public function _initialize()
    {
    	if(!session_id()){
    	    session_start();
        }
        //获取购物车数量
    	$this->session_id = session_id();
    	$lang = cookie('think_var');
    	$this -> assign('lang',$lang);
    	$this -> showNav();
        $user = session('user');
        $this->assign('uname',$user['nickname']);
        // 显示当前用户购物车数量
        // $cart_num = Db::name('shop_cart') ->where(['user_id'=>$user['id']])->count('*') ->select();
        if (!empty($user['id'])) {
            $where['user_id'] = $user['id'];
        } else {
            $where['session_id'] = $this->session_id;
        }
       
        $cart_num = Db::name('shop_cart') ->where($where) ->sum('goods_num');
        
        $this->assign('cart_num',$cart_num);

    }

    // 递归获取3极以内分类树结构
    protected function getSubCate($id)
    {
    	$cates = Db::name('goods_category') ->where(['pid'=>$id,'is_show'=>1]) ->order(['sort'=>'ASC','id'=>'ASC']) ->cache(true) ->select();
    	if ($cates) {
	    	foreach ($cates as $key => $cat) {
	    		if ($cat['level']<3) {
    				$cates[$key]['sub'] = $this -> getSubCate($cat['id']);
	    		}
	    	}
    	}
    	return $cates;
    }

    // 显示自定义导航
    protected function showNav()
    {
    	$navs = Db::name('shop_nav') ->where('is_phone',1) ->order(['sort'=>'ASC','id'=>'DESC']) ->select();
    	$this->assign('shopNav',$navs);
    }

}