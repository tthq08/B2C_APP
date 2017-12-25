<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 前台公用基础控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\shop\mobile;

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
    	$this -> showGoodsCateTree();
    	$this -> showNav();
        $this -> showCate();
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
       
        // dump($cart_num[0]['num']);
        //如果是微信公众号
        /*if ( strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ) {
            // echo '微信';die;empty(cookie('wechat')) &&
            $wexConfing = include EXTEND_PATH."plugins/payment/weixin/config.php";// 找到微信支付插件的配置
            $wexConfing = $wexConfing['config'];
            foreach ($wexConfing as $key => $config) {
                $config_value[$config['name']] = $config['value'];
            }
            $this->wexConfing = $config_value;
            if (empty(cookie('wechat')) && request()->action()!='mpuserlogin') {
                // 取得微信用户信息
                $callback = urlencode(url('shop/Index/mpuserlogin','','',true));
                $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$config_value['appid'].'&redirect_uri='.$callback.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
                 // dump($url);die;
                $this->redirect($url);
            }

            // JSSDK 获取用户当前位置
            $jssdkApi = new Jssdk(tb_config('wex_appid',1,getLang()),tb_config('wex_secret',1,getLang()));
            $sdkInfo = $jssdkApi->getSignature();
            $this->assign('sdk',$sdkInfo);
        }*/
    }

    // 显示商品分类数据树
    protected function showGoodsCateTree()
    {
    	$cateTree = $this -> getSubCate(0);
    	$this->assign('cateTree',$cateTree);
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

    /*============================================tos===========================================*/
    // 显示栏目导航
    protected function showCate()
    {
        $sCate = Db::name('goods_category') ->where(['pid'=>0,'is_show'=>1]) ->order(['id'=>'ASC']) ->select();
        $get_cid = Request::instance()->param('id');
        $get_cids = Request::instance()->param('cid');
        session('cid',$get_cid);
        $this->assign('get_cid',$get_cid);
        $this->assign('get_cids',$get_cids);
        $this->assign('sCate',$sCate);
    }    
}