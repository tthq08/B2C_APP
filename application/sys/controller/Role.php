<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 角色管理控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use \think\Request;
use think\Db;

class Role extends AdminBase
{
	protected function _initialize()
    {
    	parent::_initialize();
    }

    public function index()
    {
    	// 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['title',lang('role_list_title_0'),'text'],
            ['remark',lang('role_list_title_1'),'text'],
            ['add_time',lang('role_list_title_2'),'text'],
            ['status',lang('role_list_title_3'),'switch','Role/switchs','id'],
            ['btn',lang('role_list_title_4'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $btn = [
            [lang('role_btn_author'),'frame',lang('role_author_title'),'fa fa-fw fa-key','','Role/author','id'],
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Role/edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Role/del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Role/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Role/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $roles = Db::name('auth_group') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项

        $this->assign('data',$roles);
        // 获取分页显示
        $page = $roles->render();
        $this->assign('page', $page);
    	return $this->fetch('sys@Base/table');
    }

    // 新增角色
    public function add()
    {
        $form['action'] = url('save');

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('role_form_title_title'),'text','title','',lang('role_form_title_place'),'',[],'required'],
            [lang('role_form_remark_title'),'textarea','remark','',lang('role_form_remark_place')],
            [lang('role_form_status_title'),'radio','status',1,'','',[lang('comm_btn_disable'),lang('comm_btn_enable')]],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    // 保存角色信息
    public function save()
    {
       	if (request()->isPost()) { 
    		$data = $this->post_data;
    		$data['add_time'] = date('Y-m-d H:i:s');
    		$data['rules'] = '';
    		$res = Db::name('auth_group') ->insertGetId($data);
    		if ($res!==false) {
    			sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
                $this->success(lang('comm_insert_success'));
    		} else {
    			sys_log(lang('comm_insert_error'),0);  //操作结果写入系统日志
                $this->error(lang('comm_insert_error'));
    		}
    		
       	}else{
    		sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
       	} 
    }

    public function edit($id)
    {
    	$form['action'] = url('update');
        $role = Db::name('auth_group') ->where('id',$id) ->find();

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$role['id']],
            [lang('role_form_title_title'),'text','title',$role['title'],lang('role_form_title_place'),'',[],'required'],
            [lang('role_form_remark_title'),'textarea','remark',$role['remark'],lang('role_form_remark_place')],
            [lang('role_form_status_title'),'radio','status',$role['status'],'','',[lang('comm_btn_disable'),lang('comm_btn_enable')]],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    // 更新角色信息
    public function update()
    {
       	if (request()->isPost()) { 
    		$data = $this->post_data;
    		$id = $data['id'];
    		unset($data['id']);
    		$res = Db::name('auth_group') ->where('id',$id) ->update($data);
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

    // 删除角色
    public function del($id)
    {
    	$user = Db::name('auth_group_access') ->where('group_id',$id) ->find();
    	if ($user) {	//角色下仍有用户，不能删除
    		sys_log(lang('role_del_user_exists'),0);  //操作结果写入系统日志
            $this->error(lang('role_del_user_exists'));
    	} else {
	        $res = Db::name('auth_group') ->delete($id);
	        if ($res) {
	            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
	            $this->success(lang('comm_delete_success'));
	        } else {
	            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
	            $this->error(lang('comm_delete_error'));
	        }
    	}    	
    }

    public function dels($id)
    {
    	$id_str = implode(',', $id);
    	$user = Db::name('auth_group_access') ->where('group_id IN ('.$id_str.')') ->find();
    	if ($user) {	//角色下仍有用户，不能删除
    		sys_log(lang('role_del_user_exists'),0);  //操作结果写入系统日志
            $this->error(lang('role_del_user_exists'));
    	} else {
	        $res = Db::name('auth_group') ->where('id IN ('.$id_str.')') ->delete();
	        if ($res) {
	            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
	            $this->success(lang('comm_delete_success'));
	        } else {
	            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
	            $this->error(lang('comm_delete_error'));
	        }
	    }
    }

    public function switchs($id)
    {
    	if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('auth_group') ->where('id',$id) ->setField('status',$data['val']);
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

    public function author($id)
    {
    	$this->assign('rid',$id);
    	$rules_role = Db::name('auth_group') ->field('modules,rules') ->where('id',$id) ->find();
    	$this->assign('rules_role',$rules_role['rules']);
    	$this->assign('modules_role',$rules_role['modules']);
    	$module_list = Db::name('admin_module') ->where('status',1) ->select();
    	foreach ($module_list as $key => $module) {
    		$menus = getMenuTree($module['name']);
    		$module_list[$key]['menus'] = $menus;

    		//取出模块在当前语言版本下的显示标题
            $menu_title = json_decode($module['title'],true);
            $module_list[$key]['title'] = $menu_title[$this->lang];
    	}
    	$this->assign('menus',$module_list);
    	// dump($module_list);
    	return $this->fetch();
    }

   	public function auth_save()
   	{
	   	if (request()->isPost()) {
	   		$data = $this->post_data;
	   		$rules = implode(',', $data['menu_auth']);
	   		$modules = implode(',', $data['menu_module']);
	   		$role_id = $data['rid'];
	   		$res = Db::name('auth_group') ->where('id',$role_id) ->setField(['rules'=>$rules,'modules'=>$modules]);
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
}