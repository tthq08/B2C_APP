<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 门户模块内容控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\member\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Content extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index($model)
    {
        // 是否显示表格的选择列？
        $table['show_check'] = 1;

        $model_info = Db::name('user_model') ->where('name',$model) ->find();
        if (!empty($model_info['handle_func'])) {
            $actions = json_decode($model_info['handle_func'],true);
            $action_arr = [];
            foreach ($actions as $key => $act) {
                $act_arr = explode(':', $act[0]);
                $action_arr[$act_arr[0]] = $act_arr[1];
            }
        }

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['title',lang('content_list_title_0'),'text'],
                ['cate',lang('content_list_title_1'),'text'],
                ['view',lang('content_list_title_2'),'text'],
                ['uname',lang('content_list_title_3'),'text'],
                ['update_time',lang('content_list_title_4'),'text'],
                ['sort',lang('content_list_title_5'),'input','content/sort',$model.'_id'],
                ['status',lang('content_list_title_6'),'switch','content/switchs',$model.'_id'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];            
        }

        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $act_edit = isset($action_arr['edit'])?$action_arr['edit']:'Content/edit';
        $act_del = isset($action_arr['del'])?$action_arr['del']:'Content/del';
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal',$act_edit,$model.'_id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger',$act_del,$model.'_id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $act_add = isset($action_arr['add'])?$action_arr['add']:'Content/add';
        $act_dels = isset($action_arr['dels'])?$action_arr['dels']:'Content/dels';

        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal',$act_add,['model'=>$model]],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger',$act_dels,['model'=>$model]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        if ($model_info['type']==2) {
            $dbContent = Db::name(str_replace(config('database.prefix'), '', $model_info['table']));
        } else {
            $dbContent = Db::name('user_content');
        }
        
        $conten = $dbContent ->where(['model'=>$model_info['id'],'trash'=>0]) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有记录
        $content_list = $conten ->all();
        foreach ($content_list as $key => $con) {
            // $content_list[$key]['cate'] = getCateInfo($con['cid'],'name');
        }
        $this->assign('data',$content_list);
        // 获取分页显示
        $page = $conten->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    public function main()
    {
        
    }

    public function add($model)
    {

        $model_info = Db::name('user_model') ->where('name',$model) ->find();
        $dbField = Db::name('user_field');
        if ($model_info['type']==2) {
            $where['model'] = $model_info['id'];
        }else{
            $where['model'] = ['in', [0, $model_info['id']]];
        }
        $where['status'] = 1;
        $where['show'] = 1;
        $fields = $dbField ->where($where) ->order(['sort'=>"ASC",'id'=>'ASC']) ->select();
        // dump($fields);

        $actionUrl = empty($model_info['save_func'])?url('save'):url($model_info['save_func']);
        $form['action'] = $actionUrl;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题
        $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];  
        $option_type = ['select','radio','checkbox'];
        $form_field = [['','hidden','model',$model_info['id']]];
        foreach ($fields as $key => $field) {
            $required = $field['allow_null']!=1?'required':'';
            $options = [];
            if (in_array($field['type'], $option_type)) {
                if (!empty($field['options'])) {
                    $options = optStr2Arr($field['options']);
                }
            }
            if ($field['type']=='linkage') {
                $options = $field['ajax_url'];
            }
            if ($field['type']=='linkselect') {
                $options = $field['ajax_url'];
            }
            // dump($options);
            $form_field[] = [
                $field['title'],$field['type'],$field['name'],$field['value'],'',$field['tips'],$options,$required
            ];
        }
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_field);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (isset($data['fileList'])) {
                unset($data['fileList']);
            }
            if (isset($data['fileselect'])) {
                unset($data['fileselect']);
            }
            if (isset($data['content'])) {
                $data['content'] = htmlspecialchars($data['content']);
            }
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $model_info = Db::name('user_model') ->where('id',$data['model']) ->find();
            $model_table = str_replace(config('database.prefix'), '', $model_info['table']);
            if ($model_info['type']==2) {       //独立模型
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $data[$key] = implode(',', $val);
                    }
                }
                $res = Db::name($model_table) ->insert($data);
            } else {
                $sys_where = ['model'=>0,'status'=>1];
                $sys_field = Db::name('user_field') ->where($sys_where) ->column('name');
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $val = implode(',', $val);
                    }
                    if (in_array($key, $sys_field)) {
                        $base_data[$key] = $val;
                    } else {
                        $attach_data[$key] = $val;
                    }
                }
                // dump($attach_data);
                $result = Db::name('user_content') ->insertGetId($base_data);
                if ($result!==false) {
                    $attach_data['aid'] = $result;
                    $res = Db::name($model_table) ->insert($attach_data);
                } else {
                    $res = false;
                }           
            }

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

    public function edit()
    {
        $param = input();
        foreach ($param as $key => $value) {
            $param_arr = explode('_', $key);
            $length = count($param_arr);
            $model = '';
            for ($i=0; $i < $length-1; $i++) { 
                $model .= $param_arr[$i].'_';
            }
            $model = rtrim($model,'_');
            $id = $value;
            break;
        }
        $model_info = Db::name('user_model') ->where('name',$model) ->find();
        $dbContent = str_replace(config('database.prefix'), '', $model_info['table']);
        $dbField = Db::name('user_field');
        if ($model_info['type']==2) {
            $content = Db::name($dbContent) ->where('id',$id) ->find();
            $where['model'] = $model_info['id'];
        }else{
            $content_base = Db::name('user_content') ->where('id',$id) ->find();
            $content_attach = Db::name($dbContent) ->where('aid',$id) ->find();
            $content = array_merge($content_base,$content_attach);
            $where['model'] = ['in', [0, $model_info['id']]];
        }

        // dump($content);
        $where['status'] = 1;
        $where['show'] = 1;
        $fields = Db::name('user_field') ->where($where) ->order(['sort'=>"ASC",'id'=>'ASC']) ->select();
        // dump($fields);

        $actionUrl = empty($model_info['update_func'])?url('update'):url($model_info['update_func']);
        $form['action'] = $actionUrl;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题
        $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];  
        $option_type = ['select','radio','checkbox'];
        $form_field = [['','hidden','model',$model_info['id']],['','hidden','id',$id]];

        foreach ($fields as $key => $field) {
            $required = empty($field['allow_null'])?'':'required';
            $options = [];
            if (!empty($field['type'])) {
                if (in_array($field['type'], $option_type)) {
                    if (!empty($field['options'])) {
                        $options = optStr2Arr($field['options']);
                    }
                }
                if ($field['type']=='linkage') {
                    $options = $field['ajax_url'];
                }
                if ($field['type']=='linkselect') {
                    $options = $field['ajax_url'];
                    // $content[$field['name']] = getCateLine($content[$field['name']]);
                }
            }
            // dump($options);
            $title = empty($field['title'])?'':$field['title'];
            $form_field[] = [
                $title,$field['type'],$field['name'],$content[$field['name']],'',$field['tips'],$options,$required
            ];
        }
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_field);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }


    public function update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (isset($data['content'])) {
                $data['content'] = htmlspecialchars($data['content']);
            }
            $id = $data['id'];
            unset($data['fileList'],$data['fileselect'],$data['id']);
            $model_info = Db::name('user_model') ->where('id',$data['model']) ->find();
            $model_table = str_replace(config('database.prefix'), '', $model_info['table']);
            if ($model_info['type']==2) {       //独立模型
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $data[$key] = implode(',', $val);
                    }
                }
                $res = Db::name($model_table) ->where('id',$id) ->update($data);
            } else {
                $sys_where = ['model'=>0,'status'=>1];
                $sys_field = Db::name('user_field') ->where($sys_where) ->column('name');
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $val = implode(',', $val);
                    }
                    if (in_array($key, $sys_field)) {
                        $base_data[$key] = $val;
                    } else {
                        $attach_data[$key] = $val;
                    }
                }
                // dump($attach_data);
                // $base_data['create_time'] = date('Y-m-d H:i:s');
                $base_data['update_time'] = date('Y-m-d H:i:s');
                $result = Db::name('user_content') ->where('id',$id) ->update($base_data);
                if ($result!==false) {
                    $res = Db::name($model_table) ->where('aid',$id) ->update($attach_data);
                } else {
                    $res = false;
                }           
            }

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

    public function del()
    {
        $data = input();
        foreach ($data as $key => $value) {
            $param_arr = explode('_', $key);
            if (count($param_arr)>1) {
                $length = count($param_arr);
                $model = '';
                for ($i=0; $i < $length-1; $i++) { 
                    $model .= $param_arr[$i].'_';
                }
                $model = rtrim($model,'_');
                $id = $value;
            }
        }
        $model_info = Db::name('user_model') ->where('name',$model) ->find();
        $dbContent = $model_info['type'] == 2?str_replace(config('database.prefix'), '', $model_info['table']):'user_content';
        $dbContent = Db::name($dbContent);
        $res = $dbContent ->where('id',$id) ->setField('trash',1);

        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function dels($model)
    {
        $data = input();
        $ids =  implode(',', $data['id']);
        $model_info = Db::name('user_model') ->where('name',$model) ->find();
        $dbContent = $model_info['type'] == 2?str_replace(config('database.prefix'), '', $model_info['table']):'user_content';
        $dbContent = Db::name($dbContent);
        $res = $dbContent ->where('id','in',$ids) ->setField('trash',1);

        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function switchs()
    {
        if (request()->isPost()) {
            $data = input();
            foreach ($data as $key => $value) {
                $param_arr = explode('_', $key);
                if (count($param_arr)>1) {
                    $length = count($param_arr);
                    $model = '';
                    for ($i=0; $i < $length-1; $i++) { 
                        $model .= $param_arr[$i].'_';
                    }
                    $model = rtrim($model,'_');             
                    $id = $value;
                }
            }
            $model_info = Db::name('user_model') ->where('name',$model) ->find();
            $dbContent = $model_info['type'] == 2?str_replace(config('database.prefix'), '', $model_info['table']):'user_content';
            $dbContent = Db::name($dbContent);
            $res = $dbContent ->where('id',$id) ->setField('status',$data['val']);

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

    public function sort()
    {
        if (request()->isPost()) {
            $data = input();
            foreach ($data as $key => $value) {
                $param_arr = explode('_', $key);
                if (count($param_arr)>1) {
                    $length = count($param_arr);
                    $model = '';
                    for ($i=0; $i < $length-1; $i++) { 
                        $model .= $param_arr[$i].'_';
                    }
                    $model = rtrim($model,'_');
                    $id = $value;
                }
            }
            $model_info = Db::name('user_model') ->where('name',$model) ->find();
            $dbContent = $model_info['type'] == 2?str_replace(config('database.prefix'), '', $model_info['table']):'user_content';
            $dbContent = Db::name($dbContent);
            $res = $dbContent ->where('id',$id) ->setField('sort',$data['field']);

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

    public function setvalue()
    {
        if (request()->isPost()) {
            $data = input();
            foreach ($data as $key => $value) {
                $param_arr = explode('_', $key);
                if (count($param_arr)>1) {
                    $length = count($param_arr);
                    $model = '';
                    for ($i=0; $i < $length-1; $i++) { 
                        $model .= $param_arr[$i].'_';
                    }
                    $model = rtrim($model,'_');
                    $id = $value;
                }
            }
            $model_info = Db::name('user_model') ->where('name',$model) ->find();
            $dbContent = $model_info['type'] == 2?str_replace(config('database.prefix'), '', $model_info['table']):'user_content';
            $dbContent = Db::name($dbContent);
            $res = $dbContent ->where('id',$id) ->setField($data['field_name'],$data['field']);

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
?>