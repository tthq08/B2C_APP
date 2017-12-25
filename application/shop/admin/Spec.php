<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块规格控制器 
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

class Spec extends AdminBase
{
	protected function _initialize()
    {
    	parent::_initialize();
    }

    // 商品规格列表
    public function lists()
    {
    	// 是否显示表格的选择列？
    	$this->assign('show_check',1);


    	/*设置表格的表头，表格将按顺序显示设置的表头
    	* 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
    	*/
    	$table_head = [
    		['id','ID','text'],
//    		['p_id',lang('spec_list_title_0'),'text'],
    		['name',lang('spec_list_title_1'),'text'],
            ['cate_name','商品分类','text'],
    		['sort',lang('spec_list_title_2'),'input','Spec/setvalue','id'],
    		['search_index',lang('spec_list_title_3'),'switch','Spec/setvalue','id'],
    		['btn',lang('spec_list_title_4'),'btn'],
    	];
    	$table['tb_title'] = JCreater::table_header($table_head);


    	/*设置表格各行的操作按钮，
    	* 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
    	*/
    	$btn = [
    		[lang('spec_list_btn_item'),'frame',lang('spec_list_btn_item'),'fa fa-fw fa-tag','layui-btn-success','Spec/items','id'],
    		[lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Spec/edit','id'],
    		[lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Spec/del','id'],
    	];
    	$table['btn_lst'] = JCreater::table_btn($btn);


    	// 设置列表页顶部按钮组
    	$top_btn = [
    		[lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Spec/add'],
    		[lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Spec/dels'],
    	];
    	$table['top_btn'] = JCreater::table_btn($top_btn);

    	$this->assign($table);

        // 取出当前语言版本下的所有配置项
    	$Specs = Db::name('shop_spec') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));
    	$spec_list = $Specs ->all();
        foreach ($spec_list as $index => $item) {
            if( !empty($item['cate_id']) ){
                $spec_list[$index]['cate_name'] = getTableValue('goods_category','id='.$item['cate_id'],'name');
            }else{
                $spec_list[$index]['cate_name'] = '未绑定分类';
            }
        }
    	$this->assign('data',$spec_list);
    	// 获取分页显示
    	$page = $Specs->render();
    	$this->assign('page', $page);

    	return $this->fetch('sys@Base/table');
    }

    // 新增规格页面
    public function add()
    {
    	$form['web_title'] = lang('spec_form_add_title');   // 页面标题
    	$form['action'] = url('save');		//表单提交的目的路径

        // 获取所有一级规格
        $specList = api('shop','Goods','selectTopSpec');
        $list = [];
        foreach ($specList as $key=>$spec) {
            $list[$spec['id']] = $spec['name'];
        }
    	$form_fields = [
            [lang('spec_form_title_cate'),'linkselect','cate_tree',0,'',lang('spec_form_title_cate_tips'),'Goods/ajaxGetSubCate','required'],
            [lang('spec_form_title_pid'),'select','cate_id',0,'','',$list],
            [lang('spec_form_title_icon'),'img','icon','','','',''],

            [lang('spec_form_title_name'),'text','name','',lang('spec_form_title_name_place'),lang('spec_form_title_name_tips'),[],'required'],
    		[lang('spec_form_title_search'),'radio','search_index',1,'',lang('spec_form_title_search_tips'),[lang('spec_form_option_search_disable'),lang('spec_form_option_search_enable')]],
    		[lang('model_form_field_sort'),'text','sort','100'],
    	];
    	$JCreater = new JCreater();
    	$form['form_Html'] = $JCreater->form_build($form_fields);
    	$this->assign($form);
    	return $this->fetch('sys@Base/form'); 
    }

    // 保存规格数据
    public function save()
    {
    	if (request()->isPost()) {
    		$data = $this -> post_data;
            $cate_id = 0;
            foreach ($data['cate_tree'] as $k=>$item){
                if( !empty($item) ){
                    $cate_id = $item;
                }
            }
            $data['cate_tree'] = implode(',',$data['cate_tree']);
            $data['cate_id'] = $cate_id;
    		$res = Db::name('shop_spec') ->insertGetId($data);
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

    /*
     * 编辑规格页面
     * param $id int 规格ID
     */
    public function edit($id)
    {
    	$form['web_title'] = lang('spec_form_edit_title');   // 页面标题
    	$form['action'] = url('update');		//表单提交的目的路径

    	$spec = Db::name('shop_spec') ->find($id);

        $specList = api('shop','Goods','selectTopSpec');
        $list = [];
        foreach ($specList as $key=>$specl) {
            $list[$specl['id']] = $specl['name'];
        }

    	$form_fields = [
    		['','hidden','id',$id],
    		[lang('spec_form_title_cate'),'linkselect','cate_tree',$spec['cate_tree'],'',lang('spec_form_title_cate_tips'),'Goods/ajaxGetSubCate','required'],
            [lang('spec_form_title_pid'),'select','pid',$spec['pid'],'','',$list],
            [lang('spec_form_title_icon'),'img','icon',$spec['icon'],'','',''],

    		[lang('spec_form_title_name'),'text','name',$spec['name'],lang('spec_form_title_name_place'),lang('spec_form_title_name_tips'),[],'required'],
    		[lang('spec_form_title_search'),'radio','search_index',$spec['search_index'],'',lang('spec_form_title_search_tips'),[lang('spec_form_option_search_disable'),lang('spec_form_option_search_enable')]],
    		[lang('model_form_field_sort'),'text','sort',$spec['sort']],
    	];
    	$JCreater = new JCreater();
    	$form['form_Html'] = $JCreater->form_build($form_fields);
    	$this->assign($form);
    	return $this->fetch('sys@Base/form'); 
    }

    /*更新规格数据
    * param $id int 规格ID
    */
    public function update($id)
    {
    	if (request()->isPost()) {
    		$data = $this -> post_data;
            $cate_id = 0;
            foreach ($data['cate_tree'] as $k=>$item){
                if( !empty($item) ){
                    $cate_id = $item;
                }
            }
            $data['cate_tree'] = implode(',',$data['cate_tree']);
            $data['cate_id'] = $cate_id;
    		$res = Db::name('shop_spec') ->update($data);
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

    /*删除规格
    * param $id int 规格ID
    */
    public function del($id)
    {
    	$items = Db::name('shop_spec_item') ->field('id,item') ->where('spec_id',$id) ->select();
    	if ($items) {
    		$this->error(lang('spec_del_item_exist',[$items[0]['item']]));
    	}
    	foreach ($items as $key => $item) {
	    	$isGoodsExists = Db::name('shop_spec_price') ->where('FIND_IN_SET('.$item['id'].',key_sign)') ->where('trash',0) ->find();
	    	if ($isGoodsExists) {
	    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
	    	}
    	}

    	$res =	Db::name('shop_spec') ->delete($id);
    	if ($res!==false) {
    		sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_delete_success'));
    	} else {
    		sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_delete_error'));
    	}
    }

    /*批量删除规格
    */
    public function dels()
    {
    	$data = input('');
    	$ids = implode(',', $data['id']);
    	$items = Db::name('shop_spec_item') ->field('id,item') ->where('spec_id','IN',$ids)->select();
    	if ($items) {
    		$this->error(lang('spec_del_item_exist',[$items[0]['item']]));
    	}
    	foreach ($items as $key => $item) {
	    	$isGoodsExists = Db::name('shop_spec_price') ->where('FIND_IN_SET('.$item['id'].',key_sign)') ->where('trash',0) ->find();
	    	if ($isGoodsExists) {
	    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
	    	}
    	}
    	$res = Db::name('shop_spec') ->where('id','IN',$ids) ->delete();
    	if ($res!==false) {
    		sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_delete_success'));
    	} else {
    		sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_delete_error'));
    	}
    }

    /*修改规格的指定字段值
    * param $id int 规格ID
    */
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
    	
    	$res =	Db::name('shop_spec') ->where('id',$id) ->setField($field,$value);
    	if ($res!==false) {
    		sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_update_success'));
    	} else {
    		sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_update_error'));
    	}
    }

    // ======================================================================================
    // 
    // 										规格项管理
    // 
    // ======================================================================================

    /*规格项列表
    * param $id int 规格ID
    */
    public function items($id)
    {
    	// 是否显示表格的选择列？
    	$this->assign('show_check',1);


    	/*设置表格的表头，表格将按顺序显示设置的表头
    	* 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
    	*/
    	$table_head = [
    		['id','ID','text'],
    		['item',lang('item_list_title_0'),'text'],
            ['icon',lang('item_list_title_3'),'img'],
    		['sort',lang('item_list_title_1'),'input','Spec/setvalue_item','id'],
    		['btn',lang('item_list_title_2'),'btn'],
    	];
    	$table['tb_title'] = JCreater::table_header($table_head);


    	/*设置表格各行的操作按钮，
    	* 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
    	*/
    	$btn = [
    		[lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Spec/edit_item','id'],
    		[lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Spec/del_item','id'],
    	];
    	$table['btn_lst'] = JCreater::table_btn($btn);


    	// 设置列表页顶部按钮组
    	$top_btn = [
    		[lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Spec/add_item',['id'=>$id]],
    		[lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Spec/dels_item'],
    	];
    	$table['top_btn'] = JCreater::table_btn($top_btn);

    	$this->assign($table);

    	// 表格数据，分页，每页10条
    	$items = Db::name('shop_spec_item') ->where('spec_id',$id) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
    	$item_list = $items ->all();
    	$this->assign('data',$item_list);
    	// 获取分页显示
    	$page = $items->render();
    	$this->assign('page', $page);

    	return $this->fetch('sys@Base/table');
    }

    /*新增规格项
    * param $id int 规格ID
    */
    public function add_item($id)
    {
    	$form['web_title'] = lang('item_form_add_title');   // 页面标题
    	$form['action'] = url('save_item');		//表单提交的目的路径

        // 取出规格名称
    	$spec_name = Db::name('shop_spec') ->where('id',$id) ->value('name');
        // 取出该规格下的所有规格项
        $spec_items = Db::name('shop_spec_item')->where('spec_id',$id)->where('pid',0)->column('id');
    	// [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
    	$form_fields = [
    		['','hidden','spec_id',$id],
    		[lang('item_form_spec_name'),'static','',$spec_name],
    		[lang('item_form_title_item'),'text','item','',lang('item_form_title_item_place'),lang('item_form_title_item_tips'),[],'required'],
            [lang('item_form_title_icon'),'img','icon','','','',[],''],
    		[lang('item_form_title_sort'),'text','sort','100'],
    	];
    	$JCreater = new JCreater();
    	$form['form_Html'] = $JCreater->form_build($form_fields);
    	$this->assign($form);
    	return $this->fetch('sys@Base/form'); 
    }

    /*保存规格项数据
    */
    public function save_item()
    {
    	if (request()->isPost()) {
    		$data = $this -> post_data;
    		$res = Db::name('shop_spec_item') ->insertGetId($data);
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

    /*编辑规格项
    * param $id int 规格项ID
    */
    public function edit_item($id)
    {
    	$form['web_title'] = lang('item_form_add_title');   // 页面标题
    	$form['action'] = url('update_item');		//表单提交的目的路径

    	$item = Db::name('shop_spec_item') ->find($id);
    	$spec_name = Db::name('shop_spec') ->where('id',$item['spec_id']) ->value('name');
    	// [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
    	$form_fields = [
    		['','hidden','id',$id],
    		[lang('item_form_spec_name'),'static','',$spec_name],
    		[lang('item_form_title_item'),'text','item',$item['item'],lang('item_form_title_item_place'),lang('item_form_title_item_tips'),[],'required'],
            [lang('item_form_title_icon'),'img','icon',$item['icon'],'','',[],''],
    		[lang('item_form_title_sort'),'text','sort',$item['sort']],
    	];
    	$JCreater = new JCreater();
    	$form['form_Html'] = $JCreater->form_build($form_fields);
    	$this->assign($form);
    	return $this->fetch('sys@Base/form'); 
    }

    /*更新规格项
    */
    public function update_item()
    {
    	if (request()->isPost()) {
    		$data = $this -> post_data;
    		$res = Db::name('shop_spec_item') ->update($data);
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

    /*设置规格项指定字段值
    * param $id int 规格项ID
    */
    public function setvalue_item($id)
    {
    	$data = input('');
    	if (isset($data['field']) && isset($data['val'])) {
    		$field = $data['field'];
    		$value = $data['val'];
    	} else {
    		$field = $data['field_name'];
    		$value = $data['field'];
    	}
    	
    	$res =	Db::name('shop_spec_item') ->where('id',$id) ->setField($field,$value);
    	if ($res!==false) {
    		sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_update_success'));
    	} else {
    		sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_update_error'));
    	}
    }

    /*删除指定规格项
    * param $id int 规格项ID
    */
    public function del_item($id)
    {
    	$isGoodsExists = Db::name('shop_spec_price') ->where('FIND_IN_SET('.$id.',spec_key)') ->find();
    	if ($isGoodsExists) {
    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
    	}
    	$res =	Db::name('shop_spec_item') ->delete($id);
    	if ($res!==false) {
    		sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_delete_success'));
    	} else {
    		sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_delete_error'));
    	}
    }

    /*批量删除规格项
    */
    public function dels_item()
    {
    	$data = input('');
    	foreach ($data['id'] as $key => $id) {
	    	$isGoodsExists = Db::name('shop_spec_price') ->where('FIND_IN_SET('.$id.',key_sign)') ->find();
	    	if ($isGoodsExists) {
	    		$this->error(lang('spec_del_error_exist',[$isGoodsExists['goods_id']]));
	    	}
    	}
    	$ids = implode(',', $data['id']);
    	$res =	Db::name('shop_spec_item') ->where('id','IN',$ids) ->delete();
    	if ($res!==false) {
    		sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
    		$this->success(lang('comm_delete_success'));
    	} else {
    		sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
			$this->error(lang('comm_delete_error'));
    	}
    }


    public function ajaxChoose($id,$goods_id)
    {
        // $specs = Db::name('shop_spec') ->field('id,name') ->where('cate_id',$id) ->select();
        // $open = true;
        // foreach ($specs as $key => $spec) {
        //     $items = Db::name('shop_spec_item') ->field('id,item as name,spec_id as pid') ->where('spec_id',$spec['id']) ->select();
        //     foreach ($items as $dd => $value) {
        //         $items[$dd]['spec_name'] = $spec['name'];
        //     }

        //     $specs[$key]['open'] = $open;
        //     $specs[$key]['children'] = $items;
        //     $open = false;
        // }
        // $this->assign('spec_tree',json_encode($specs));
        $key_sign_arr = [];
        if( !empty(cache('key_sign_arr','','','goodsAdmin')) ){
            $key_sign_arr = cache('key_sign_arr','','','goodsAdmin');
        }
        $keys = array_keys($key_sign_arr);
        $key = end($keys);
        $this->assign('key_nums',$key+1);

        $this->assign('id',$id);
        $goods_sn = Db::name('shop_goods') ->where('id',$goods_id) ->value('goods_sn');
        $this->assign('goods_sn',$goods_sn);
        return $this->fetch();
    }

    public function ajaxGetSpec($id)
    {
        $specs = Db::name('shop_spec') ->field('id,name') ->where('cate_id',$id) ->select();
        $open = true;
        foreach ($specs as $key => $spec) {
            $items = Db::name('shop_spec_item') ->field('id,item as name,spec_id as pid') ->where('spec_id',$spec['id']) ->select();
            foreach ($items as $dd => $value) {
                $items[$dd]['spec_name'] = $spec['name'];
            }

            $specs[$key]['open'] = $open;
            $specs[$key]['children'] = $items;
            $open = false;
        }
        return $specs;
    }

    public function ajaxList2Tree($items)
    {
        $key_sign_arr = [];
        if( !empty(cache('key_sign_arr','','','goodsAdmin')) ){
            $key_sign_arr = cache('key_sign_arr','','','goodsAdmin');
        }
        $keys = array_keys($key_sign_arr);
        $key = end($keys);
        $this->assign('key_nums',$key+1);

        $items = json_decode(htmlspecialchars_decode($items),true);
        foreach ($items as $key => $it) {
            $dika[$it['pid']][] = $it['id'];

            $spec_tree[$it['pid']][] = $it;
            $tree[$it['id']] = $it;
        }
        // dump($spec_tree);die;

        $spec_tree = $tree;
        foreach ($spec_tree as $key => $spec) {
            $specs[$spec['pid']][] = $spec;
        }

        $j = 0;
        foreach ($specs as $k => $val) {
            $item_stree[$j]['id'] = $k;
            $item_stree[$j]['spec'] = getTableValue('shop_spec',['id'=>$k],'name');
            $item_stree[$j]['items'] = $val;
            $j ++;
        }
        // dump($item_stree);die;

        $arr_new = array_values($dika);
        foreach ($arr_new as $key => $val) {
            unset($arr_new[$key]);
            $nums = 1;
            foreach ($arr_new as $kk => $vv) {
                $nums = $nums*count($vv);
            }
            foreach ($val as $k => $item) {
                $rowspan[$item] = $nums;
            }
        }


        foreach ($dika as $key => $di) {
            $dikar[] = $di;
        }
        $dikas = combineDika($dikar);


        $table = [];

        $i = count($key_sign_arr);
        foreach ($dikas as $key => $dik) {
            $key_tree = [];
            $table_key = [];
            foreach ($dik as $kk => $d) {
                $tree[$d]['rowspan'] = $rowspan[$tree[$d]['id']];
                $key_tree[] = $tree[$d]['id'];
                $table_key[] = $tree[$d];
            }
            $new_key_sign = implode('_',$key_tree);
            if( in_array($new_key_sign,$key_sign_arr) ){
                unset($table[$key]);
            }else{
                $key_sign_arr[$i] = $new_key_sign;
                $table[] = $table_key;
                cache('key_sign_arr2',$key_sign_arr,'','goodsAdmin');
            }
            $i++;
        }
        // dump($table);die;
        return  array('status' => 1,'msg'=>'Data Complate','data'=>$table,'spec_item'=>$item_stree,'key_sign_arr2'=>json_encode($key_sign_arr));
    }

}
