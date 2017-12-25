<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 门户模块模型控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\web\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use app\web\model\Model as ContentModel;
use app\web\model\Field as FieldModel;
use think\Db;

class Model extends AdminBase
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
            ['title',lang('model_list_title_0'),'text'],
            ['name',lang('model_list_title_1'),'text'],
            ['table',lang('model_list_title_2'),'text'],
            ['type_str',lang('model_list_title_3'),'text'],
            ['create_time',lang('model_list_title_4'),'text'],
            // ['sort',lang('model_list_title_5'),'input','Model/sort','id'],
            ['status',lang('model_list_title_6'),'switch','Model/switchs','id'],
            ['btn',lang('model_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Model/edit','id'],
            [lang('model_list_btn_field'),'frame',lang('model_list_btn_field'),'fa fa-fw fa-sitemap','','Model/field','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Model/del','id',['type','<>',0]],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Model/add'],
            [lang('model_top_btn_sys_field'),'frame',lang('model_top_btn_sys_field'),'fa fa-fw fa-sitemap','','Model/sys_field'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $models = Db::name('web_model') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $model_list = $models ->all();
        foreach ($model_list as $key => $model) {
            $type = [lang('model_type_option_sys_ab'),lang('model_type_option_usual_ab'),lang('model_type_option_single_ab')];
            $model_list[$key]['type_str'] = $type[$model['type']];
            $model_list[$key]['create_time'] = date('Y-m-d H:i:s',$model['create_time']);
        }
        $this->assign('data',$model_list);
        // 获取分页显示
        $page = $models->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    public function add()
    {
        // 页面标题
        $form['web_title'] = lang('model_form_add_title');
        $form['action'] = url('save');
        $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('model_form_field_name'),'text','name','',lang('model_form_field_name_place'),lang('model_form_field_name_tips'),[],'required'],
            [lang('model_form_field_title'),'text','title','',lang('model_form_field_title_place'),lang('model_form_field_title_tips'),[],'required'],
            [lang('model_form_field_table'),'text','table','',lang('model_form_field_table_place'),lang('model_form_field_table_tips')],
            [lang('model_form_field_type'),'radio','type','','',lang('model_form_field_tips'),$model_type],
            [lang('model_form_field_status'),'radio','status',1,'','',[lang('model_form_status_disable'),lang('model_form_status_enable')]],
            [lang('model_form_field_sort'),'text','sort','100'],
            [lang('model_form_field_fields'),'textarea','table_fields','','',lang('model_form_field_fields_tips')],
            [lang('model_form_field_func'),'textarea','handle_func','','',lang('model_form_field_func_tips')]
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;  //取得表单验证通过后的post数据
            $data['table'] = empty($data['table'])?config('database.prefix').'web_content_'.$data['name']:$data['table'];
            $data['table'] = str_replace('#@_', config('database.prefix'), $data['table']);
            if (!empty($data['table_fields'])) {
                $data_fileds = str_replace(" "," ",str_replace("\n","|",$data['table_fields']));
                $data_fileds = explode('|', $data_fileds);
                foreach ($data_fileds as $key => $field) {
                    $fd[] = explode(',', $field);                
                }
                $data['table_fields'] = json_encode($fd);
            }
            if (!empty($data['handle_func'])) {
                $handle_func = str_replace(" "," ",str_replace("\n","|",$data['handle_func']));
                $handle_func = explode('|', $handle_func);
                foreach ($handle_func as $key => $field) {
                    $func[] = explode(',', $field);                
                }
                $data['handle_func'] = json_encode($func);
            }

            // 严格验证附加表是否存在
            if (table_exist($data['table'])) {
                $this->error(lang('model_form_table_exist'));
            }

            if ($model = ContentModel::create($data)) {
                // 创建附加表
                if (false === ContentModel::createTable($model)) {
                    $this->error(lang('model_form_table_error'));
                }
                // 创建菜单节点
                $map = [
                    'module' => 'web',
                    'contet_sign'  => 'web'
                ];
                $menu_id = Db::name('auth_rule')->where($map)->value('id');
                if (empty($menu_id)) {
                    $content_top_menu = [
                        "module"    => "web",
                        'pid'       => 0,
                        'title'     => '{"zh-cn":"内容管理","en-us":"Content Management"}',
                        'level'     => 1,
                        'type'      => 1,
                        'icon'      => 'fa fa-fw fa-th-list',
                        'sort'      => 50,
                        'status'    => 1,
                        'contet_sign' => 'web'
                    ];
                    $menu_id = Db::name('auth_rule') ->insertGetId($content_top_menu);
                }
                
                $menu_data = [
                    "module"      => "web",
                    "name"        => 'web/content/index',
                    "attach"      => 'model='.$data['name'],
                    "pid"         => $menu_id,
                    "title"       => '{"zh-cn":"'.$data['title'].'"}',
                    "level"       => 2,
                    "type"        => "1",
                    "icon"        => "fa fa-fw fa-list",
                    'sort'        => 50,
                    "status"      => 1
                ];
                $model_menu_id = Db::name('auth_rule') ->insertGetId($menu_data);

                if ($model_menu_id) {
                    $sub_menu = [
                        [
                            "module"      => "web",
                            "name"        => 'web/content/add',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_btn_add').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/save',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_form_data_save').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/edit',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_btn_edit').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/update',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_form_data_update').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/del',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_btn_del').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/dels',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_btn_dels').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/switchs',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_form_data_switch').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ],[
                            "module"      => "web",
                            "name"        => 'web/content/sort',
                            "attach"      => 'model='.$data['name'],
                            "pid"         => $model_menu_id,
                            "title"       => '{"zh-cn":"'.lang('comm_form_data_sort').'"}',
                            "level"       => 3,
                            "type"        => "1",
                            "icon"        => "",
                            'sort'        => 50,
                            "status"      => 0,
                        ]
                    ];
                    Db::name('auth_rule')->insertAll($sub_menu);
                }
                // 记录行为
                sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
                $this->success(lang('comm_insert_success'));
            } else {
                 // 记录行为
                sys_log(lang('comm_insert_error'),0);  //操作结果写入系统日志
                $this->success(lang('comm_insert_error'));
            }
        }else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        } 
    }

    public function edit($id)
    {
        // 页面标题
        $form['web_title'] = lang('model_form_edit_title');
        $form['action'] = url('update');
        $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];

        $model = Db::name('web_model') ->where('id',$id) ->find();
        $model = Db::name('web_model') ->where('id',$id) ->find();
        $field_str = '';
        if (!empty($model['table_fields'])) {
            $model_fields = json_decode($model['table_fields'],true);
            foreach ($model_fields as $key => $field) {
                $field_str .= implode(',', $field).'|';
            }
            $field_str = rtrim($field_str,'|');
            $field_str = str_replace(" "," ",str_replace("|","\n",$field_str));
        }
        $handle_func = '';
        if (!empty($model['handle_func'])) {
            $model_fields = json_decode($model['handle_func'],true);
            foreach ($model_fields as $key => $field) {
                $handle_func .= implode(',', $field).'|';
            }
            $handle_func = rtrim($handle_func,'|');
            $handle_func = str_replace(" "," ",str_replace("|","\n",$handle_func));
        }
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('model_form_field_name'),'static','name',$model['name']],
            [lang('model_form_field_title'),'text','title',$model['title'],lang('model_form_field_title_place'),lang('model_form_field_title_tips'),[],'required'],
            [lang('model_form_field_table'),'static','table',$model['table']],
            [lang('model_form_field_type'),'static','type',$model_type[$model['type']]],
            [lang('model_form_field_status'),'radio','status',$model['status'],'','',[lang('model_form_status_disable'),lang('model_form_status_enable')]],
            [lang('model_form_field_fields'),'textarea','table_fields',$field_str,'',lang('model_form_field_fields_tips')],
            [lang('model_form_field_func'),'textarea','handle_func',$handle_func,'',lang('model_form_field_func_tips')]
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            unset($data['type']);
            if (!empty($data['table_fields'])) {
                $data_fileds = str_replace(" "," ",str_replace("\n","|",$data['table_fields']));
                $data_fileds = explode('|', $data_fileds);
                foreach ($data_fileds as $key => $field) {
                    $fd[] = explode(',', $field);                
                }
                $data['table_fields'] = json_encode($fd);
            }
            if (!empty($data['handle_func'])) {
                $handle_func = str_replace(" "," ",str_replace("\n","|",$data['handle_func']));
                $handle_func = explode('|', $handle_func);
                foreach ($handle_func as $key => $field) {
                    $func[] = explode(',', $field);                
                }
                $data['handle_func'] = json_encode($func);
            }
            $res = ContentModel::update($data);
            if ($res) {
                // 同时修改相关的菜单的状态
                $map = ['module'=>'web','attach'=>'model='.$data['name'],'level'=>2];
                Db::name('auth_rule') ->where($map) ->setField('status',$data['status']);
                
                // 记录行为
                sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                $this->success(lang('comm_update_success'));
            }else{
                // 记录行为
                sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
                $this->success(lang('comm_update_error'));
            }
       }else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
       } 
    }

    public function del($id)
    {
        $model = Db::name('web_model') ->where('id',$id) ->find();
        if ($model['type']==0) {
            $this->error(lang('model_del_sys_error'));
        }

         // 删除表和字段信息
        if (ContentModel::deleteTable($id)) {
            // 删除主表中的文档
            if (false === Db::name('web_content')->where('model', $id)->delete()) {
                $this->error(lang('model_del_content_error'));
            }
            // 删除菜单节点
            $map = [
                'module'    => 'web',
                'attach' => "model={$model['name']}"
            ];
            if (false === Db::name('auth_rule')->where($map)->delete()) {
                $this->error(lang('model_del_menu_error'));
            }
            // 删除字段数据
            if (false !== Db::name('web_field')->where('model', $id)->delete()) {
                $res = Db::name('web_model') ->delete($id);
                $this->success(lang('comm_delete_success'));
                
            } else {
                return $this->error(lang('model_del_field_error'));
            }
        }
    }

    public function switchs($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $model = Db::name('web_model') ->where('id',$id) ->value('name');
            $res = Db::name('web_model') ->where('id',$id) ->setField('status',$data['val']);
            if ($res!==false) {
                // 同时修改相关的菜单的状态
                $map = ['module'=>'web','attach'=>'model='.$model,'level'=>2];
                Db::name('auth_rule') ->where($map) ->setField('status',$data['val']);

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

    // ===================================================================================
    //                              字段管理
    // ===================================================================================

    public function sys_field()
    {
        $html = $this->field(0);
        return $html;
    }

    public function field($id)
    {
        // 表格页顶部提示信息
        $table['tabel_tips'] = lang('field_table_tips');
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['title',lang('field_list_title_0'),'text'],
            ['name',lang('field_list_title_1'),'text'],
            ['type',lang('field_list_title_2'),'text'],
            // ['type_str',lang('field_list_title_3'),'text'],
            ['sort',lang('field_list_title_4'),'input','Model/field_sort','id'],
            // ['table_show',lang('field_list_title_8'),'switch','Model/field_tbshow','id'],
            ['show',lang('field_list_title_5'),'switch','Model/field_show','id'],
            ['status',lang('field_list_title_6'),'switch','Model/field_switch','id'],
            ['btn',lang('field_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Model/field_edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Model/field_del','id',['model','<>',0]],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Model/field_add',['id'=>$id]],
            // [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Model/field_dels',['id'=>$id]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $fields = Db::name('web_field') ->where('model',$id) ->order(['sort'=>'ASC','id'=>'ASC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $field_list = $fields ->all();
        foreach ($field_list as $key => $field) {
            $field_list[$key]['update_time'] = date('Y-m-d H:i:s',$field['update_time']);
        }
        $this->assign('data',$field_list);
        // 获取分页显示
        $page = $fields->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    public function field_add($id)
    {
        // $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['form_tpis'] = lang('field_form_tips');   // 页面标题
        $form['action'] = url('field_save');        //表单提交的目的路径
        $field_types = tb_config('form_item_type',1,$this->lang_id);
        // dump($field_types);
        
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','model',$id],
            [lang('field_form_field_title'),'text','title','',lang('field_form_field_title_place'),lang('field_form_field_title_tips'),[],'required'],
            [lang('field_form_field_name'),'text','name','',lang('field_form_field_name_place'),lang('field_form_field_name_tips'),[],'required'],
            [lang('field_form_field_type'),'select','type','',lang('field_form_field_type_place'),lang('field_form_field_type_tips'),$field_types],         
            [lang('field_form_field_ajaxurl'),'text','ajax_url','',lang('field_form_field_ajaxurl_place'),lang('field_form_field_ajaxurl_tips')],
            [lang('field_form_field_length'),'text','length','',lang('field_form_field_length_place'),lang('field_form_field_length_tips')],
            [lang('field_form_field_isnull'),'radio','allow_null',1,'',lang('field_form_field_isnull_tips'),[lang('field_form_isnull_disable'),lang('field_form_isnull_enable')]],
            [lang('field_form_field_value'),'text','value','',lang('field_form_field_value_place'),lang('field_form_field_value_tips')],
            [lang('field_form_field_options'),'textarea','options','',lang('field_form_field_options_place'),lang('field_form_field_options_tips')],
            [lang('field_form_field_tips'),'text','tips','',lang('field_form_field_tips_place'),lang('field_form_field_tips_tips')],
            [lang('field_form_field_show'),'radio','show',1,'',lang('field_form_field_show_tips'),[lang('field_form_show_disable'),lang('field_form_show_enable')]],
            [lang('model_form_field_status'),'radio','status',1,'',lang('field_form_field_status_tips'),[lang('model_form_status_disable'),lang('model_form_status_enable')]],
            [lang('model_form_field_sort'),'text','sort','100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function field_save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $sys_field = ['id','aid','cid','uid','uname','lang','model','title','create_time','update_time','sort','status','view','trash'];
            $model = Db::name('web_model') ->where('id',$data['model']) ->find();
            if (in_array($data['name'], $sys_field)) {
                // 字段名存在于系统字段数组中
                $this->error(lang('field_sys_exists'));
            }
            $data['options'] = str_replace(" "," ",str_replace("\n",",",$data['options']));
            if ($field = FieldModel::create($data)) {
                $FieldModel = new FieldModel();
                // 添加字段
                if ($FieldModel->newField($data)) {
                    sys_log(lang('comm_insert_success'),1);  //操作结果写入系统日志
                    $this->success(lang('comm_insert_success'));
                } else {
                    // 添加失败，删除新增的数据
                    FieldModel::destroy($field['id']);
                    $this->error($FieldModel->getError());
                }
            } else {
                sys_log(lang('comm_insert_error'),1);  //操作结果写入系统日志
                $this->error(lang('comm_insert_error'));
            }
        }else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        } 
    }

    public function field_edit($id)
    {
        $form['action'] = url('field_update');      //表单提交的目的路径
        $field_types = tb_config('form_item_type',1,$this->lang_id);
        $field_info = Db::name('web_field') ->where('id',$id) ->find();
        $field_info['options'] = str_replace(" "," ",str_replace(",","\n",$field_info['options']));
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            ['','hidden','model',$field_info['model']],
            [lang('field_form_field_title'),'text','title',$field_info['title'],lang('field_form_field_title_place'),lang('field_form_field_title_tips'),[],'required'],
            [lang('field_form_field_name'),'static','name',$field_info['name'],lang('field_form_field_name_place'),lang('field_form_field_name_tips'),[],'required'],
            [lang('field_form_field_type'),'select','type',$field_info['type'],lang('field_form_field_type_place'),lang('field_form_field_type_tips'),$field_types],
            [lang('field_form_field_ajaxurl'),'text','ajax_url',$field_info['ajax_url'],lang('field_form_field_ajaxurl_place'),lang('field_form_field_ajaxurl_tips')],
            [lang('field_form_field_length'),'text','length',$field_info['length'],lang('field_form_field_length_place'),lang('field_form_field_length_tips')],
            [lang('field_form_field_isnull'),'radio','allow_null',$field_info['allow_null'],'',lang('field_form_field_isnull_tips'),[lang('field_form_isnull_disable'),lang('field_form_isnull_enable')]],
            [lang('field_form_field_value'),'text','value',$field_info['value'],lang('field_form_field_value_place'),lang('field_form_field_value_tips')],
            [lang('field_form_field_options'),'textarea','options',$field_info['options'],lang('field_form_field_options_place'),lang('field_form_field_options_tips')],
            [lang('field_form_field_tips'),'text','tips',$field_info['tips'],lang('field_form_field_tips_place'),lang('field_form_field_tips_tips')],
            [lang('field_form_field_show'),'radio','show',$field_info['show'],'',lang('field_form_field_show_tips'),[lang('field_form_show_disable'),lang('field_form_show_enable')]],
            [lang('model_form_field_status'),'radio','status',$field_info['status'],'',lang('field_form_field_status_tips'),[lang('model_form_status_disable'),lang('model_form_status_enable')]],
            [lang('model_form_field_sort'),'text','sort',$field_info['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function field_update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;       
            $data['options'] = str_replace(" "," ",str_replace("\n",",",$data['options']));
            // 更新字段信息
            $FieldModel = new FieldModel();
            if ($FieldModel->updateField($data)) {
                if ($FieldModel->isUpdate(true)->save($data)) {
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('comm_update_success'));
                }
            }
            $this->error(lang('comm_update_error'),0);
        }else{
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        } 
    }

    public function field_del($id)
    {
        $FieldModel = new FieldModel();
        $field      = $FieldModel->where('id', $id)->find();
        if ($field['model']==0) {
            $this->error(lang('field_del_sys_error'));
        }
        if ($FieldModel->deleteField($field)) {
            if ($FieldModel->where('id', $id)->delete()) {
                sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
                $this->success(lang('comm_delete_success'));
            }
        }
        sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
        $this->error(lang('comm_delete_error'));
    }

    public function field_sort($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('web_field') ->where('id',$id) ->setField('sort',$data['field']);
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

    public function field_tbshow($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('web_field') ->where('id',$id) ->setField('table_show',$data['val']);
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

    public function field_show($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('web_field') ->where('id',$id) ->setField('show',$data['val']);
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

    public function field_switch($id)
    {
        if (request()->isPost()) {
            $data = request()->post();
            $res = Db::name('web_field') ->where('id',$id) ->setField('status',$data['val']);
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