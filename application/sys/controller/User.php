<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 用户模块主控制器
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
use think\Session;
use \think\Request;
use think\Db;

class User extends AdminBase
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

    // 用户列表
    public function lists()
    {
    	// 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['username',lang('user_list_title_0'),'text'],
            ['nickname',lang('user_list_title_1'),'text'],
            ['role',lang('user_list_title_2'),'text'],
            ['create_time',lang('user_list_title_3'),'text'],
            ['last_login_time',lang('user_list_title_4'),'text'],
            ['status',lang('user_list_title_5'),'switch','User/switchs','id'],
            ['btn',lang('user_list_title_6'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','User/edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','User/delete','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','User/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','User/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $users = Db::name('admin_user') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $userData = $users->all();
        foreach ($userData as $key => $u) {
        	$role = getUserRole($u['id']);        	
        	$userData[$key]['role'] = $role['title'];
        }

        $this->assign('data',$userData);
        // 获取分页显示
        $page = $users->render();
        $this->assign('page', $page);
    	return $this->fetch('sys@Base/table');
    }

    // 新增用户
    public function add()
    {
        $form['action'] = 'save';
        $roles = Db::name('auth_group') ->where('status',1) ->column('title','id');

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('user_form_head'),'img','head'],
            [lang('user_form_username'),'text','username','',lang('user_form_username_place'),lang('user_form_username_tips'),[],'required'],
            [lang('user_form_nick'),'text','nickname','',lang('user_form_nick_place'),lang('user_form_nick_tips')],
            [lang('user_form_role'),'select','role','',lang('user_form_role_place'),'',$roles,'required'],
            [lang('user_form_pass'),'password','password','',lang('user_form_pass_place'),lang('user_form_pass_tips'),[],'pass'],
            [lang('user_form_status'),'radio','status',1,'','',[lang('user_form_status_disable'),lang('user_form_status_enable')]],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    // 保存用户数据
    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $role_id = $data['role'];
            unset($data['role'],$data['fileList']);
            // 对密码进行加密
            $data['password'] = encrypt($data['password']);
            $data['create_time'] = date('Y-m-d H:i:s');
            // 检测用户是否已经存在
            $exists = Db::name('admin_user') ->where('username',$data['username']) ->find();
            if (!$exists) {
                $res = Db::name('admin_user') ->insertGetId($data);
                if ($res!==false) {
                    // 保存用户的角色信息
                    $dataRole = ['uid'=>$res,'group_id'=>$role_id];
                    $result = Db::name('auth_group_access') ->insert($dataRole);
                    sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
                    $this->success(lang('comm_insert_success'));
                } else {
                    sys_log(lang('comm_insert_error'),0);  //操作结果写入系统日志
                    $this->error(lang('comm_insert_error'));
                }
            } else {
                sys_log(lang('user_add_exists'),0);  //操作结果写入系统日志
                $this->error(lang('user_add_exists'));
            }
        }else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    // 删除用户
    public function delete($id)
    {
        $res = Db::name('admin_user') ->delete($id);
        if ($res) {
            Db::name('auth_group_access') ->where('uid',$id) ->delete();
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }


    // 批量删除用户
    public function dels($id)
    {
        $id_str = implode(',', $id);
        $res = Db::name('admin_user') ->where('id IN ('.$id_str.')') ->delete();
        if ($res) {
            Db::name('auth_group_access') ->where('uid IN ('.$id_str.')') ->delete();
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    //切换用户的状态
    public function switchs($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('admin_user') ->where('id',$id) ->setField('status',$data['val']);
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

    // 编辑用户
    public function edit($id,$me='0')
    {
        if ($me==1) {
            $this->assign('target','parent');
            $form['action'] = url('update_me');
        } else {
            $form['action'] = url('update');
        }
        
        $roles = Db::name('auth_group') ->where('status',1) ->column('title','id');
        $user = Db::name('admin_user') ->find($id);
        $user['role'] = getUserRole($id)['id'];
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('user_form_head'),'img','head',$user['head']],
            [lang('user_form_username'),'static','username',$user['username'],lang('user_form_username_place'),lang('user_form_username_tips')],
            [lang('user_form_nick'),'text','nickname',$user['nickname'],lang('user_form_nick_place'),lang('user_form_nick_tips')],
            [lang('user_form_role'),'select','role',$user['role'],lang('user_form_role_place'),'',$roles],
            [lang('user_form_pass'),'password','password','',lang('user_form_pass_place'),lang('user_form_pass_tips')],
            [lang('user_form_status'),'radio','status',1,$user['status'],'',[lang('user_form_status_disable'),lang('user_form_status_enable')]],
        ];
        if (session('admin_id')==$id) {
            $form_fields[4] = ['','hidden','role',$user['role']];
        }
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    // 更新用户信息
    public function update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $role_id = $data['role'];
            $id = $data['id'];
            unset($data['id'],$data['role'],$data['fileList']);

            // 如果没有设置密码则表示不修改密码
            if (!empty($data['password'])) {
                // 对密码进行加密
                $data['password'] =  encrypt($data['password']);
            }else{
                unset($data['password']);
            }

            $res = Db::name('admin_user') ->where('id',$id) ->update($data);
            if ($res!==false) {
                // 保存用户的角色信息
                $result = Db::name('auth_group_access') ->where('uid',$id) ->setField('group_id',$role_id);
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

    // 更新当前用户信息
    public function update_me()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $role_id = $data['role'];
            $id = $data['id'];
            unset($data['id'],$data['role'],$data['fileList']);

            // 如果没有设置密码则表示不修改密码
            if (!empty($data['password'])) {
                // 对密码进行加密
                $data['password'] =  encrypt($data['password']);
            }else{
                unset($data['password']);
            }

            $res = Db::name('admin_user') ->where('id',$id) ->update($data);
            if ($res!==false) {
                // 保存用户的角色信息
                $result = Db::name('auth_group_access') ->where('uid',$id) ->setField('group_id',$role_id);
                $userInfo = Db::name('admin_user') ->where('id',$id) ->find();
                // 更新session中的用户信息
                Session::set('admin_name', $userInfo['username']);
                Session::set('admin_head', $data['head']);
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