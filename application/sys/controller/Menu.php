<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 后台菜单控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use think\Config;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 后台菜单
 * Class Menu
 * @package app\Sys\controller
 */
class Menu extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();
        $modules = $this->module_list;
    	foreach ($modules as $key => $mod) {
    		$menus = Db::name('auth_rule')->where(['module'=>$mod['name']])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();    		
			$menu = [];
    		foreach ($menus as $kk => $mn) {
    			//取出所有菜单在当前语言版本下的显示标题
	            $menu_title = json_decode($mn['title'],true);
	            $mn['title'] = $menu_title[$this->lang];
	            $menu[$mn['id']] = $mn;
    		}
    		if (!empty($menu)) {
    			$menu = array2tree($menu);
    			$menu = tree2list($menu);
    		} else {
    			$menu = [];
    		}
    		$modules[$key]['menus'] = $menu;
    	}
    	$this->assign('module_menu',$modules);
    }

    // 节点列表
    public function index($module='sys')
    {
        $this->assign('modu_now',$module);
    	return $this->fetch();
    }

    // 打开新增节点页面，如有pid参数，则是增加子节点
    public function add($pid = '')
    {
    	$lang = Db::name('lang') ->where('status=1') ->select();
    	$this->assign('lang',$lang);

    	if (!empty($pid)) {
    		$parent = Db::name('auth_rule') ->find($pid);
    		$title = json_decode($parent['title'],true);
    		$parent['title'] = $title[$this->lang];
    		$this->assign('parent',$parent);
    		$controller = controller('Comm')->getController($parent['module']);
    		$this->assign('controller',$controller);
    	}
    	return $this->fetch();
    }

    // 打开编辑节点页面
    public function edit($id = '')
    {
    	$lang = Db::name('lang') ->where('status=1') ->select();
    	$this->assign('lang',$lang);

    	$menu = Db::name('auth_rule') ->find($id);
    	$title = json_decode($menu['title'],true);
        foreach ($lang as $key => $val) {
            if (!isset($title[$val['lang']])) {
                $title[$val['lang']] = '';
            }
        }
		$menu['title'] = $title;
    	$this->assign('menu',$menu);
    	// 解析出当前节点的控制器与方法名
    	if (!empty($menu['name']) && $menu['type']==1) {
	    	$act = explode('/', $menu['name']);
	    	$ctr_menu = ucfirst($act[1]); //对控制器名首字母大写化
	    	$act_menu = $act[2];
	    	

			// 取出方法列表
			$action = controller('Comm') ->getAction($menu['module'],$ctr_menu);
			$action = $action['action'];
    	}else{
    		$ctr_menu = '';
    		$act_menu = '';
    		$action = [];
    	}
    	$this->assign('ctr_menu',$ctr_menu);
    	$this->assign('act_menu',$act_menu);
		$this->assign('actions',$action);

    	// 取出控制器列表
    	$controller = controller('Comm')->getController($menu['module']);
		$this->assign('controller',$controller);

		// 取出父级目录树形列表
		$parent = controller('Comm') -> getMenu($menu['module']);
		$menus = $parent['menus'];
		$this->assign('parents',$menus);

    	return $this->fetch();
    }

    // 删除节点
    public function del($id='')
    {
        $module = Db::name('auth_rule') ->where('id',$id) ->value('module');
        $tree = explode(',',$this->getMenuTreeIds($id));
        $tree = array_filter($tree);
        $tree[] = $id;
        $res = Db::name('auth_rule')->where('id','in',$tree) ->delete();
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'),url('index',['module'=>$module]));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    private function getMenuTreeIds($pid)
    {
        $id = Db::name('auth_rule') ->where('pid',$pid) ->column('id');
        $ids = implode(',',$id);
        $subIDs ='';
        foreach ($id as $key) {
            $subIDs .=$this -> getMenuTreeIds($key);
        }
        return $ids.','.$subIDs;
    }

    // 批量删除节点
    public function dels()
    {
        $ids = input();

        $m = input('m');
        $id_arr = $ids['id'.$m];
        foreach ($id_arr as $key => $ids) {
            $tree = explode(',',$this->getMenuTreeIds($key));
            $tree = array_filter($tree);
            $id[] = $key;
            foreach ($tree as $t){
                $id[] = $t;
            }
        }
        $id_str = implode(',', $id);
        $module = Db::name('auth_rule') ->where('id',$id[0]) ->value('module');
            
        $res = Db::name('auth_rule') ->where('id IN ('.$id_str.')') ->delete();
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'),url('index',['module'=>$module]));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    // 显示图标显示器
    public function showicons()
    {
    	return $this->fetch();
    }

    //保存节点数据 - 新增
    public function save()
    {
    	if (request()->isPost()) {
    		$data = $this->post_data;
    		if ($data['type']==1) {
    			if ($data['controller']=='' && $data['action']=='') {
    				$data['name'] = '';
    			}else{
	    			$data['name'] = $data['module'].'/'.$data['controller'].'/'.$data['action'];
    			}
            }
			unset($data['controller'],$data['action']);    				
            $data['title'] = json_encode($data['title']);
            $dbMenu = Db::name('auth_rule');
            $pid_lv = $dbMenu ->where('id',$data['pid']) ->value('level');
            $data['level'] = $pid_lv+1;
    		$has = $dbMenu ->where(['name'=>$data['name']]) ->find();
    		$has = $data['name']==''?false:$has;
    		if ($has) {
    			$this->error(lang('comm_insert_had'));
    		} else {
    			$res = $dbMenu ->insert($data);
    			if ($res!==false) {
                    sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
    				$this->success(lang('comm_insert_success'),url('index',['module'=>$data['module']]));
    			} else {
                    sys_log(lang('comm_insert_error'),0);  //操作结果写入系统日志
    				$this->error(lang('comm_insert_error'));
    			}    			
    		}    		
    	}else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
    		$this->error(lang('comm_request_error'));
    	}
    }

    //切换节点的状态
    public function switchs()
    {
    	if (request()->isPost()) {
    		$data = request()->post();
    		$res = Db::name('auth_rule') ->where('id',$data['id']) ->setField('status',$data['val']);
    		if ($res!==false) {
                sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
				$this->success(lang('comm_update_success'));
			} else {
                sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
				$this->error(lang('comm_update_error'));
			}    		
    	}else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
    		$this->error(lang('comm_request_error'));
    	}
    }

    //更新节点的排序
    public function sort()
    {
    	if (request()->isPost()) {
    		$data = request()->post();
    		$res = Db::name('auth_rule') ->where('id',$data['id']) ->setField('sort',$data['sort']);
    		$module = Db::name('auth_rule') ->where('id',$data['id']) ->value('module');
            if ($res!==false) {
                sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
				$this->success(lang('comm_update_success'),url('index',['module'=>$module]));
			} else {
                sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
				$this->error(lang('comm_update_error'));
			}    		
    	}else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
    		$this->error(lang('comm_request_error'));
    	}
    }

    // 更新节点数据 - 编辑
    public function update()
    {
    	if (request()->isPost()) {
    		$data = $this->post_data;
    		if ($data['type']==1) {
    			if ($data['controller']=='' && $data['action']=='') {
    				$data['name'] = '';
    			}else{
	    			$data['name'] = $data['module'].'/'.$data['controller'].'/'.$data['action'];
    			}
            }
			unset($data['controller'],$data['action']);    				
    		$data['title'] = json_encode($data['title']);
    		$dbMenu = Db::name('auth_rule');
            $pid_lv = $dbMenu ->where('id',$data['pid']) ->value('level');
            $data['level'] = $pid_lv+1;
    		// $has = $dbMenu ->where("name='{$data['name']}' and id<>{$data['id']}") ->find();
    		// $has = $data['name']==''?false:$has;
    		// if ($has) {
    		// 	$this->error(lang('comm_insert_had'));
    		// } else {
    			$res = $dbMenu ->where('id',$data['id']) ->update($data);
    			if ($res!==false) {
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
    				$this->success(lang('comm_update_success'),url('index',['module'=>$data['module']]));
    			} else {
                    sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
    				$this->error(lang('comm_update_error'));
    			}    			
    		// }    		
    	}else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
    		$this->error(lang('comm_request_error'));
    	}
    }
}