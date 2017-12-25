<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 门户模块栏目控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\web\admin;

use app\sys\controller\AdminBase;
use app\sys\controller\Comm;
use app\common\JunCreater\JCreater;
use think\Db;

class Category extends AdminBase
{
	protected function _initialize()
    {
    	parent::_initialize();
    }

    public function index()
    {
    	// 是否显示表格的选择列？
        $table['show_check'] = 1;
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id','ID','text'],
            ['icon',lang('cate_list_title_0'),'img'],
            ['name',lang('cate_list_title_1'),'text'],
            ['is_show',lang('cate_list_title_show'),'switch','Category/setval','id'],
            ['status',lang('cate_list_title_2'),'switch','Category/setval','id'],
            ['sort',lang('cate_list_field_sort'),'input','Category/setval','id'],
            ['btn',lang('cate_list_title_3'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('cate_list_btn_add'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-plus','','Category/add','id'],
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Category/edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Category/del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Category/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Category/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条

        $cate = Db::name('web_cate') ->order(['sort'=>'ASC','id'=>'DESC'])->column('*','id');  //取出当前语言版本下的所有记录
        $cate = array2tree($cate);
        $cate = tree2list($cate);
        $this->assign('expandLevel',1);
        $this->assign('data',$cate);
        return $this->fetch('sys@Base/treetable'); 
    }

    public function add($id='')
    {
    	$form['web_title'] = lang('cate_form_win_title');   // 页面标题
        $form['action'] = url('save');      //表单提交的目的路径
        $line = '0';
        if (!empty($id)) {
            $p_line = Db::name('web_cate') ->where('id',$id) ->value('pid_path');
            $line = $p_line.','.$id;
        }

        $web_model = Db::name('web_model') ->where('status',1) ->column('title','id');
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('cate_form_field_name'),'text','name','','','',[],'required'],
            [lang('cate_form_field_en_name'),'text','en_name','','','',[]],
            [lang('cate_form_model_name'),'select','model',0,'','',$web_model,'required'],
            [lang('cate_form_field_icon'),'img','icon'],
            [lang('cate_form_field_pid'),'linkselect','pid',$line,'','','Category/ajaxGetCate','required'],
            [lang('cate_form_model_type'),'radio','cat_type','','','',[lang('cate_form_model_type_single'),lang('cate_form_model_type_list'),lang('cate_form_model_type_path'),lang('cate_form_model_type_url')]],
            [lang('cate_form_field_url'),'text','cat_url','',lang('cate_form_field_url_place'),lang('cate_form_field_url_tips')],
            [lang('cate_form_field_show'),'radio','is_show',0,'','',[lang('cate_form_radio_opt_disable'),lang('cate_form_radio_opt_enable')]],
            [lang('cate_form_field_target'),'radio','target','_self','','',['_self'=>lang('cate_form_radio_target_self'),'_blank'=>lang('cate_form_radio_target_blank')]],
            [lang('model_form_field_sort'),'text','sort','100'],
            [lang('cate_form_field_index'),'radio','is_index',0,'','',[lang('cate_form_radio_opt_disable'),lang('cate_form_radio_opt_enable')]],
            [lang('cate_form_field_aliaindex'),'text','alias_index','',lang('cate_form_field_aliaindex_place'),lang('cate_form_field_aliaindex_tips')],
//            [lang('cate_form_field_alialist'),'text','alias_list','',lang('cate_form_field_alialist_place'),lang('cate_form_field_alialist_tips')],
            [lang('cate_form_index_tpl'),'linkage','index_template','','','','Category/ajaxGetTpl'],
            [lang('cate_form_list_tpl'),'linkage','list_template','','','','Category/ajaxGetTpl'],
            [lang('cate_form_detail_tpl'),'linkage','detail_template','','','','Category/ajaxGetTpl'],

            ['SEO标题','text','seo_title','','SEO标题'],
            ['SEO关键词','tags','seo_key','','SEO关键词'],
            ['SEO描述','textarea','seo_des','','SEO描述'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function save()
    {
    	if (request()->isPost()) {
            $data = $this -> post_data;
            if (!isset($data['pid'])) {
                $data['pid'] = [0,'',''];
            }
            foreach ($data['pid'] as $key => $pid) {
                if (empty($pid)) {
                    unset($data['pid'][$key]);
                }
            }
            $data['pid_path'] = '0,'.ltrim(implode(',', $data['pid']),'0,');
            $data['pid'] = end($data['pid']);
            $p_lv = Db::name('web_cate') ->where('id',$data['pid']) ->value('level');
            $p_lv = empty($p_lv)?0:$p_lv;
            $data['level'] = $p_lv+1;
            $res = Db::name('web_cate') ->insertGetId($data);
            if ($res!==false) {
                if (!empty($data['alias_index'])) {
                    $url = '/' . $data['alias_index'];
                    api('sys', 'Domain', 'diyUrlInsert', [$data['alias_index'], 'web/Index/cate?id=' . $data['id']]);
                } else {
                    $url = rurl('web/Index/cate', ['id' => $res]);
                }
                $res = Db::name('web_cate') ->where('id',$res) ->update(['url'=>$url]);

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
    	$form['web_title'] = lang('cate_form_win_title_edit');   // 页面标题
        $form['action'] = url('update');      //表单提交的目的路径

        $cate = Db::name('web_cate') ->find($id);

        $web_model = Db::name('web_model') ->where('status',1) ->column('title','id');
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('cate_form_field_name'),'text','name',$cate['name'],'','',[],'required'],
            [lang('cate_form_field_en_name'),'text','en_name',$cate['en_name'],'','',[]],
            [lang('cate_form_model_name'),'select','model',$cate['model'],'','',$web_model,'required'],
            [lang('cate_form_field_icon'),'img','icon',$cate['icon']],
            [lang('cate_form_field_pid'),'linkselect','pid',$cate['pid_path'],'','','Category/ajaxGetCate','required'],
            [lang('cate_form_model_type'),'radio','cat_type',$cate['cat_type'],'','',[lang('cate_form_model_type_single'),lang('cate_form_model_type_list'),lang('cate_form_model_type_path'),lang('cate_form_model_type_url')]],
            [lang('cate_form_field_url'),'text','cat_url',$cate['cat_url'],lang('cate_form_field_url_place'),lang('cate_form_field_url_tips')],
            [lang('cate_form_field_show'),'radio','is_show',$cate['is_show'],'','',[lang('cate_form_radio_opt_disable'),lang('cate_form_radio_opt_enable')]],
            [lang('cate_form_field_target'),'radio','target',$cate['target'],'','',['_self'=>lang('cate_form_radio_target_self'),'_blank'=>lang('cate_form_radio_target_blank')]],
            [lang('model_form_field_sort'),'text','sort',$cate['sort']],            
            [lang('cate_form_field_index'),'radio','is_index',$cate['is_index'],'','',[lang('cate_form_radio_opt_disable'),lang('cate_form_radio_opt_enable')]],
            [lang('cate_form_field_aliaindex'),'text','alias_index',$cate['alias_index'],lang('cate_form_field_aliaindex_place'),lang('cate_form_field_aliaindex_tips')],
//            [lang('cate_form_field_alialist'),'text','alias_list',$cate['alias_list'],lang('cate_form_field_alialist_place'),lang('cate_form_field_alialist_tips')],
            [lang('cate_form_index_tpl'),'linkage','index_template',$cate['index_template'],'','','Category/ajaxGetTpl'],
            [lang('cate_form_list_tpl'),'linkage','list_template',$cate['list_template'],'','','Category/ajaxGetTpl'],
            [lang('cate_form_detail_tpl'),'linkage','detail_template',$cate['detail_template'],'','','Category/ajaxGetTpl'],

            ['SEO标题','text','seo_title',$cate['seo_title'],'SEO标题'],
            ['SEO关键词','tags','seo_key',$cate['seo_key'],'SEO关键词'],
            ['SEO描述','textarea','seo_des',$cate['seo_des'],'SEO描述'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form'); 
    }

    public function update()
    {
    	if (request()->isPost()) {
            $data = $this -> post_data;
            foreach ($data['pid'] as $key => $pid) {
                if (empty($pid)) {
                    unset($data['pid'][$key]);
                }
            }
            $data['pid_path'] = '0,'.ltrim(implode(',', $data['pid']),'0,');
            $data['pid'] = end($data['pid']);
            $p_lv = Db::name('web_cate') ->where('id',$data['pid']) ->value('level');
            $data['level'] = $p_lv+1;
            $res = Db::name('web_cate') ->update($data);
            if ($res!==false) {

                if (!empty($data['alias_index'])) {
                    $url = '/' . $data['alias_index'];
                    api('sys', 'Domain', 'diyUrlInsert', [$data['alias_index'], 'web/Index/cate?id=' . $data['id']]);
                } else {
                    $url = rurl('web/Index/cate', ['id' => $data['id']]);
                }
                $res = Db::name('web_cate') ->where('id',$data['id']) ->update(['url'=>$url]);
                
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

    public function del($id)
    {
    	if(empty($id)){
            $this->error(lang('comm_delete_error'));
        }
        $res =  Db::name('web_cate') ->delete($id);
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    public function dels()
    {
    	$data = input('');
        $ids = implode(',', $data['id']);
        if(empty($ids)){
            $this->error(lang('comm_delete_error'));
        }
        $res =  Db::name('web_cate') ->where('id','IN',$ids) ->delete();
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    public function setval($id)
    {
    	$data = input('');
        if (isset($data['field']) && isset($data['val'])) {
            $field = $data['field'];
            $value = $data['val'];
        } else {
            $field = $data['field_name'];
            $value = $data['field'];
        }
        
        $res =  Db::name('web_cate') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    // ajax方式获取正常状态的栏目ID及名称
    public function ajaxGetCate($val=0)
    {
        // $cates = Db::name('web_cate')->field('id,name') ->where('status',1) ->select();
        $cates = Db::name('web_cate') ->where(['pid'=>$val,'status'=>1]) ->column('name','id');
        if ($cates) {
            return ['code'=>1,'msg'=>'success','data'=>$cates];
        } else {
            return ['code'=>0,'msg'=>'Data null','data'=>[]];
        }       
    }

    // ajax方式获取正常状态的栏目ID及名称
    public function ajaxGetTree($val=0)
    {
    	// $cates = Db::name('web_cate')->field('id,name') ->where('status',1) ->select();
    	$cates = Db::name('web_cate') ->where(['status'=>1,'pid'=>$val]) ->column('name','id');
    	if ($cates) {
    		return ['code'=>1,'msg'=>'success','data'=>$cates];
    	} else {
    		return ['code'=>0,'msg'=>'Data null','data'=>[]];
    	}    	
    }

    // ajax方式获取分类模板文件列表
    public function ajaxGetTpl($value='')
    {
        $theme_path = config('tpl_base').'/'.tb_config('web_template',1,$this->lang);
        $module = Request()->module();
        $theme_path .= '/'.$module.'/public';
        $allTpl = getFile($theme_path,false);
        foreach ($allTpl as $key => $value) {
            if (strpos($value, '__')===false) {
                $tpls[$value] = $value;
            }
        }
        return ['code'=>1,'msg'=>'success','data'=>$tpls];
    }

}
