<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块属性控制器 
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

class Attribute extends AdminBase
{
	protected function _initialize()
    {
    	parent::_initialize();
    }

    public function lists()
    {
    	// 是否显示表格的选择列？
    	$this->assign('show_check',1);

    	/*设置表格的表头，表格将按顺序显示设置的表头
    	* 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
    	*/
    	$table_head = [
    		['id','ID','text'],
    		['attr_name',lang('attri_list_title_1'),'text'],    		
    		['sort',lang('attri_list_title_2'),'input','Attribute/setvalue','id'],
    		['attr_index',lang('attri_list_title_3'),'switch','Attribute/setvalue','id'],
    		['btn',lang('attri_list_title_4'),'btn'],
    	];
    	$table['tb_title'] = JCreater::table_header($table_head);

    	$btn = [
    		// [lang('attri_list_btn_item'),'frame',lang('attri_list_btn_item'),'fa fa-fw fa-tag','layui-btn-success','Attribute/items','id'],
    		[lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Attribute/edit','id'],
    		[lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Attribute/del','id'],
    	];
    	$table['btn_lst'] = JCreater::table_btn($btn);


    	// 设置列表页顶部按钮组
    	$top_btn = [
    		[lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Attribute/add'],
    		[lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Attribute/dels'],
    	];
        $url = '/shop/FeatureGroup/lists/type/1';
        $top_btn[] = [lang('comm_btn_frame_feature_group_1'),'frame',lang('comm_edit_frame_title'),'glyphicon glyphicon-th','layui-btn-normal',$url,'id'];
    	$table['top_btn'] = JCreater::table_btn($top_btn);

    	$this->assign($table);


    	// 表格数据，分页，每页10条
    	$Attis = Db::name('shop_attribute') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
    	$attri_list = $Attis ->all();

    	$this->assign('data',$attri_list);
    	// 获取分页显示
    	$page = $Attis->render();
    	$this->assign('page', $page);

    	return $this->fetch('sys@Base/table');
    }


    /**
     * 添加属性
     * @return mixed
     */
    public function add()
    {
    	$form['web_title'] = lang('Attri_form_add_title');   // 页面标题
    	$form['action'] = url('save');		//表单提交的目的路径

    	$form_fields = [
    		[lang('Attri_form_title_name'),'text','attr_name','',lang('Attri_form_title_name_place'),lang('Attri_form_title_name_tips'),[],'required'],
    		[lang('Attri_form_title_search'),'radio','attr_index',1,'',lang('Attri_form_title_search_tips'),[lang('Attri_form_option_search_disable'),lang('Attri_form_option_search_enable')]],
//    		[lang('attri_form_title_single'),'radio','attr_type',0,'',lang('attri_form_title_single_tips'),[lang('attri_form_option_single_radio'),lang('attri_form_option_single_checkbox')]],
    		[lang('attri_form_title_input'),'radio','attr_input_type',0,'',lang('attri_form_title_input_tips'),[lang('attri_form_option_input_hand'),lang('attri_form_option_input_check'),lang('attri_form_option_input_text')]],
    		[lang('attri_form_title_value'),'textarea','attr_values','','',lang('attri_form_title_value_tips')],
    		[lang('model_form_field_sort'),'text','sort','100'],
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
    		$data['attr_values'] = str_replace(" "," ",str_replace("\n",",",$data['attr_values'])); 
    		// dump($data);die;
    		$res = Db::name('shop_attribute') ->insertGetId($data);
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
    	$form['web_title'] = lang('Attri_form_edit_title');   // 页面标题
    	$form['action'] = url('update');		//表单提交的目的路径

    	$attribute = Db::name('shop_attribute') ->find($id);
    	$attribute['attr_values'] = str_replace(" "," ",str_replace(",","\n",$attribute['attr_values']));
    	// [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
    	$form_fields = [
    		['','hidden','id',$id],
    		[lang('Attri_form_title_name'),'text','attr_name',$attribute['attr_name'],lang('Attri_form_title_name_place'),lang('Attri_form_title_name_tips'),[],'required'],
    		[lang('Attri_form_title_search'),'radio','attr_index',$attribute['attr_index'],'',lang('Attri_form_title_search_tips'),[lang('Attri_form_option_search_disable'),lang('Attri_form_option_search_enable')]],
//    		[lang('attri_form_title_single'),'radio','attr_type',$attribute['attr_type'],'',lang('attri_form_title_single_tips'),[lang('attri_form_option_single_radio'),lang('attri_form_option_single_checkbox')]],
    		[lang('attri_form_title_input'),'radio','attr_input_type',$attribute['attr_input_type'],'','',[lang('attri_form_option_input_hand'),lang('attri_form_option_input_check'),lang('attri_form_option_input_text')]],
    		[lang('attri_form_title_value'),'textarea','attr_values',$attribute['attr_values'],'',lang('attri_form_title_value_tips')],
    		[lang('model_form_field_sort'),'text','sort',$attribute['sort']],
    	];
    	$JCreater = new JCreater();
    	$form['form_Html'] = $JCreater->form_build($form_fields);
    	$this->assign($form);
    	return $this->fetch('sys@Base/form'); 
    }

    public function update($id)
    {
    	if (request()->isPost()) {
    		$data = $this -> post_data;
    		$data['attr_values'] = str_replace(" "," ",str_replace("\n",",",$data['attr_values']));
    		$res = Db::name('shop_attribute') ->update($data);
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

    public function del($id)
    {
    	$isGoodsExists = Db::name('shop_goods_attr') ->where('attr_id',$id) ->find();
    	if ($isGoodsExists) {
    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
    	}

    	$res =	Db::name('shop_attribute') ->delete($id);
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
    	$isGoodsExists = Db::name('shop_goods_attr') ->where('attr_id','IN',$ids) ->find();
    	if ($isGoodsExists) {
    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
    	}
    	$res = Db::name('shop_attribute') ->where('id','IN',$ids) ->delete();
    	if ($res!==false) {
    		sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_delete_success'));
    	} else {
    		sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_delete_error'));
    	}
    }

    public function setvalue($id)
    {
    	$data = input('');
    	if (isset($data['field']) && isset($data['val'])) {
    		$field = $data['field'];
    		$value = $data['val'];
    	} else {
    		$field = $data['field_name'];
    		$value = $data['field'];
    	}
    	
    	$res =	Db::name('shop_attribute') ->where('id',$id) ->setField($field,$value);
    	if ($res!==false) {
    		sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_update_success'));
    	} else {
    		sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_update_error'));
    	}
    }

    
    /*ajax获取指定类型下的所有规格
    * param $id int 商品类型ID
    */
    public function ajaxGetAttr($id)
    {
        $Attribute = Db::name('shop_attribute') ->where('cate_id',$id) ->select();
        if ($Attribute) {
            foreach ($Attribute as $key => $Attr) {
                if ($Attr['attr_input_type']==1) {
                    $Attribute[$key]['attr_values'] = explode(',', $Attr['attr_values']);
                }
            }
            return ['code'=>1,'msg'=>lang('ajax_get_attr_success'),'data'=>$Attribute];
        } else {
            return ['code'=>0,'msg'=>lang('ajax_get_attr_error'),'data'=>[]];
        }  
    }
}