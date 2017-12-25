<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 官网模块前台用户控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\web\home;

use app\web\home\HomeBase;
use think\Db;

class User extends HomeBase

{
	public function _initialize()
    {
    	// $this->assign('active','user');
    	parent::_initialize();
    	$user = session('user');
        if (empty($user)) {
    		$this->error('请登录',url('/'));
    	}
    	$this->assign('user',$user);
    }

    public function index()
    {
    	$this->assign('active','order');
    	//查询用户的所有订单
        $orderList = Db::name('shop_order')->where('user_id',session('user.id'))->where('status>=0')->order('add_time desc')->select();
        $this->assign('orderList',$orderList);
    	return $this->fetch();
    }

    public function ping()
    {
    	echo '功能开发中...';
    }

    public function collection()
    {
    	$this->assign('active','collection');

    	$model = Db::name('shop_goods_collect');
        $collectList = $model->where('user_id',session('user.id'))->where('status',1)->select();
        // $this->assign('page',$collectList->render());
        $this->assign('goods_collect_list',$collectList);
    	return $this->fetch();
    }

    public function relation()
    {
    	$this->assign('active','relation');
    	return $this->fetch();
    }

    public function user_sign()
    {
    	$this->assign('active','user_sign');
    	$user_id = session('user.id');
    	   	
    	
    	// 取当月所有日期
    	$days = $this->getMonthDays();
    	$days_group = [9,20,31,35];
    	foreach ($days as $key => $day) {
    		$is_sign = Db::name('user_sign') ->where(['user_id'=>$user_id,'sign_date'=>$day]) ->find();
    		$is_sign = !$is_sign?false:true;
    		$monthDay = [
    			'title' => '第'.($key+1).'天',
    			'date' => $day,
    			'is_sign' => $is_sign,
    			'give'=>10
    		]; 
    		$monthSign[] = $monthDay;
    	}
    	for ($i=0; $i < count($days_group); $i++) { 
	    	foreach ($monthSign as $k => $sign) {
	    		if ($k<=$days_group[$i]-1) {
	    			$sign_group[$i][] = $monthSign[$k];
	    			unset($monthSign[$k]);
	    		}
	    	}
    	}
    	// dump($sign_group);
    	$this->assign('monthSign',$sign_group);
    	return $this->fetch();
    }

    public function address()
    {
    	$this->assign('active','address');
    	//获取用户收货地址
        $addressList = Db::name('user_address')->where('user_id',session('user.id'))->where('status',1)->select();
        $this->assign('lists',$addressList);
        $province = $this->getAddressList(0);
        $this->assign('province',$province);
    	return $this->fetch();
    }

    /**
     * 获取子地址，默认为0
     */
    public function getAddressList($parent_id = 0){
        $addressList = Db::name('region') ->where(['parent_id'=>$parent_id]) ->select();
        if( is_array( $addressList ) && count($addressList) >0 ){
            $addressHtml = '';
            foreach ($addressList as $address) {
                $addressHtml .= "<option value='{$address['id']}'>{$address['name']}</option>";
            }
        }
        return $addressHtml;
    }

    public function save_addr()
    {
        $data = input('post.');
        $data['district'] = $data['area'];
        unset($data['area']);
        $user_id = session('user.id');
        // dump($user_id);
        if (isset($data['is_default'])) {   //如果设置当前地址为默认地址，先将用户所有地址设为非默认地址
            Db::name('user_address') ->where('user_id',$user_id) ->setField('is_default',0);
        }
        $data['user_id'] = $user_id;
        $res = Db::name('user_address') ->insertGetId($data);
        if ($res!==false) {
            $this->success('收货地址新增成功');
        } else {
            $this->error('收货地址新增失败，请重试');
        }        
    }

    public function addr_default($id)
    {
        $user_id = session('user.id');
        // 先将用户所有地址设为非默认地址
        Db::name('user_address') ->where('user_id',$user_id) ->setField('is_default',0);
        $res = Db::name('user_address') ->where('id',$id) ->setField('is_default',1);
        if ($res!==false) {
            $this->success('默认地址设置成功');
        } else {
            $this->error('默认地址设置失败，请重试');
        }        
    }

    public function del_addr($id)
    {
        $res = Db::name('user_address') ->delete($id);
        if ($res!==false) {
            $this->success('地址删除成功');
        } else {
            $this->error('地址删除失败，请重试');
        }
        
    }

    public function msg()
    {
    	$this->assign('active','msg');
    	return $this->fetch();
    }

    private function getMonthDays($month = "this month", $format = "Y-m-d") {
	    $start = strtotime("first day of $month");
	    $end = strtotime("last day of $month");
	    $days = array();
	    for($i=$start;$i<=$end;$i+=24*3600) $days[] = date($format, $i);
	    return $days;
	}
}