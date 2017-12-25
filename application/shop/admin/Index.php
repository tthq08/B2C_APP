<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块后台主控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
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

    // 根据ID获取商户相关信息
    // @param $id  商户ID
    // @param $field  欲获取信息的字段，如为空，则获取所有信息
    public static function getCustname($id,$field='')
    {
        if (empty($field)) {
            $res = Db::name('cust') ->where('id',$id) ->find();
        } else {
            $res = Db::name('cust') ->where('id',$id) ->value($field);
        }
        return $res;
    }

    public function getCustList()
    {
        $custs = Db::name('cust') ->where(['status'=>1,'transh'=>0]) ->column('id,cust_name','id');
        return ['code'=>1,'data'=>$custs];
    }
}
