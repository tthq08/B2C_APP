<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 官网模块前台主控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\web\home;

use app\shop\api\Pieces;
use app\web\home\HomeBase;
use plugins\shipping\Shipping;
use think\Db;
class Index extends HomeBase
{
	public function _initialize()
    {
    	parent::_initialize();
    }

    public function index()
    {
    	$this->assign('active','home');
        return $this->fetch();
    }

    public function cate($id)
    {
        $data = input();
    	$cate = Db::name('web_cate') ->find($id);
        $this->assign('cate',$cate);
        $active = Db::name('web_cate') ->where('id','in',$cate['pid_path']) ->column('en_name','id');
        $active[] = $cate['en_name'];
    	$this->assign('active',$active);
        $model = Db::name('web_model') ->where('id',$cate['model']) ->find();
        $this->assign('model',$model['name']);
        $model_table = str_replace(config('database.prefix'), '', $model['table']);
        if ($cate['is_index']==1 && !isset($data['a'])) {
            $tpl = empty($cate['index_template'])?'cate_index':$cate['index_template'];
            $this->assign('model_table',$model_table);
            $this->assign('model',$model);
        } else {
        	if ( $cate['cat_type'] == 0 ) {		// 单页栏目
        		$tpl = empty($cate['detail_template'])?'cate_detail':$cate['detail_template'];
        		$web_info = Db::name($model_table) ->where('cid',$cate['id']) ->find();
        		$this->assign('content',$web_info);
        	} else {		// 列表栏目
                $tpl = empty($cate['list_template'])?'cate_list':$cate['list_template'];
                
        		if ($model['type']!=2) {	//非独立模块，取content基础表
        			$base_table	=	'web_content';
                    $cont_list = Db::name($base_table) ->alias('a') ->where('FIND_IN_SET('.$cate['id'].',cat_tree)') ->join("$model_table b","b.aid=a.id") ->where(['trash'=>0]) ->paginate(9);
        		}else{
                    $cont_list = Db::name($model_table)->where('FIND_IN_SET('.$cate['id'].',cat_tree)') ->where(['trash'=>0]) ->paginate(9);
                }
                $page = $cont_list -> render();
                $this->assign('page',$page);
                $this->assign('content_list',$cont_list);
        	}
        }
        
    	return $this->fetch('public/'.$tpl);
    }

    public function cate_show($id)
    {
        $param = explode('_', $id);
        $model = $param[0];
        $id = $param[1];
        $model = Db::name('web_model') ->where('name',$model) ->find();
        $model_table = str_replace(config('database.prefix'), '', $model['table']);
        if ($model['type']==2) {
            $content = Db::name($model_table)->find($id);
        } else {
            $base_content = Db::name('web_content') ->find($id);
            $attach_content = Db::name($model_table) ->find($id);
            $content = array_merge($base_content,$attach_content);
        }
        $cate = Db::name('web_cate') ->find($content['cid']);
        $this->assign('cate',$cate);
        $active = Db::name('web_cate') ->where('id','in',$cate['pid_path']) ->column('en_name','id');
        $active[] = $cate['en_name'];
        $this->assign('active',$active);

        $tpl = empty($cate['detail_template'])?'cate_detail':$cate['detail_template'];
        // dump($content);
        $this->assign('content',$content);
        return $this->fetch('public/'.$tpl);
    }
}