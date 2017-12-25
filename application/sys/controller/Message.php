<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 消息中心控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\common\JunCreater\JCreater;
use think\Db;

/**
 * 后台菜单
 * Class Menu
 * @package app\Sys\controller
 */
class Message extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    // =============================================================================
    // 								场景管理
    // =============================================================================

    // 场景列表
    public function scene()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check', 1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id', 'ID', 'text'],
            ['sc_title', lang('scene_list_table_title'), 'text'],
            ['sc_key', lang('scene_list_table_key'), 'text'],
            ['create_time', lang('scene_list_table_time'), 'text'],
            ['sort', lang('scene_list_table_sort'), 'input', 'Message/scene_value', 'id'],
            ['status', lang('scene_list_table_status'), 'switch', 'Message/scene_value', 'id'],
            ['btn', lang('scene_list_table_action'), 'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名，也可设置数组设置参数对;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', 'Message/scene_edit', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Message/scene_del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Message/scene_add'],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Message/scene_dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $datas = Db::name('admin_sms_scene')->order(['sort' => 'ASC', 'id' => 'DESC'])->paginate(tb_config('list_rows', 1, $this->lang));  //取出当前语言版本下的所有配置项
        $list = $datas->all();
        foreach ($list as $key => $model) {
        }
        $this->assign('data', $list);
        // 获取分页显示
        $page = $datas->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    // 新增场景
    public function scene_add()
    {
        $form['web_title'] = lang('scene_form_window_add');   // 页面标题
        $form['action'] = url('scene_handle');        //表单提交的目的路径
        // $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('scene_form_field_title'), 'text', 'sc_title', '', lang('scene_form_field_title_place'), lang('scene_form_field_title_tips'), [], 'required'],
            [lang('scene_form_field_key'), 'text', 'sc_key', '', lang('scene_form_field_key_place'), lang('scene_form_field_key_tips'), [], 'required'],
            [lang('scene_form_field_status'), 'radio', 'status', 1, '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('scene_form_field_sort'), 'text', 'sort', '100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 编辑场景
    public function scene_edit($id)
    {
        $form['web_title'] = lang('scene_form_window_edit');   // 页面标题
        $form['action'] = url('scene_handle');        //表单提交的目的路径

        $data = Db::name('admin_sms_scene')->find($id);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['', 'hidden', 'id', $data['id']],
            [lang('scene_form_field_title'), 'text', 'sc_title', $data['sc_title'], lang('scene_form_field_title_place'), lang('scene_form_field_title_tips'), [], 'required'],
            [lang('scene_form_field_key'), 'text', 'sc_key', $data['sc_key'], lang('scene_form_field_key_place'), lang('scene_form_field_key_tips'), [], 'required'],
            [lang('scene_form_field_status'), 'radio', 'status', $data['status'], '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('scene_form_field_sort'), 'text', 'sort', $data['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 场景数据处理
    public function scene_handle()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (isset($data['id'])) {        //如果参数中有id,则为编辑，否则为新增
                $res = Db::name('admin_sms_scene')->update($data);
            } else {
                $data['create_time'] = date('Y-m-d H:i:s');
                $is_exist = Db::name('admin_sms_scene')->where('sc_key', $data['sc_key'])->find();
                if (!empty($is_exist)) {
                    $this->error(lang('scene_form_add_exist'));
                }
                $res = Db::name('admin_sms_scene')->insert($data);
            }
            if ($res !== false) {
                sys_log(lang('scene_form_handle_success'), 1);  //操作结果写入系统日志
                $this->success(lang('scene_form_handle_success'));
            } else {
                sys_log(lang('scene_form_handle_error'), 0);  //操作结果写入系统日志
                $this->error(lang('scene_form_handle_error'));
            }

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    // 删除场景
    public function scene_del($id = 0)
    {
        $is_models = Db::name('admin_sms_model')->where(['scene' => $id, 'transh' => 0])->find();
        if ($is_models) {
            $this->error(lang('scene_form_del_model_exist'));
        } else {
            $res = Db::name('admin_sms_scene')->delete($id);
            if ($res !== false) {
                sys_log(lang('comm_delete_success'), 1);  //操作结果写入系统日志
                $this->success(lang('comm_delete_success'));
            } else {
                sys_log(lang('comm_delete_error'), 0);  //操作结果写入系统日志
                $this->error(lang('comm_delete_error'));
            }
        }
    }

    // 删除场景
    public function scene_dels($id = 0)
    {

        $res = Db::name('admin_sms_scene')->where('id','in',$id)->delete();
        if ($res !== false) {
            sys_log(lang('comm_delete_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }

    }

    // 设置场景内容值
    public function scene_value($id = 0)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (isset($data['field_name'])) {
                $field = $data['field_name'];
                $value = $data['field'];
            } else {
                $field = $data['field'];
                $value = $data['val'];
            }

            $res = Db::name('admin_sms_scene')->where(['id' => $id])->setField($field, $value);
            if ($res !== false) {
                sys_log(lang('scene_form_handle_success'), 1);  //操作结果写入系统日志
                $this->success(lang('scene_form_handle_success'));
            } else {
                sys_log(lang('scene_form_handle_error'), 0);  //操作结果写入系统日志
                $this->error(lang('scene_form_handle_error'));
            }
        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    // ajax获取场景列表
    public function ajaxGetScene($id = 0)
    {
        $scenes = Db::name('admin_sms_scene')->where(['status' => 1, 'trash' => 0])->column('sc_title', 'sc_key');
        return ['code' => 1, 'msg' => '获取成功', 'data' => $scenes];
    }



    // =====================================================================================
    // 									短信模板管理
    // =====================================================================================

    // 消息模板列表
    public function models()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check', 1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id', 'ID', 'text'],
            ['title', lang('model_list_table_title'), 'text'],
            ['scene', lang('model_list_table_scene'), 'text'],
            ['md_title', lang('model_list_table_md_title'), 'text'],
            ['types', lang('model_list_table_types'), 'text'],
            ['sort', lang('model_list_table_sort'), 'input', 'Message/model_value', 'id'],
            ['status', lang('model_list_table_status'), 'switch', 'Message/model_value', 'id'],
            ['btn', lang('model_list_table_action'), 'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名，也可设置数组设置参数对;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', 'Message/model_edit', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Message/model_del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Message/model_add'],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Message/model_del'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $datas = Db::name('admin_sms_model')->order(['sort' => 'ASC', 'id' => 'DESC'])->paginate(tb_config('list_rows', 1, $this->lang));  //取出当前语言版本下的所有配置项
        $list = $datas->all();
        $types = getMsgPlugins(true);
        foreach ($list as $key => $model) {
            $list[$key]['scene'] = getTableValue('admin_sms_scene', ['sc_key' => $model['scene']], 'sc_title');
            $tps = explode(',', $model['types']);
            $show = [];
            foreach ($tps as $k => $val) {
                $show[] = $types[$val];
            }
            $list[$key]['types'] = implode(',', $show);
        }
        $this->assign('data', $list);
        // 获取分页显示
        $page = $datas->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
        return $this->fetch();
    }

    public function model_add()
    {
        $form['web_title'] = lang('model_form_window_add');   // 页面标题
        $form['action'] = url('model_handle');        //表单提交的目的路径
        $scenes = Db::name('admin_sms_scene')->where(['status' => 1, 'trash' => 0])->column('sc_title', 'sc_key');
        $types = getMsgPlugins(true);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('model_form_field_title'), 'text', 'title', '', lang('model_form_field_title_place'), lang('model_form_field_title_tips'), [], 'required'],
            [lang('model_form_field_scene'), 'select', 'scene', '', '', '', $scenes],
            [lang('model_form_field_types'), 'checkbox', 'types', [], '', lang('model_form_field_types_tips'), $types],
            [lang('model_form_field_mbtitle'), 'text', 'md_title', '', lang('model_form_field_mbtitle_place'), lang('model_form_field_mbtitle_tips')],
            [lang('model_form_field_content'), 'textarea', 'content', '', '', lang('model_form_field_content_tips')],
            [lang('scene_form_field_status'), 'radio', 'status', 1, '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('scene_form_field_sort'), 'text', 'sort', '100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function model_edit($id)
    {
        $form['web_title'] = lang('model_form_window_edit');   // 页面标题
        $form['action'] = url('model_handle');        //表单提交的目的路径
        $scenes = Db::name('admin_sms_scene')->where(['status' => 1, 'trash' => 0])->column('sc_title', 'sc_key');
        $types = getMsgPlugins(true);
        $data = Db::name('admin_sms_model')->find($id);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $data['types'] = explode(',', $data['types']);
        foreach ($data['types'] as $key => $item) {
            $data['types'][$key] = ucfirst($item);
        }
        $form_fields = [
            ['', 'hidden', 'id', $data['id']],
            [lang('model_form_field_title'), 'text', 'title', $data['title'], lang('model_form_field_title_place'), lang('model_form_field_title_tips'), [], 'required'],
            [lang('model_form_field_scene'), 'select', 'scene', $data['scene'], '', '', $scenes],
            [lang('model_form_field_types'), 'checkbox', 'types', $data['types'], '', lang('model_form_field_types_tips'), $types],
            [lang('model_form_field_mbtitle'), 'text', 'md_title', $data['md_title'], lang('model_form_field_mbtitle_place'), lang('model_form_field_mbtitle_tips')],
            [lang('model_form_field_content'), 'ueditor', 'content', $data['content'], '', lang('model_form_field_content_tips')],
            [lang('scene_form_field_status'), 'radio', 'status', $data['status'], '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('scene_form_field_sort'), 'text', 'sort', $data['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function model_handle()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $data['types'] = implode(',', $data['types']);

            if (isset($data['id'])) {        //如果参数中有id,则为编辑，否则为新增
                $res = Db::name('admin_sms_model')->update($data);
            } else {
                $is_exist = Db::name('admin_sms_model')->where('scene', $data['scene'])->find();
                if ($is_exist) {
                    // 同一场景限制只有一个模板
                    $this->error(lang('model_form_handle_scene_exist', [$is_exist['id']]));
                }
                $data['create_time'] = date('Y-m-d H:i:s');
                $res = Db::name('admin_sms_model')->insert($data);
            }
            if ($res !== false) {
                sys_log(lang('model_form_handle_success'), 1);  //操作结果写入系统日志
                $this->success(lang('model_form_handle_success'));
            } else {
                sys_log(lang('model_form_handle_error'), 0);  //操作结果写入系统日志
                $this->error(lang('model_form_handle_error'));
            }

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    public function model_del($id)
    {
        $res = Db::name('admin_sms_model')->where('id', 'in', $id)->delete();
        if ($res !== false) {
            sys_log(lang('model_form_del_success'), 1);  //操作结果写入系统日志
            $this->success(lang('model_form_del_success'));
        } else {
            sys_log(lang('model_form_del_error'), 0);  //操作结果写入系统日志
            $this->error(lang('model_form_del_error'));
        }

    }

    public function model_value($id = 0)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (isset($data['field_name'])) {
                $field = $data['field_name'];
                $value = $data['field'];
            } else {
                $field = $data['field'];
                $value = $data['val'];
            }

            $res = Db::name('admin_sms_model')->where(['id' => $id])->setField($field, $value);
            if ($res !== false) {
                sys_log(lang('model_form_handle_success'), 1);  //操作结果写入系统日志
                $this->success(lang('model_form_handle_success'));
            } else {
                sys_log(lang('model_form_handle_error'), 0);  //操作结果写入系统日志
                $this->error(lang('model_form_handle_error'));
            }
        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }
}