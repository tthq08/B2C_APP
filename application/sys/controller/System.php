<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 系统设置控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\common\JunCreater\JCreater;
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
class System extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();
    }

    // 系统设置
    public function index($group='base')
    {
         // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (isset(tb_config('config_group',1,$this->lang_id)[$group])) {
                // 查询该分组下所有的配置项名和类型
                $items = Db::name('admin_config')->where('group', $group)->where('status', 1)->column('name,type');
                foreach ($items as $name => $type) {
                    if (!isset($data[$name])) {
                        switch ($type) {
                            // 开关
                            case 'switch':
                                $data[$name] = 0;
                                break;
                            case 'checkbox':
                                $data[$name] = '';
                                break;
                        }                        
                    } else {
                        // 如果值是数组则转换成字符串，适用于复选框等类型
                        if (is_array($data[$name])) {
                            $data[$name] = implode(',', $data[$name]);
                        }
                        switch ($type) {
                            // 开关
                            case 'switch':
                                $data[$name] = 1;
                                break;
                            // 日期时间
                            case 'date':
                            case 'time':
                            case 'datetime':
                                $data[$name] = strtotime($data[$name]);
                                break;
                            case 'array':
                                $data[$name] = str_replace(" "," ",str_replace("\n",",",$data[$name])); 
                                break;
                        }
                    }
                    // dump($name.':'.$data[$name]);
                    $res = Db::name('admin_config')->where('name', $name)->update(['value' => $data[$name]]);
                    if($res===false){
                        break;
                    }
                }
                // dump($res);
                if ($res!==false) {
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('comm_update_success'));
                } else {
                    sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
                    $this->error(lang('comm_update_error'));
                }   
            }
        }else{

            // 页面标题
            // $form['web_title'] = lang('config_form_title_add');
            $form['action'] = '';

            $list_group = tb_config('config_group',1,$this->lang_id);
            $tab_list   = [];

            if (empty(input('m'))) {
                // 设置页面选项卡中的Tab列表，每个tab包含title(标题)、sign(自身标记)、url(绑定URL路径)
                foreach ($list_group as $key => $value) {
                    $tab_list[$key]['title'] = $value;
                    $tab_list[$key]['sign'] = $key;
                    $tab_list[$key]['url']   = url('index', ['group' => $key]);
                }
                $tab = JCreater::setTabNav($tab_list,$group);
                $this->assign($tab);
            }

            $con_list = Db::name('admin_config') ->where(['group'=>$group,'status'=>1,'lang'=>$this->lang_id]) ->order(['sort'=>'ASC','id'=>'ASC']) ->select();
            // $this->assign('configs',$con_list);
            // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组)]
            foreach ($con_list as $key => $con) {
                if (!empty($con['options'])) {
                    $con['options'] = optStr2Arr($con['options']);
                }
                if ($con['type'] == 'linkage' || $con['type'] == 'ajaxframe') {
                    $con['options'] = $con['ajax_url'];
                }
                if ($con['type']=='array') {
                    $con['value'] = str_replace(" "," ",str_replace(",","\n",$con['value'])); 
                }
                $tips = $con['tips']." 调用方法:<code>tb_config('{$con['name']}')</code>";
                $field = [$con['title'],$con['type'],$con['name'],$con['value'],'',$tips,$con['options']];
                $form_fields[] = $field;
            }
            if (!empty($form_fields)) {
                $JCreater = new JCreater();
                $form['form_Html'] = $JCreater->form_build($form_fields);
            }else{
                $form['form_Html'] = lang('config_list_empty_tips');
            }
            $this->assign($form);
            return $this->fetch('Base/form');
        }
    }

    // 配置项管理
    public function config($group='base')
    {
        $groups = tb_config('config_group',0,$this->lang_id);
        $list_group = $groups['value'];
        $tab_list   = [];

        if (empty(input('m'))) {
            // 设置页面选项卡中的Tab列表，每个tab包含title(标题)、sign(自身标记)、url(绑定URL路径)
            foreach ($list_group as $key => $value) {
                $tab_list[$key]['title'] = $value;
                $tab_list[$key]['sign'] = $key;
                $tab_list[$key]['url']   = url('config', ['group' => $key]);
            }
            $tab = JCreater::setTabNav($tab_list,$group);
            $this->assign($tab);
        }

        // 是否显示表格的选择列？
        $this->assign('show_check',1);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['name',lang('config_list_title_0'),'text'],
            ['title',lang('config_list_title_1'),'text'],
            ['type',lang('config_list_title_2'),'text'],
            ['status',lang('config_list_title_3'),'switch','System/conf_switch','id'],
            ['sort',lang('config_list_title_4'),'input','System/conf_sort','id'],
            ['btn',lang('config_list_title_5'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','System/conf_edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','System/conf_del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','System/conf_add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','System/conf_dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $configs = Db::name('admin_config') ->where(['group'=>$group,'lang'=>$this->lang_id]) ->order(['sort'=>'ASC','id'=>'ASC']) ->paginate(tb_config('list_rows',1,$this->lang_id));  //取出当前语言版本下的所有配置项
        $this->assign('data',$configs);
        // 获取分页显示
        $page = $configs->render();
        $this->assign('page', $page);

        return $this->fetch('config');
    }

    // 添加配置项
    public function conf_add()
    {
        // 页面标题
        $form['web_title'] = lang('config_form_title_add');
        $form['action'] = url('System/conf_save');

        $groups = tb_config('config_group',1,$this->lang_id);
        $types = tb_config('form_item_type',1,$this->lang_id);

        /*表单中显示的填写项
        填写说明：[标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组)]*/
        $form_item = [
            [lang('config_form_group'),'radio','group','','','',$groups],
            [lang('config_form_type'),'select','type','',lang('config_form_type_tips'),'',$types],
            [lang('config_form_title'),'text','title','',lang('config_form_title_place'),lang('config_form_title_tips')],
            [lang('config_form_name'),'text','name','',lang('config_form_name_place'),lang('config_form_name_tips')],
            [lang('config_form_value'),'textarea','value','',lang('config_form_value_place'),lang('config_form_value_tips')],
            [lang('config_form_ajaxurl'),'text','ajax_url','',lang('config_form_ajaxurl_place'),lang('config_form_ajaxurl_tips')],
            [lang('config_form_option'),'textarea','options','',lang('config_form_option_place'),lang('config_form_option_tips')],
            [lang('config_form_tips'),'text','tips','',lang('config_form_tips_place'),lang('config_form_tips_tips')],
            [lang('config_form_sort'),'text','sort','100',lang('config_form_sort_place'),lang('config_form_sort_tips')],
            // [lang('config_form_status'),'switch','status','1',lang('config_form_status_place'),lang('config_form_status_tips')],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_item);
        $this->assign($form);
        return $this->fetch('Base/form');
    }

    // 保存配置项数据
    public function conf_save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if ($data['type']=='array') {
                $data['value'] = str_replace(" "," ",str_replace("\n",",",$data['value'])); 
            }
            if (!empty($data['options'])) {
                $data['options'] = str_replace(" "," ",str_replace("\n",",",$data['options'])); 
            }
            $has = Db::name('admin_config') ->where('name',$data['name']) ->find();
            if ($has) {
                $this->error(lang('comm_insert_had'));
            } else {
                $res = Db::name('admin_config') ->insert($data);
                if ($res!==false) {
                    sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
                    $this->success(lang('comm_insert_success'));
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

    // 编辑配置项
    public function conf_edit($id)
    {
        // 页面标题
        $form['web_title'] = lang('config_form_title_edit');
        $form['action'] = url('System/conf_update');

        $groups = tb_config('config_group',1,$this->lang_id);
        $types = tb_config('form_item_type',1,$this->lang_id);
        $config = Db::name('admin_config') ->find($id);
        if ($config['type']=='array') {
            $value_arr = explode(',', $config['value']);
            $config['value'] = implode("\r", $value_arr);
        }
        if (!empty($config['options'])) {
            $value_arr = explode(',', $config['options']);
            $config['options'] = implode("\r", $value_arr);
        }
        /*表单中显示的填写项
        填写说明：[标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组)]*/
        $form_item = [
            [lang('config_form_group'),'radio','group',$config['group'],'','',$groups],
            [lang('config_form_type'),'select','type',$config['type'],lang('config_form_type_tips'),'',$types],
            [lang('config_form_title'),'text','title',$config['title'],lang('config_form_title_place'),lang('config_form_title_tips')],
            [lang('config_form_name'),'text','name',$config['name'],lang('config_form_name_place'),lang('config_form_name_tips')],
            [lang('config_form_value'),'textarea','value',$config['value'],lang('config_form_value_place'),lang('config_form_value_tips')],
            [lang('config_form_ajaxurl'),'text','ajax_url',$config['ajax_url'],lang('config_form_ajaxurl_place'),lang('config_form_ajaxurl_tips')],
            [lang('config_form_option'),'textarea','options',$config['options'],lang('config_form_option_place'),lang('config_form_option_tips')],
            [lang('config_form_tips'),'text','tips',$config['tips'],lang('config_form_tips_place'),lang('config_form_tips_tips')],
            [lang('config_form_sort'),'text','sort',$config['sort'],lang('config_form_sort_place'),lang('config_form_sort_tips')],
            // [lang('config_form_status'),'switch','status',$config['status'],lang('config_form_status_place'),lang('config_form_status_tips')],
            ['id','hidden','id',$config['id']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_item);
        $this->assign($form);
        return $this->fetch('Base/form');
    }

    // 更新配置项数据
    public function conf_update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if ($data['type']=='array') {
                $data['value'] = str_replace(" "," ",str_replace("\n",",",$data['value'])); 
            }
            if (!empty($data['options'])) {
                $data['options'] = str_replace(" "," ",str_replace("\n",",",$data['options'])); 
            }
            $id = $data['id'];
            unset($data['id']);
            $res = Db::name('admin_config') ->where('id',$id) ->update($data);
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

    // 删除配置项
    public function conf_del($id)
    {
        $res = Db::name('admin_config') ->delete($id);
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'),url('System/config'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        } 
    }

    // 批量删除配置项
    public function conf_dels()
    {
        $ids = input();
        foreach ($ids['id'] as $key => $ids) {
            $id[] = $key;
        }
        $id_str = implode(',', $id);
        $res = Db::name('admin_config') ->where('id IN ('.$id_str.')') ->delete();
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    // 修改配置项排序
    public function conf_sort()
    {
        if (request()->isPost()) {
            $id = input('id');
            $data = request()->post();
            $res = Db::name('admin_config') ->where('id',$id) ->setField('sort',$data['field']);
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

    // 切换配置项状态
    public function conf_switch()
    {
        if (request()->isPost()) {
            $id = input('id');
            $data = request()->post();
            $res = Db::name('admin_config') ->where('id',$id) ->setField('status',$data['val']);
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

    // ======================================================================================
    //                                    系统日志管理
    // ======================================================================================


    // 系统日志列表
    public function logs()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check',1);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['module',lang('logs_list_title_0'),'text'],
            ['controller',lang('logs_list_title_1'),'text'],
            ['action',lang('logs_list_title_2'),'text'],
            ['admin',lang('logs_list_title_3'),'text'],
            ['msg',lang('logs_list_title_4'),'text'],
            ['log_time',lang('logs_list_title_5'),'text'],
            ['btn',lang('logs_list_title_6'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $btn = [
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','System/log_del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','System/log_dels'],
            [lang('comm_btn_clear'),'confirm_form',lang('comm_clear_confirm_msg'),'fa fa-fw fa-paint-brush','layui-btn-danger','System/log_clear'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $configs = Db::name('log') ->order(['log_time'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang_id));
        $this->assign('data',$configs);
        // 获取分页显示
        $page = $configs->render();
        $this->assign('page', $page);
        
        return $this->fetch('logs');
    }

    // 删除系统日志
    public function log_del($id)
    {
        $res = Db::name('log') ->delete($id);
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }   
    }

    // 批量删除系统日志
    public function log_dels()
    {
        $ids = input();
        foreach ($ids['id'] as $key => $ids) {
            $id[] = $key;
        }
        $id_str = implode(',', $id);
        $res = Db::name('log') ->where('id IN ('.$id_str.')') ->delete();
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    // 清空系统日志
    public function log_clear()
    {
        $res = Db::name('log') ->where('id>0') ->delete();
        if ($res) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        } 
    }
}