<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块商品控制器 
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
use app\sys\controller\Comm;
use think\Db;
use think\Exception;

class Goods extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    // 商品列表
    public function index()
    {
        $data = input('');
        $sort_curr = 'ASC';
        if (empty($data['sort'])) {
            $order = ['sort'=>'ASC','id'=>'DESC'];
        } else {
            if ($data['sort']=='ASC') {
                $order = ['shop_price'=>'ASC'];
                $sort_curr = 'DESC';
            } else {
                $order = ['shop_price'=>'DESC'];
                $sort_curr = 'ASC';
            }
        }
        $this->assign('sort_curr',$sort_curr);

        $where['trash'] = 0;
        if (isset($data['cat_id'])) {
            foreach ($data['cat_id'] as $key => $cat) {
                if ($data['cat_id'][$key] == '') {
                    unset($data['cat_id'][$key]);
                }
            }
        }
        // dump($isset['cat_id'][0]);
        if (!empty($data['cat_id'][0])) {
            foreach ($data['cat_id'] as $k => $v) {
                if ($v == 0) {
                    unset($data['cat_id'][$k]);
                }
            }
            $cat_id = end($data['cat_id']);
            $where_1 = 'FIND_IN_SET("' . $cat_id . '",cat_tree)';
        } else {
            $where_1 = '1=1';
        }

        if (!empty($data['brand_id'])) {
            $where['brand_id'] = $data['brand_id'];
        }

        // if (!empty($data['key'])) {
        //     $where['title'] = ['like', "%{$data['key']}%"];
        // }

        if (!empty($data['condition'])) {
            switch ($data['condition']) {
                case 'brand':
                    $brand_ids = Db::name('shop_brand') ->field('GROUP_CONCAT(id) as ids') ->where('name','LIKE',"%{$data['key']}%") ->whereOr('title','LIKE',"%{$data['key']}%") ->whereOr('name','LIKE',"%{$data['key']}%") ->find();
                    $where['brand_id'] = ['IN',$brand_ids['ids']];
                    break;

                case 'shop_id':
                    $shop_ids = Db::name('cust_shop') ->field('GROUP_CONCAT(id) as ids') ->where('shop_name','LIKE',"%{$data['key']}%") ->whereOr('show_name','LIKE',"%{$data['key']}%") ->whereOr('sysid','LIKE',"%{$data['key']}%") ->find();
                    $where['shop_id'] = ['IN',$shop_ids['ids']];
                    break;

                default:
                    $where[$data['condition']] = ['like', "%{$data['key']}%"];
                    break;
            }
        }
        // dump($where);
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        $model = 'goods';
        $model_info = Db::name('shop_model')->where('name', $model)->find();
        $actions = json_decode($model_info['handle_func'], true);
        $action_arr = [];
        foreach ($actions as $key => $act) {
            $act_arr = explode(':', $act[0]);
            $action_arr[$act_arr[0]] = $act_arr[1];
        }
        $cat_id = !empty($data['cat_id']) ? implode(',', $data['cat_id']) : '';
        $brand_id = !empty($data['brand_id']) ? $data['brand_id'] : '';
        $key = !empty($data['key']) ? $data['key'] : '';

        $conditions = ['goods_sn' => '货号', 'title' => '名称', 'brand' => '品牌', 'shop_id' => '店铺ID'];
        $condition = !empty($data['condition']) ? $data['condition'] : '';
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'goods_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                ['', 'linkselect', 'cat_id', $cat_id, '', '', 'Goods/ajaxGetSubCate'],
                ['', 'select', 'condition',$condition, '', '', $conditions],
                [lang('comm_filter_key_title'), 'text', 'key', $key, lang('comm_filter_key_title')]
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $act_edit = isset($action_arr['edit']) ? $action_arr['edit'] : 'Content/edit';
        $act_del = isset($action_arr['del']) ? $action_arr['del'] : 'Content/del';
        $btn = [
            ['推荐', 'frame', '推荐', '', 'layui-btn-normal', 'Sys/Position/addBind', $model . '_id','','推荐'],
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', $act_edit, $model . '_id'],
            [lang('goods_list_btn_imgs'), 'frame', lang('goods_list_btn_imgs'), 'fa fa-fw fa-picture-o', 'layui-btn-normal', 'Goods/addimgs', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_del, $model . '_id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $act_add = isset($action_arr['add']) ? $action_arr['add'] : 'Content/add';
        $act_dels = isset($action_arr['dels']) ? $action_arr['dels'] : 'Content/dels';

        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', $act_add, ['model' => $model]],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_dels, ['model' => $model]],
            ['更新选中的商品链接', 'confirm_form', '您确定要更新选中的商品链接吗?', '', '', 'updateGoodsUrl', ['model' => $model]],

            ['更新所有的商品链接', 'confirm_form', '您确定要更新所有的商品链接吗?', '', 'layui-btn-warm', 'updateGoodsUrl', ['type' => 'all']],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $dbContent = str_replace(config('database.prefix'), '', $model_info['table']);
        $conten = Db::name($dbContent)->where($where)->where($where_1)->order($order)->paginate(tb_config('list_rows', 1, $this->lang), '', ['query' => $data]);  //取出当前语言版本下的所有记录
        // dump(Db::name($dbContent)->getLastSql());
        $content_list = $conten->all();
        foreach ($content_list as $key => $con) {
            $content_list[$key]['cat_id'] = getTableValue('goods_category', ['id' => $con['cat_id']], 'name');
            $stock = Db::name('shop_spec_price') ->where('goods_id',$con['id']) ->sum('store_count');
            $stock = !empty($stock)?$stock:getTableValue('shop_goods', ['id' => $con['id']], 'stock');
            $content_list[$key]['stock'] = $stock;
        }
        $this->assign('data', $content_list);
        // 获取分页显示
        $page = $conten->render();
        $this->assign('page', $page);
        return $this->fetch('goods');
    }

    // ajax 筛选页面 
    public function ajaxfilter()
    {
        $data = input('');
        $where['trash'] = 0;
        if (isset($data['cat_id'])) {
            foreach ($data['cat_id'] as $key => $cat) {
                if ($data['cat_id'][$key] == '') {
                    unset($data['cat_id'][$key]);
                }
            }
        }
        // dump($isset['cat_id'][0]);
        if (!empty(($data['cat_id'][0]))) {
            foreach ($data['cat_id'] as $k => $v) {
                if ($v == 0) {
                    unset($data['cat_id'][$k]);
                }
            }
            $cat_id = end($data['cat_id']);
            $where_1 = 'FIND_IN_SET("' . $cat_id . '",cat_tree)';
        } else {
            $where_1 = '1=1';
        }

        if (!empty($data['brand_id'])) {
            $where['brand_id'] = $data['brand_id'];
        }

        if (!empty($data['key'])) {
            $where['title'] = ['like', "%{$data['key']}%"];
        }

        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        $model = 'goods';
        $model_info = Db::name('shop_model')->where('name', $model)->find();
        $actions = json_decode($model_info['handle_func'], true);
        $action_arr = [];
        foreach ($actions as $key => $act) {
            $act_arr = explode(':', $act[0]);
            $action_arr[$act_arr[0]] = $act_arr[1];
        }
        $data['cat_id'] = implode(',', $data['cat_id']);
        $filter = [
            'method' => 'get',
            'action' => 'Goods/ajaxFilter',
            'post_id' => 'goods_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('goods_select_down_category'), 'linkselect', 'cat_id', $data['cat_id'], '', '', 'Goods/ajaxGetSubCate'],
                [lang('goods_select_brand'), 'linkage', 'brand_id', $data['brand_id'], '', '', 'Goods/ajaxGetBrand'],
                [lang('comm_filter_key_title'), 'text', 'key', $data['key'], lang('comm_filter_key_title')]
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);


        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'], true);
            $table_head['btn'] = ['btn', lang('content_list_title_7'), 'btn'];
        } else {
            $table_head = [
                ['id', 'ID', 'text'],
                ['title', lang('content_list_title_0'), 'text'],
                ['cate', lang('content_list_title_1'), 'text'],
                ['view', lang('content_list_title_2'), 'text'],
                ['uname', lang('content_list_title_3'), 'text'],
                ['update_time', lang('content_list_title_4'), 'text'],
                ['sort', lang('content_list_title_5'), 'input', 'content/sort', $model . '_id'],
                ['status', lang('content_list_title_6'), 'switch', 'content/switchs', $model . '_id'],
                ['btn', lang('content_list_title_7'), 'btn'],
            ];
        }

        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $act_edit = isset($action_arr['edit']) ? $action_arr['edit'] : 'Content/edit';
        $act_del = isset($action_arr['del']) ? $action_arr['del'] : 'Content/del';
        $btn = [
            ['推荐', 'frame', '推荐', '', 'layui-btn-normal', 'Sys/Position/addBind', $model . '_id','','推荐'],

            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', $act_edit, $model . '_id'],
            [lang('goods_list_btn_imgs'), 'frame', lang('goods_list_btn_imgs'), 'fa fa-fw fa-picture-o', 'layui-btn-normal', 'Goods/addimgs', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_del, $model . '_id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $act_add = isset($action_arr['add']) ? $action_arr['add'] : 'Content/add';
        $act_dels = isset($action_arr['dels']) ? $action_arr['dels'] : 'Content/dels';

        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', $act_add, ['model' => $model]],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_dels, ['model' => $model]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $dbContent = str_replace(config('database.prefix'), '', $model_info['table']);
        $conten = Db::name($dbContent)->where($where)->where($where_1)->order(['id' => 'DESC'])->paginate(tb_config('list_rows', 1, $this->lang), '', ['query' => $data]);  //取出当前语言版本下的所有记录

        $content_list = $conten->all();
        foreach ($content_list as $key => $con) {
            $content_list[$key]['cat_id'] = getTableValue('goods_category', ['id' => $con['cat_id']], 'name');
        }
        $this->assign('data', $content_list);
        // 获取分页显示
        $page = $conten->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');


    }

    // 新增商品页面
    public function add($step = '1')
    {
        $this->assign('step', $step);
        $this->assign('action', 'add');
        $model = 'goods';
        $model_info = Db::name('shop_model')->where('name', $model)->find();
        $dbField = Db::name('shop_field');
        $where['model'] = $model_info['id'];
        $where['status'] = 1;
        $where['show'] = 1;
        $where['group'] = $step;
        $fields = $dbField->where($where)->order(['sort' => "ASC", 'id' => 'ASC'])->select();
        // dump($fields);

        if ($step == 2) {
            $data = $this->post_data;
            if( empty($data['cat_id'][0]) ){
                $this->redirect('add');
            }

            foreach ($data['cat_id'] as $key => $cat) {
                if (!empty($cat)) {
                    $cat_title[] = getTableValue('goods_category', ['id' => $cat], 'name');
                    $cat_id = $cat;
                } else {
                    unset($data['cat_id'][$key]);
                }
            }
            $cate_tree = implode(',', $data['cat_id']);
            $next_url = url('Goods/save');
            $form_field = [['', 'hidden', 'cat_id', $cat_id], ['', 'hidden', 'cat_tree', $cate_tree], [lang('content_form_cate_title'), 'static', '', implode('--', $cat_title)]];

        } else {
            $next_url = url('Goods/add', ['step' => 2]);
        }
        $form['action'] = $next_url;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题

        $option_type = ['select', 'radio', 'checkbox'];


        foreach ($fields as $key => $field) {

            if ($field['name'] == 'brand_id') {
                // 获取当前分类的品牌
                $brandList = api('shop', 'Goods', 'getBrandList', [$cat_id]);
                $options = [];
                foreach ($brandList as $key2=>$item){
                    $options[$item['id']] = $item['name'];
                }

            } else {
                $required = $field['allow_null'] != 1 ? 'required' : '';
                $options = [];
                if (in_array($field['type'], $option_type)) {
                    if (!empty($field['options'])) {
                        $options = optStr2Arr($field['options']);
                    }
                }
                if ($field['type'] == 'linkage') {
                    $options = $field['ajax_url'];
                }
                if ($field['type'] == 'linkselect') {
                    $options = $field['ajax_url'];
                }
            }

            // dump($options);
            $form_field[] = [
                $field['title'], $field['type'], $field['name'], $field['value'], '', $field['tips'], $options, $required
            ];
        }
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_field);
        $this->assign($form);
        return $this->fetch('form');
    }

    // 保存商品数据
    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['goods_sn'] = empty($data['goods_sn']) ? date('YmdHis') . rand(10000, 99999) : $data['goods_sn'];
            // dump($data['cat_id']);
            if (is_array($data['cat_id'])) {
                foreach ($data['cat_id'] as $key => $cat) {
                    if (empty($cat)) {
                        unset($data['cat_id'][$key]);
                    }
                }
                $data['cat_tree'] = implode(',', $data['cat_id']);
                $data['cat_id'] = end($data['cat_id']);
            }

            Db::startTrans();
            try{
                $contentData = [
                    'seo_title' => $data['seo_title'],
                    'seo_keyword' => $data['seo_key'],
                    'seo_description' => $data['seo_desc']
                ];
                unset($data['seo_title']);
                unset($data['seo_key']);
                unset($data['seo_desc']);
                $goods_id = Db::name('shop_goods')->insertGetId($data);
                $contentData['id'] = $goods_id;
                $goods_content = Db::name('shop_goods_content')->insert($contentData);
                api('shop','Goods','updateGoodsUrl',[$goods_id]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                sys_log(lang('comm_insert_error'), 0);
                $this->error($e->getMessage());
            }
            sys_log(lang('comm_insert_success'), 1);
            $this->success(lang('comm_insert_success'), url('Goods/addimgs', ['id' => $goods_id]));
        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    // 编辑商品页面
    public function edit($goods_id)
    {
        $this->assign('goods_id', $goods_id);
        $this->assign('step', 2);

        $goods = Db::name('shop_goods')->find($goods_id);
        $this->assign('action', 'edit');
        $model = 'goods';
        $model_info = Db::name('shop_model')->where('name', $model)->find();
        $where['model'] = $model_info['id'];
        $where['status'] = 1;
        $where['show'] = 1;
        $fields = Db::name('shop_field')->where($where)->order(['sort' => "ASC", 'id' => 'ASC'])->select();

        $next_url = url('Goods/update');
        $form['action'] = $next_url;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_edit_title');   // 页面标题
        $form_field = [['', 'hidden', 'id', $goods_id]];
        $option_type = ['select', 'radio', 'checkbox'];

        $cat_id = $goods['cat_id'];
        $goods['cat_id'] = $goods['cat_tree'];
        $seo = api('shop','Goods','getSeo',[$goods_id]);
        $goods['seo_title'] = $seo['seo_title'];
        $goods['seo_key'] = $seo['seo_keyword'];
        $goods['seo_desc'] = $seo['seo_description'];
        $goods['content'] = api('shop','Goods','getContent',[$goods_id]);
        foreach ($fields as $key => $field) {

            if ($field['name'] == 'brand_id') {
                // 获取当前分类的品牌
                $brandList = api('shop', 'Goods', 'getBrandList', [$cat_id]);
                $options = [];
                foreach ($brandList as $key2=>$item){
                    $options[$item['id']] = $item['name'];
                }
            } else {
                $required = $field['allow_null'] != 1 ? 'required' : '';
                $options = [];
                if (in_array($field['type'], $option_type)) {
                    if (!empty($field['options'])) {
                        $options = optStr2Arr($field['options']);
                    }
                }
                if ($field['type'] == 'linkage') {
                    $options = $field['ajax_url'];
                }
                if ($field['type'] == 'linkselect') {
                    $options = $field['ajax_url'];
                }
            }

            // dump($options);
            $form_field[] = [$field['title'], $field['type'], $field['name'], $goods[$field['name']], '', $field['tips'], $options, $required];
        }
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_field);
        $this->assign($form);
        return $this->fetch('form_edit');
    }

    // 更新商品数据
    public function update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            foreach ($data['cat_id'] as $key => $cat) {
                if (empty($cat)) {
                    unset($data['cat_id'][$key]);
                }
            }
            // dump($data['cat_id']);
            $data['cat_tree'] = implode(',', $data['cat_id']);
            $data['cat_id'] = end($data['cat_id']);

            $id = $data['id'];
            unset($data['id']);
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['goods_sn'] = empty($data['goods_sn']) ? date('YmdHis') . rand(10000, 99999) : $data['goods_sn'];

            Db::startTrans();
            try{
                $contentData = [
                    'seo_title' => $data['seo_title'],
                    'seo_keyword' => $data['seo_key'],
                    'seo_description' => $data['seo_desc'],
                    'content' => $data['content'],
                ];
                unset($data['seo_title']);
                unset($data['seo_key']);
                unset($data['seo_desc']);
                unset($data['content']);
                $update = Db::name('shop_goods')->where('id', $id)->update($data);
                $goods_content = Db::name('shop_goods_content')->where('id',$id)->update($contentData);
                api('shop','Goods','updateGoodsUrl',[$id]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                dump($e->getMessage());
                die;
                sys_log(lang('comm_update_error'), 0);
                $this->error(lang('comm_update_error'));
            }
            sys_log(lang('comm_update_success'), 1);
            $this->success(lang('comm_update_success'), url('Goods/addimgs', ['id' => $id]));

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    // 设置商品指定字段的值
    public function goods_set_value($id)
    {
        $data = input('');
        if (isset($data['field_name'])) {
            $field = $data['field_name'];
            $value = $data['field'];
        } else {
            $field = $data['field'];
            $value = $data['val'];
        }
        $res = Db::name('shop_goods')->where('id', $id)->setField($field, $value);
        if ($res !== false) {
            sys_log(lang('comm_update_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    // 上传商品默认图册
    public function addimgs($id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $id = $data['id'];

            $del = Db::name('shop_goods_images')->where('goods_id', $id)->delete();
            $imgData = [];
            if( !empty($data['image_url']) ) {
                foreach ($data['image_url'] as $key => $img) {
                    $img_data['image_url'] = $img;
                    $img_data['image_sort'] = empty($data['sort'][$key]) ? 0 : $data['sort'][$key];
                    $img_data['goods_id'] = $data['id'];
                    $imgData[] = $img_data;
                }
            } else {
                sys_log( lang('comm_update_success') , 1);
                $this->success(lang('comm_update_success'), url('Goods/spec_price', ['id' => $id]));
            }
            if ($del !== false) {
                $res = Db::name('shop_goods_images')->insertAll($imgData);
                if ($res !== false) {
                    sys_log(lang('comm_update_success'), 1);
                    $this->success(lang('comm_update_success'), url('Goods/spec_price', ['id' => $id]));
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            } else {
                sys_log(lang('comm_update_error'), 0);
                $this->error(lang('comm_update_error'));
            }

        } else {
            $this->assign('id', $id);
            $imgInfo = Db::name('shop_goods_images')->where('goods_id', $id)->order('image_sort ASC')->select();
            $this->assign('img_list', $imgInfo);
            $this->assign('step', 3);
            $next_url = '';
            $form['action'] = $next_url;      //表单提交的目的路径
            $form['web_title'] = lang('content_form_addimg_title');   // 页面标题
            $form['title'] = lang('content_form_imgs_title');
            $form['name'] = 'image_url';
            $form['tips'] = '';
            $this->assign($form);
            $ruller = [];
            $goods = Db::name('shop_goods')->find($id);
            $this->assign('Goods', $goods);
            return $this->fetch();
        }
    }

    // 设置商品规格属性
    public function spec_price($id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $goods_id = $data['id'];
            // 保存商品默认属性设置
            if (isset($data['attr'])) {
                foreach ($data['attr'] as $key => $attr) {
                    $attr = str_replace("\n", "", $attr);
                    $attr = is_array($attr) ? implode($attr, ',') : $attr;
                    $spec_attr[] = [
                        'goods_id' => $goods_id,
                        'spec_key' => 0,
                        'spec_sn' => 0,
                        'attr_id' => $key,
                        'attr_value' => $attr
                    ];
                }

                // 首先清除原先的属性设置，再插入新的属性设置
                $del = Db::name('shop_goods_attr')->where(['spec_key' => 0, 'goods_id' => $goods_id])->delete();
                if ($del !== false) {
                    $resInsert = Db::name('shop_goods_attr')->insertAll($spec_attr);
                    if ($resInsert === false) {
                        sys_log(lang('comm_update_error'), 0);
                        $this->error(lang('comm_update_error'));
                    }
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            }

            // 先删除当前商品下的所有规格记录，后面再添加新的
            $del = Db::name('shop_spec_price')->where(['goods_id' => $goods_id])->delete();
            // 拼接商品规格数组
            if (isset($data['spec_id'])) {
                foreach ($data['spec_id'] as $key => $spec) {
                    $specData[] = [
                        'goods_id' => $goods_id,
                        'key_sign' => $spec,
                        'spec_key' => str_replace("_", ",", $spec),
                        'key_name' => $data['spec_name'][$spec],
                        'key_sn' => $data['spec_sn'][$spec],
                        'price' => $data['spec_price'][$spec],
                        'points' => $data['spec_points'][$spec],
                        'store_count' => $data['spec_stock'][$spec],
                    ];
                }

                if ($del !== false) {
                    // 添加当前商品的规格记录
                    $res = Db::name('shop_spec_price')->insertAll($specData);
                    if ($res === false) {
                        sys_log(lang('comm_update_error'), 0);
                        $this->error(lang('comm_update_error'));
                    }
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            }
            sys_log(lang('comm_update_success'), 1);
            $this->success(lang('comm_update_success'), 'Goods/index');


        } else {
            $this->assign('id', $id);
            $this->assign('step', 4);
            $next_url = '';

            $form['action'] = $next_url;      //表单提交的目的路径
            $form['web_title'] = lang('content_form_spec_price_title');   // 页面标题
            $this->assign($form);

            // 商品基础信息
            $goods = Db::name('shop_goods')->find($id);
            $this->assign('goods', $goods);

            // 加载商品默认属性设置
            $goods_attr = Db::name('shop_goods_attr') ->where(['goods_id'=>$id,'spec_key'=>0,'spec_sn'=>0]) ->column('attr_value','attr_id');
            $attrGroup = api('shop', 'Goods', 'getAttrGroup', [$goods['cat_id']]);
            $Attribute = api('shop', 'FeatureGroup', 'itemList', [$attrGroup, true]);

            $attr_temp = [];
            if (!empty($Attribute)) {

                foreach ($Attribute as $key => $Attr) {
                    $option = [];
                    $attr_temp[$key] = $Attr;
                    switch ($Attr['attr_input_type']) {
                        case '0':
                            $attr_temp[$key]['type'] = 'text';
                            break;
                        case '1':
                            $opts = explode(',', $Attr['attr_values']);
                            foreach ($opts as $k => $val) {
                                $option[$val] = $val;
                            }
                            $attr_temp[$key]['options'] = $option;
                            $attr_temp[$key]['type'] = 'radio';
                            break;

                        case '2':
                            $opts = explode(',', $Attr['attr_values']);
                            foreach ($opts as $k => $val) {
                                $option[$k] = $val;
                            }
                            $attr_temp[$key]['options'] = $option;
                            $attr_temp[$key]['type'] = 'checkbox';
                            break;
                    }
                }
            }
            foreach ($goods_attr as $k=>$attr) {
                $attr = explode(',',$attr);
                array_unshift($attr,'');
                $goods_attr[$k] = array_filter($attr);
            }
            $this->assign('goods_attr',$goods_attr);
            $this->assign('attr_temp', $attr_temp);
            // 已经加载的sign_key
            $key_sign_arr = [];

            // 加载商品已有规格数据
            $spec_goods = Db::name('shop_spec_price')->where('goods_id', $id)->select();
            if (!empty($spec_goods)) {
                foreach ($spec_goods as $key => $spec) {
                    $key_sign_arr[] = $spec['key_sign'];
                    $spec_arr = explode(',', rtrim($spec['spec_key']));
                    foreach ($spec_arr as $kk => $arr) {
                        if (empty($arr)) {
                            continue;
                        }
                        // 获取数据
                        $arrData = api('shop','Goods','getSpecItem',[$arr]);
                        $specData = api('shop','Goods','getSpec',[$arrData['spec_id']]);
                        $spec_arrs[$kk]['spec'] = $specData['name'];
                        $spec_arrs[$kk]['item'] = $arrData['item'];
                    }
                    $spec_goods[$key]['key_name_arr'] = $spec_arrs;
                }
            } else {
                $spec_goods = [];
            }

            cache('key_sign_arr', $key_sign_arr, '', 'goodsAdmin');
            $this->assign('key_sign_arr', json_encode($key_sign_arr));

            $this->assign('spec_goods', $spec_goods);

            $spec_icon = Db::name('shop_spec_icon')->where('goods_id', $id)->select();
            foreach ($spec_icon as $key => $spec) {
                $spec_gp[$spec['spec_id']][] = $spec;
            }
            $spec_gps = [];
            if (!empty($spec_gp)) {
                foreach ($spec_gp as $kk => $vv) {
                    $spec_gps[$kk]['spec_id'] = $kk;
                    $spec_gps[$kk]['spec'] = getTableValue('shop_spec', ['id' => $kk], 'name');
                    foreach ($vv as $k => $v) {
                        $vv[$k]['item'] = getTableValue('shop_spec_item', ['id' => $v['item_id']], 'item');
                    }
                    $spec_gps[$kk]['items'] = $vv;
                }
            }
            $this->assign('specIcon', $spec_gps);
            return $this->fetch();
        }
    }

    public function update_key_sign_arr()
    {
        if (!empty(request()->param('data'))) {
            $data = request()->param('data');
            $data = json_decode(htmlspecialchars_decode($data), 1);
            cache('key_sign_arr', $data, '', 'goodsAdmin');
        } else {
            return true;
        }
    }

    // 上传商品规格的自定义图册
    public function specimgs($spec_key, $spec_sn, $goods_id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            foreach ($data['image_url'] as $key => $img) {
                $img_data['image_url'] = $img;
                $img_data['image_sort'] = empty($data['sort'][$key]) ? 0 : $data['sort'][$key];
                $img_data['goods_id'] = $data['goods_id'];
                $img_data['key_sn'] = $data['spec_sn'];
                $img_data['spec_key'] = $data['spec_key'];
                $imgData[] = $img_data;
            }
            $del = Db::name('shop_spec_image')->where(['spec_key' => $data['spec_key'], 'goods_id' => $data['goods_id']])->delete();
            if ($del !== false) {
                $res = Db::name('shop_spec_image')->insertAll($imgData);
                if ($res !== false) {
                    sys_log(lang('comm_update_success'), 1);
                    $this->success(lang('comm_update_success'));
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            } else {
                sys_log(lang('comm_update_error'), 0);
                $this->error(lang('comm_update_error'));
            }
        } else {
            $this->assign('spec_key', $spec_key);
            $this->assign('spec_sn', $spec_sn);
            $this->assign('goods_id', $goods_id);
            $this->assign('action', '');
            $imgs = Db::name('shop_spec_image')->where(['goods_id' => $goods_id, 'key_sn' => $spec_sn, 'spec_key' => $spec_key])->order(['image_sort' => 'ASC'])->select();
            $this->assign('img_list', $imgs);
            return $this->fetch();
        }
    }

    // 编辑商品规格的自定义内容详情
    public function speccontent($spec_key, $spec_sn, $goods_id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $data['content'] = htmlspecialchars($data['content']);
            $del = Db::name('shop_spec_content')->where(['spec_key' => $data['spec_key'], 'goods_id' => $data['goods_id']])->delete();
            if ($del !== false) {
                $res = Db::name('shop_spec_content')->insert($data);
                if ($res !== false) {
                    sys_log(lang('comm_update_success'), 1);
                    $this->success(lang('comm_update_success'));
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            } else {
                sys_log(lang('comm_update_error'), 0);
                $this->error(lang('comm_update_error'));
            }
        } else {
            $this->assign('goods_id', $goods_id);

            $spec_info = Db::name('shop_spec_content')->where(['goods_id' => $goods_id, 'key_sn' => $spec_sn, 'spec_key' => $spec_key])->find();

            $form['action'] = '';      //表单提交的目的路径
            $form['web_title'] = lang('spec_price_table_window_content');   // 页面标题
            $form_field = [
                ['', 'hidden', 'goods_id', $goods_id],
                ['', 'hidden', 'key_sn', $spec_sn],
                ['', 'hidden', 'spec_key', $spec_key],
                [lang('spec_con_window_title'), 'ueditor', 'content', $spec_info['content']],
            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_field);
            $this->assign($form);
            return $this->fetch('sys@Base/form');
        }
    }

    // 设置商品规格的自定义属性
    public function specattr($spec_key, $spec_sn, $goods_id, $type_id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $del = Db::name('shop_goods_attr')->where(['spec_key' => $data['spec_key'], 'goods_id' => $data['goods_id']])->delete();
            if( empty($data['attr_values']) ){
                sys_log(lang('comm_update_success'), 1);
                $this->success(lang('comm_update_success'));
            }
            foreach ($data['attr_values'] as $key => $value) {
                $value = is_array($value) ? implode($value, ',') : $value;
                $spec_attr[] = [
                    'goods_id' => $data['goods_id'],
                    'spec_key' => $data['spec_key'],
                    'spec_sn' => $data['spec_sn'],
                    'attr_id' => $key,
                    'attr_value' => $value
                ];
            }

            if ($del !== false) {
                $res = Db::name('shop_goods_attr')->insertAll($spec_attr);
                if ($res !== false) {
                    sys_log(lang('comm_update_success'), 1);
                    $this->success(lang('comm_update_success'));
                } else {
                    sys_log(lang('comm_update_error'), 0);
                    $this->error(lang('comm_update_error'));
                }
            } else {
                sys_log(lang('comm_update_error'), 0);
                $this->error(lang('comm_update_error'));
            }
        } else {
            $this->assign('goods_id', $goods_id);

            $spec_info = Db::name('shop_goods_attr')->where(['goods_id' => $goods_id, 'spec_sn' => $spec_sn, 'spec_key' => $spec_key])->find();

            $form['action'] = '';      //表单提交的目的路径
            $form['web_title'] = lang('spec_attr_window_title');   // 页面标题
            $form_field = [
                ['', 'hidden', 'goods_id', $goods_id],
                ['', 'hidden', 'spec_sn', $spec_sn],
                ['', 'hidden', 'spec_key', $spec_key],
            ];

            $goods = Db::name('shop_goods')->find($goods_id);
            $spec_attr = Db::name('shop_goods_attr') ->where(['goods_id'=>$goods_id,'spec_key'=>$spec_key,'spec_sn'=>$spec_sn]) ->column('attr_value','attr_id');

            $attrGroup = api('shop', 'Goods', 'getAttrGroup', [$goods['cat_id']]);
            $Attribute = api('shop', 'FeatureGroup', 'itemList', [$attrGroup, true]);

            foreach ($Attribute as $key => $Attr) {
                $option = [];
                switch ($Attr['attr_input_type']) {
                    case '0':
                        $type = 'text';
                        $value = isset($spec_attr[$Attr['id']]) ? $spec_attr[$Attr['id']] : '';
                        break;

                    case '1':
                        $opts = explode(',', $Attr['attr_values']);
                        foreach ($opts as $k => $val) {
                            $val = rtrim($val);
                            $option[$val] = $val;
                        }
                        $type = 'radio';
                        $value = isset($spec_attr[$Attr['id']]) ? $spec_attr[$Attr['id']] : '';
                        break;

                    case '2':
                        $opts = explode(',', $Attr['attr_values']);
                        foreach ($opts as $k => $val) {
                            $val = rtrim($val);
                            $option[$val] = $val;
                        }
                        $type = 'checkbox';
                        $value = isset($spec_attr[$Attr['id']]) ? explode(',',$spec_attr[$Attr['id']]) : [];
                        break;
                }
                $form_field[] = [$Attr['attr_name'], $type, 'attr_values[' . $Attr['id'] . ']', $value, '', '', $option];
            }
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_field);
            $this->assign($form);
            return $this->fetch('sys@Base/form');
        }
    }

    public function delete($goods_id)
    {
        $res = Db::name('shop_goods')->where('id', $goods_id)->setField('trash', 1);
        if ($res !== false) {
            // 同时将商品所有的规格也设为已删除
            Db::name('shop_spec_price')->where('goods_id', $goods_id)->setField('trash', 1);
            sys_log(lang('comm_update_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function clearGoodsThumb($id)
    {
        $dirName = str_replace('\\', '/', '.'.UPLOAD_PATH.'goods/thumb/' . $id);
        if( !is_dir($dirName) ){
            $this->success('当前没有需要清理的图片缓存!');
        }
        $op = dir($dirName);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . '/' . $item)) {
//                deleteAll($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                unlink($op->path . '/' . $item);
            }

        }
        $this->success(lang('goods_list_clear_imgs_ok'));
    }


    /* =======================================================================================
    *
    *                                       商品分类管理
    *
    *=========================================================================================/

        /*商品分类列表*/
    public function category_old()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check', 1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id', 'ID', 'text'],
            ['sort', lang('cate_list_title_0'), 'input', 'Goods/cate_set_value', 'id'],
            ['name', lang('cate_list_title_1'), 'text'],
            // ['mobile_name',lang('cate_list_title_2'),'text'],
            ['is_hot', lang('cate_list_title_3'), 'switch', 'Goods/cate_switch', 'id'],
            ['is_show', lang('cate_list_title_4'), 'switch', 'Goods/cate_switch', 'id'],
            ['commission_rate', lang('cate_list_title_5'), 'input', 'Goods/cate_set_value', 'id'],
            // ['cat_group',lang('cate_list_title_6'),'input','Goods/cate_set_value','id'],
            ['btn', lang('cate_list_title_7'), 'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class -按钮的样式类；执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名，也可设置数组设置参数对;显示条件 - 数组，存放需要显示该按钮应该具备的条件格式['字段','判断标准','条件值']，如 ['type','<>','0']
        */
        $btn = [
            [lang('cate_btn_add_ad'), 'frame', lang('cate_frame_ad_title'), 'fa fa-fw fa-flag', 'layui-btn-success', 'Goods/cate_adv', 'id', ['level', '=', '1']],
            [lang('cate_btn_add_sub'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-success', 'Goods/cate_add', 'id', ['level', '<', '3']],
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', 'Goods/cate_edit', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Goods/cate_del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Goods/cate_add'],
            // [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Index/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $models = Db::name('goods_category')->where('level', '<=', 2)->order(['sort' => 'ASC', 'id' => 'ASC'])->column('*', 'id');
        $model_list = array2tree($models);
        $model_list = tree2list($model_list);
        $this->assign('expandLevel', 1);
        $this->assign('data', $model_list);
        return $this->fetch('sys@Base/treetable');
    }

    /*商品分类列表*/
    public function category()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check', 1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
        */
        $table_head = [
            ['id', 'ID', 'text'],
            ['sort', lang('cate_list_title_0'), 'input', 'Goods/cate_set_value', 'id'],
            ['name', lang('cate_list_title_1'), 'text', '', '', [], 'text-align:left'],
            ['is_hot', lang('cate_list_title_3'), 'switch', 'Goods/cate_switch', 'id'],
            ['is_show', lang('cate_list_title_4'), 'switch', 'Goods/cate_switch', 'id'],
            ['btn', lang('cate_list_title_7'), 'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Goods/cate_add'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $models = Db::name('goods_category')->where('pid', 0)->order(['sort' => 'ASC', 'id' => 'ASC'])->column('*', 'id');

        foreach ($models as $key => $cate) {
            $models[$key]['subs'] = Db::name('goods_category')->where('pid', $cate['id'])->count();
        }
        // dump($models);
        $this->assign('data', $models);
        return $this->fetch();
    }


    /**
     * 域名绑定
     * @param $id
     * @return mixed
     */
    public function cate_bind($id)
    {
        if (empty($id)) {
            return $this->error('请选择一个需要绑定的分类!');
        }
        $comm = new Comm();
        $controller = $comm->getController('shop');
        // 获取分类信息
        $cateInfo = api('shop', 'Goods', 'categoryInfo', [$id]);

        // 获取已经绑定的信息
        $bind_info = api('sys', 'Domain', 'getDomainInfoFromType', [1, $id]);
        $this->assign('cate', $cateInfo);
        $this->assign('bindInfo', $bind_info);
        $this->assign('controller', $controller);
        $this->assign('cate_id', $id);
        return $this->fetch();
    }


    /**
     * 保存域名绑定
     * @return mixed
     */
    public function cate_bind_save()
    {
        $data = $this->post_data;
        // 查看域名库中是否存在该域名
        $is_exist = api('sys', 'Domain', 'getDomainInfo', [$data['domain']]);
        if (!empty($is_exist)) {
            if (!empty($data['id']) && $data['id'] != $is_exist['id']) {
                $this->error('当前域名已被设置,请您重新填写!' . $data['id'] . '|' . $is_exist['id']);
            }
        }

        if ($data['custom'] == 1) {
            if (empty($data['controller']) || empty($data['action'])) {
                $this->error('请选择指向地址!');
            }
        }

        // 保存信息
        $insertData = [
            'domain' => $data['domain'],
            'module' => 'shop',
            'controller' => $data['controller'],
            'action' => $data['action'],
            'bind_type' => 1,
            'bind_id' => $data['cate_id'],
            'status' => $data['status'],
        ];

        if ($data['time'] == 1) {
            $insertData['start_time'] = $diyUrlData['start_time'] = strtotime($data['start_time']);
            $insertData['end_time'] = $diyUrlData['end_time'] = strtotime($data['end_time']);
        }
        if (!empty($data['param'])) {
            $insertData['param'] = $data['param'];
        }
        $cate = api('shop', 'Goods', 'categoryInfo', [$data['cate_id']]);
        Db::startTrans();
        try {

            if (empty($data['id'])) {
                // 放入专属域名库
                $bind_id = api('sys', 'Domain', 'insert', [$insertData]);
            } else {
                api('sys', 'Domain', 'update', [$data['id'], $insertData]);
            }
            if ($data['status'] == 1) {
                $url = $data['domain'];
            } else {
                if (!empty($cate['url_logo'])) {
                    $url = '/' . $cate['url_logo'];
                    api('sys', 'Domain', 'diyUrlInsert', [$cate['url_logo'], 'shop/Goods/goodslist?id=' . $cate['id']]);
                } else {
                    $url = rurl('shop/goods/goodslist', ['id' => $cate['id']]);
                }
            }
            $upUrl = Db::name('goods_category')->where('id', $data['cate_id'])->setField('url', $url);
            $cate['url'] = $url;
            cache('category:' .$cate['id'], $cate,'','system-category');
            Db::commit();
        } catch (\ErrorException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }


        $this->success($url);
    }


    /**
     * 分类添加
     * @param int $id
     * @return mixed
     */
    public function cate_add($id = 0)
    {
        $form['web_title'] = lang('cate_form_web_title');   // 页面标题
        $form['action'] = url('cate_save');        //表单提交的目的路径
        if ($id != 0) {
            $id = Db::name('goods_category')->where('id', $id)->value('parent_id_path');
        }
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('cate_form_field_name'), 'text', 'name', '', lang('cate_form_field_name_place'), lang('cate_form_field_name_tips'), [], 'required'],
            [lang('cate_form_field_phone'), 'text', 'mobile_name', '', lang('cate_form_field_phone_place'), lang('cate_form_field_phone_tips'), [], 'required'],
            [lang('cate_form_field_parent'), 'linkselect', 'pid', $id, '', lang('cate_form_field_parent_tips'), 'Goods/ajaxGetSubCate'],

            [lang('comm_btn_frame_feature_group_1'), 'select', 'attr_group', '', lang('user_form_role_place'), '', api('shop', 'FeatureGroup', 'featureSelectList', [1]), 'required'],

            [lang('cate_form_field_url'), 'text', 'url_logo', '', '', lang('cate_form_field_url_tips'), [], ''],
            [lang('cate_form_field_show'), 'radio', 'is_show', 1, '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('cate_form_field_command'), 'radio', 'is_hot', 0, '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('cate_form_field_img'), 'img', 'image'],
            [lang('cate_form_rate_total'), 'text', 'commission_rate', '', lang('cate_form_rate_total_place'), lang('cate_form_rate_total_tips')],
            [lang('cate_form_rate_plat'), 'text', 'plat_rate', '', lang('cate_form_rate_plat_place'), lang('cate_form_rate_plat_tips')],
            [lang('cate_form_field_sort'), 'text', 'sort', '100'],
            [lang('cate_form_index_tpl'), 'linkage', 'index_template', '', '', '', 'Goods/ajaxGetTpl'],
            [lang('cate_form_list_tpl'), 'linkage', 'list_template', '', '', '', 'Goods/ajaxGetTpl'],
            [lang('cate_form_detail_tpl'), 'linkage', 'detail_template', '', '', '', 'Goods/ajaxGetTpl'],


        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 保存分类数据
    public function cate_save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            foreach ($data['pid'] as $key => $val) {
                if (empty($val)) {
                    unset($data['pid'][$key]);
                }
            }
            $data['level'] = count($data['pid']) + 1;
            $parent_id_path = '0,' . implode(',', $data['pid']);
            $data['pid'] = end($data['pid']);
            $res = Db::name('goods_category')->insertGetId($data);
            api('shop','Goods','updateCategoryUrl',[$res]);
            if ($res !== false) {

                $result = Db::name('goods_category')->where('id', $res)->setField('parent_id_path', $parent_id_path . ',' . $res);

                if ($result !== false) {
                    sys_log(lang('cate_form_save_success'), 1);  //操作结果写入系统日志
                    $this->success(lang('cate_form_save_success'));
                } else {
                    Db::name('goods_category')->delete($res);
                    sys_log(lang('cate_form_save_error'), 0);  //操作结果写入系统日志
                    $this->error(lang('cate_form_save_error'));
                }
            } else {
                sys_log(lang('cate_form_save_error'), 0);  //操作结果写入系统日志
                $this->error(lang('cate_form_save_error'));
            }

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    /*编辑产品分类信息*/
    public function cate_edit($id)
    {
        $form['web_title'] = lang('cate_form_web_title');   // 页面标题
        $form['action'] = url('cate_update');        //表单提交的目的路径

        $cate = Db::name('goods_category')->find($id);
        if (!$cate) {
            $this->error(lang('comm_request_error'));
        }
        $pid_str = str_replace(',' . $id, '', $cate['parent_id_path']);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['', 'hidden', 'id', $id],
            [lang('cate_form_field_name'), 'text', 'name', $cate['name'], lang('cate_form_field_name_place'), lang('cate_form_field_name_tips'), [], 'required'],

            [lang('cate_form_field_phone'), 'text', 'mobile_name', $cate['mobile_name'], lang('cate_form_field_phone_place'), lang('cate_form_field_phone_tips'), [], 'required'],

            [lang('cate_form_field_parent'), 'linkselect', 'pid', $pid_str, '', lang('cate_form_field_parent_tips'), 'Goods/ajaxGetSubCate'],

            [lang('comm_btn_frame_feature_group_1'), 'select', 'attr_group', $cate['attr_group'], '', lang('cate_form_field_attr_group_tips'), api('shop', 'FeatureGroup', 'featureSelectList', [1]), 'required'],

            [lang('cate_form_field_url'), 'text', 'url_logo', $cate['url_logo'], '', lang('cate_form_field_url_tips'), [], ''],
            [lang('cate_form_field_show'), 'radio', 'is_show', $cate['is_show'], '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('cate_form_field_command'), 'radio', 'is_hot', $cate['is_hot'], '', '', [lang('comm_btn_disable'), lang('comm_btn_enable')]],
            [lang('cate_form_field_img'), 'img', 'image', $cate['image']],
            [lang('cate_form_rate_total'), 'text', 'commission_rate', $cate['commission_rate'], lang('cate_form_rate_total_place'), lang('cate_form_rate_total_tips')],
            [lang('cate_form_rate_plat'), 'text', 'plat_rate', $cate['plat_rate'], lang('cate_form_rate_plat_place'), lang('cate_form_rate_plat_tips')],
            [lang('cate_form_field_sort'), 'text', 'sort', $cate['sort']],
            [lang('cate_form_index_tpl'), 'linkage', 'index_template', $cate['index_template'], '', '', 'Goods/ajaxGetTpl'],
            [lang('cate_form_list_tpl'), 'linkage', 'list_template', $cate['list_template'], '', '', 'Goods/ajaxGetTpl'],
            [lang('cate_form_detail_tpl'), 'linkage', 'detail_template', $cate['detail_template'], '', '', 'Goods/ajaxGetTpl'],

        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    /*更新商品分类数据*/
    public function cate_update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $id = $data['id'];
            unset($data['id']);
            $data['level'] = count($data['pid']) + 1;
            $data['pid'] = array_filter($data['pid']);
            $parent_id_path = empty($data['pid']) ? '0' : '0,' . implode(',', $data['pid']);
            $data['parent_id_path'] = $parent_id_path . ',' . $id;
            $data['pid'] = end($data['pid']);
            if( !empty($data['pid']) ){
                $parentInfo = Db::name('goods_category')->where('id', $data['pid'])->find();
                $parentIdPathArr = explode(',',$parentInfo['parent_id_path']);
                if( in_array($id,$parentIdPathArr) ){
                    $this->error('上级分类选择错误');
                }
            }
            $res = Db::name('goods_category')->where('id', $id)->update($data);
            api('shop','Goods','updateCategoryUrl',[$id]);
            if ($res !== false) {
                sys_log(lang('cate_form_update_success'), 1);  //操作结果写入系统日志
                $this->success(lang('cate_form_update_success'));
            } else {
                sys_log(lang('cate_form_update_error'), 0);  //操作结果写入系统日志
                $this->error(lang('cate_form_update_error'));
            }

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    /*
    * 删除指定id的商品分类
    * param $id int 分类id
    * return json
    */
    public function cate_del($id)
    {
        if ($id) {
            $haveSub = Db::name('goods_category')->where('pid', $id)->find();
            if ($haveSub) {
                $this->error(lang('cate_list_del_error_exist', [$haveSub['name']]));
            } else {
                $res = Db::name('goods_category')->delete($id);
                if ($res !== false) {
                    sys_log(lang('comm_delete_success'), 1);  //操作结果写入系统日志
                    $this->success(lang('comm_delete_success'));
                } else {
                    sys_log(lang('comm_delete_error'), 0);  //操作结果写入系统日志
                    $this->error(lang('comm_delete_error'));
                }
            }

        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    /*切换分类指定字段状态*/
    public function cate_switch($id)
    {
        $data = input('');
        $res = Db::name('goods_category')->where('id', $id)->setField($data['field'], $data['val']);
        if ($res !== false) {
            $this->success(lang('comm_update_success'));
        } else {
            $this->error(lang('comm_update_error'));
        }
    }

    /*修改分类指定字段值*/
    public function cate_set_value($id)
    {
        $data = input('');
        $res = Db::name('goods_category')->where('id', $id)->setField($data['field_name'], $data['field']);
        if ($res !== false) {
            $this->success(lang('comm_update_success'));
        } else {
            $this->error(lang('comm_update_error'));
        }
    }

    /* ajax输出指定id的子分类列表,主要用于输出分类联动 */
    public function ajaxGetSubCate($val = 0, $web = 0)
    {
        if ($web == 0) {
            $cates = Db::name('goods_category')->where(['pid' => $val, 'is_show' => 1])->column('name', 'id');
            if ($cates) {
                return ['code' => 1, 'msg' => 'success', 'data' => $cates];
            } else {
                return ['code' => 0, 'msg' => 'Data null', 'data' => []];
            }
        } else {
            $cates = Db::name('goods_category')->where(['pid' => $val, 'is_show' => 1])->column('*', 'id');
            foreach ($cates as $key => $cate) {
                $cates[$key]['subs'] = Db::name('goods_category')->where('pid', $cate['id'])->count();
            }
            $this->assign('cates', $cates);
            return $this->fetch();
        }
    }

    // ajax方式获取分类模板文件列表
    public function ajaxGetTpl()
    {
        $theme_path = config('tpl_base') . '/' . tb_config('web_template', 1);
        $module = request()->module();
        $theme_path .= '/' . $module . '/skin';
        $allTpl = getFile($theme_path, false);
        foreach ($allTpl as $key => $value) {
            if (strpos($value, '__') === false) {
                $tpls[$value] = $value;
            }
        }
        return ['code' => 1, 'msg' => 'success', 'data' => $tpls];
    }

    /*ajax输出所有品牌列表 */
    public function ajaxGetBrand()
    {
        $brand_list = Db::name('shop_brand')->where(['trash' => 0, 'status' => 1])->column('name', 'id');
        if ($brand_list) {
            return ['code' => 1, 'msg' => 'Data null', 'data' => $brand_list];
        } else {
            return ['code' => 0, 'msg' => 'Data null', 'data' => []];
        }
    }

    /**
     * 获取商品详细信息
     * @param $id 商品ID
     * @return array
     */
    public function getGoods($id, $field = '')
    {

        $goods = Db::name('shop_goods')->where('id', $id)->field($field)->find();
        if (request()->isAjax()) {
            if (empty($goods)) {
                $this->error('商品不存在');
            }
            $goods['currency'] = tb_config('web_currency', 1, $this->lang);
            $this->success($goods);
        } else {
            return $goods;
        }
    }

    // 商品分类广告设置
    public function cate_adv($id)
    {
        // 是否显示表格的选择列？
        $this->assign('show_check', 0);

        $adv_type = tb_config('floor_ad_type', 1, $this->lang);

        $position = empty(request()->get('position')) ? '' : request()->get('position');

        // 筛选功用
        $filter = [
            'method' => 'get',
            'action' => 'Goods/cate_adv',
            'post_id' => 'position',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('cate_adv_select'), 'select', 'position', $position, '', '', $adv_type],
                ['', 'hidden', 'id', $id, '', '', ''],
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id', 'ID', 'text'],
            ['title', lang('cate_adv_title_title'), 'text'],
            ['img', lang('cate_adv_title_pic'), 'img'],
            ['sort', lang('cate_adv_title_sort'), 'input', 'Goods/adv_setval', 'id'],
            ['position', lang('cate_adv_title_type'), 'text'],
            ['is_show', lang('cate_adv_title_show'), 'switch', 'Goods/adv_setval', 'id'],
            ['url', lang('cate_adv_title_url'), 'text'],
            ['btn', lang('model_list_title_7'), 'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', 'Goods/adv_edit', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Goods/adv_del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Goods/adv_add', ['id' => $id]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $where = '';
        if( !empty($position) ){
            $where = '`position` = '.$position;
        }

        // 表格数据，分页，每页10条
        $advs = Db::name('goods_categoryimg') ->where($where) ->where('cid', $id)->order(['sort' => 'ASC', 'id' => 'DESC'])->paginate(tb_config('list_rows', 1, $this->lang));  //取出当前语言版本下的所有配置项
        $adv = $advs->all();

        $adv_type = tb_config('floor_ad_type', 1, $this->lang);

        foreach ($adv as $key => $ad) {
            $adv[$key]['position'] = $adv_type[$ad['position']];
        }
        $this->assign('data', $adv);
        // 获取分页显示
        $page = $advs->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    public function adv_add($id)
    {
        $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['action'] = url('adv_save');      //表单提交的目的路径

        $adv_type = tb_config('floor_ad_type', 1, $this->lang);

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['', 'hidden', 'cid', $id],
            [lang('cate_adv_form_title'), 'text', 'title'],
            [lang('cate_adv_form_vtitle'), 'text', 'v_title', '', lang('cate_adv_form_vtitle_place'), lang('cate_adv_form_vtitle_place')],
            [lang('cate_adv_form_show'), 'radio', 'is_show', 1, '', '', [lang('cate_adv_form_show_dis'), lang('cate_adv_form_show_en')], 'required'],
            [lang('cate_adv_form_type'), 'radio', 'position', 0, '', '', $adv_type, 'required'],
            [lang('cate_adv_form_img'), 'img', 'img'],
            [lang('cate_adv_form_url'), 'text', 'url', 'http://'],
            [lang('cate_adv_form_sort'), 'text', 'sort', '100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function adv_save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $res = Db::name('goods_categoryimg')->insertGetId($data);
            if ($res !== false) {
                sys_log(lang('comm_insert_success'), 1);  //操作结果写入系统日志
                $this->success(lang('comm_insert_success'));
            } else {
                sys_log(lang('comm_insert_error'), 0);  //操作结果写入系统日志
                $this->error(lang('comm_insert_error'));
            }
        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    public function adv_edit($id)
    {
        $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['action'] = url('adv_update');      //表单提交的目的路径

        $adv_type = tb_config('floor_ad_type', 1, $this->lang);

        $adv = Db::name('goods_categoryimg')->find($id);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['', 'hidden', 'id', $id],
            [lang('cate_adv_form_title'), 'text', 'title', $adv['title']],
            [lang('cate_adv_form_vtitle'), 'text', 'v_title', $adv['v_title'], lang('cate_adv_form_vtitle_place'), lang('cate_adv_form_vtitle_place')],
            [lang('cate_adv_form_show'), 'radio', 'is_show', $adv['is_show'], '', '', [lang('cate_adv_form_show_dis'), lang('cate_adv_form_show_en')], 'required'],
            [lang('cate_adv_form_type'), 'radio', 'position', $adv['position'], '', '', $adv_type, 'required'],
            [lang('cate_adv_form_img'), 'img', 'img', $adv['img']],
            [lang('cate_adv_form_url'), 'text', 'url', $adv['url']],
            [lang('cate_adv_form_sort'), 'text', 'sort', $adv['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function adv_update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            $res = Db::name('goods_categoryimg')->update($data);
            if ($res !== false) {
                sys_log(lang('comm_update_success'), 1);  //操作结果写入系统日志
                $this->success(lang('comm_update_success'));
            } else {
                sys_log(lang('comm_update_error'), 0);  //操作结果写入系统日志
                $this->error(lang('comm_update_error'));
            }
        } else {
            sys_log(lang('comm_request_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    public function adv_setval($id)
    {
        $data = input('');
        if (isset($data['field']) && isset($data['val'])) {
            $field = $data['field'];
            $value = $data['val'];
        } else {
            $field = $data['field_name'];
            $value = $data['field'];
        }

        $res = Db::name('goods_categoryimg')->where('id', $id)->setField($field, $value);
        if ($res !== false) {
            sys_log(lang('comm_update_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function adv_del($id)
    {
        $res = Db::name('goods_categoryimg')->delete($id);
        if ($res !== false) {
            sys_log(lang('comm_delete_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }



    /**
     * 更新商品分类链接
     * @return mixed
     */
    public function updateCategoryUrl()
    {
        $ids = request()->param('ids') ?: '';
        $update = api('shop','Goods','updateCategoryUrl',[$ids,1]);
        if ( $update['code'] == 0 ){
            sys_log('商品分类链接更新失败:'.$update['error'],1);
            $this->success('更新失败');
        }else{
            sys_log('商品分类链接更新成功',1);
            $this->success('更新成功');
        }
    }


    /**
     * 更新商品链接
     * @return mixed
     */
    public function updateGoodsUrl()
    {
        if( empty(request()->param('type')) && request()->param('type') == 'all' ){
            $ids = '';
        }else{
            $data = request()->param();
            $ids = empty($data['id']) ?: $data['id'];
        }
        $update = api('shop','Goods','updateGoodsUrl',[$ids,1]);
        if ( $update['code'] == 0 ){
            sys_log('商品链接更新失败:'.$update['error'],1);
            $this->success('更新失败:'.$update['error']);
        }else{
            sys_log('商品链接更新成功',1);
            $this->success('更新成功');
        }
    }

}