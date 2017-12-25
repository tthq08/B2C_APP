<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块品牌控制器 
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

class Brand extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }


    public function index($model)
    {
        // 是否显示表格的选择列？
        $table['show_check'] = 1;

        $model_info = Db::name('shop_model')->where('name', $model)->find();
        if (!empty($model_info['handle_func'])) {
            $actions = json_decode($model_info['handle_func'], true);
            $action_arr = [];
            foreach ($actions as $key => $act) {
                $act_arr = explode(':', $act[0]);
                $action_arr[$act_arr[0]] = $act_arr[1];
            }
        }
        $ajaxValue = [];
        $table_head = json_decode($model_info['table_fields'], true);
        foreach ($table_head as $key => $head) {
            if ($head[2] == 'text' && isset($head[3])) {
                $ajaxValue[$head[0]] = ['field' => $head[0], 'action' => $head[3], 'param' => $head[4]];
            }
        }
        $table_head['btn'] = ['btn', lang('content_list_title_7'), 'btn'];

        $table['tb_title'] = JCreater::table_header($table_head);

        $act_edit = isset($action_arr['edit']) ? $action_arr['edit'] : 'Brand/edit';
        $act_del = isset($action_arr['del']) ? $action_arr['del'] : 'Brand/del';
        $btn = [
            [lang('comm_btn_edit'), 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', $act_edit,'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_del,'id'],
        ];

        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $act_add = isset($action_arr['add']) ? $action_arr['add'] : 'Brand/add';
        $act_dels = isset($action_arr['dels']) ? $action_arr['dels'] : 'Brand/dels';

        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', $act_add],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', $act_dels],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        if ($model_info['type'] == 2) {
            $dbContent = Db::name(str_replace(config('database.prefix'), '', $model_info['table']));
        } else {
            $dbContent = Db::name('shop_content');
        }

        $conten = $dbContent->where(['model' => $model_info['id'], 'trash' => 0])->order(['id' => 'DESC'])->paginate(tb_config('list_rows', 1, $this->lang));
        //取出当前语言版本下的所有记录
        $content_list = $conten->all();

        $this->assign('data', $content_list);
        // 获取分页显示
        $page = $conten->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    /**
     * 添加品牌
     * @return mixed
     */
    public function add()
    {

        $actionUrl = url('save');
        $form['action'] = $actionUrl;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题

        $this->assign($form);
        return $this->fetch();
    }


    /**
     * 编辑品牌
     * @param int $id
     * @return mixed
     */
    public function edit($id)
    {
        $actionUrl = url('update');
        $form['action'] = $actionUrl;      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题

        $info = api('shop','Goods','getBrand',[$id,true]);

        $category = array_filter(explode(',',$info['cate']));
        $info['cate'] = [];
        $cateList = [];
        foreach ($category as $item=>$cate){
            // 分类树
            $cateTree = getTableValue('goods_category','id='.$cate,'parent_id_path');
            $cateTree = array_filter(explode(',',$cateTree));
            $cateTreeArr = [];
            $i = 1;
            foreach ($cateTree as $key=>$value){
                $cateTreeArr[$i] = api('shop','Goods','categoryInfo',[$value]);
                $cateTreeArr[$i]['list'] = api('shop','Goods','category',[$cateTreeArr[$i]['pid']]);
                $i++;
            }
            $cateList[$item] = $cateTreeArr;
        }
        $info['cate'] = $cateList;
        $info['count_cate'] = count($cateList);

        $form['info'] = $info;
        $this->assign($form);
        return $this->fetch();
    }


    /**
     * 保存品牌
     * @return mixed
     */
    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if( empty($data['name']) ){
                $this->error('请填写品牌名称!');
            }

            $category = [];
            if ( !empty($data['category']) ){
                foreach ($data['category'] as $key => $cate){
                    $cate = array_filter($cate);
                    $category[] = end($cate);
                }
            }
            $data['cate'] = implode(',',$category);
            unset($data['category']);

            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['model'] = 4;
            // dump($data);die;
            $res = Db::name('shop_brand')->insert($data);

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


    /**
     * 更新品牌信息
     * @return mixed
     */
    public function update()
    {
        $data = $this->post_data;
        if( empty($data['name']) ){
            $this->error('请填写品牌名称!');
        }

        $category = [];
        if ( !empty($data['category']) ){
            foreach ($data['category'] as $key => $cate){
                $cate = array_filter($cate);
                if( !empty($cate) ){
                    if( !in_array($cate,$category) ){
                        $category[] = end($cate);
                    }
                }
            }
        }
        $data['cate'] = implode(',',$category);
        unset($data['category']);

        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['model'] = 4;
        // dump($data);die;
        $res = Db::name('shop_brand')->where('id',$data['id'])->update($data);

        if ($res !== false) {
            sys_log(lang('comm_update_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }


    public function del()
    {
        $data = input();
        $dbContent = Db::name('shop_brand');
        $res = $dbContent ->where('id',$data['id']) ->setField('trash',1);

        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }


    /**
     * 批量删除
     * @param $model
     */
    public function dels()
    {
        $data = input();
        $ids =  implode(',', $data['id']);
        $dbContent = Db::name('shop_brand');
        $res = $dbContent ->where('id','in',$ids) ->setField('trash',1);

        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }
}