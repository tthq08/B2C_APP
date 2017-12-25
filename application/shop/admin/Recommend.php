<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/20
 * Time: 上午10:13
 */

namespace app\shop\admin;


use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Recommend extends AdminBase
{


    /**
     * 推荐位列表
     * @return mixed
     */
    public function index()
    {
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        $model = 'goods';

        // 筛选功用
        $filter = [
            'method' => 'get',
            'action' => 'Recommend/ajaxFilter',
            'post_id' => 'recommend_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('comm_filter_key_title'), 'text', 'key', '', '推荐位名称']
            ]
        ];

        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'], true);
            $table_head['btn'] = ['btn', lang('content_list_title_7'), 'btn'];
        } else {
            $table_head = [
                ['id', 'ID', 'text'],
                ['name', lang('推荐位名称'), 'text'],
                ['position', lang('调用标识'), 'text'],
                ['cate_id', lang('所属分类'), 'text'],
                ['goods_num', lang('商品数量'), 'text'],
                ['img', lang('显示图片'), 'img'],
                ['update_time', lang('content_list_title_4'), 'text'],
                ['sort', lang('content_list_title_5'), 'input', 'content/sort', $model . '_id'],
                ['status', lang('content_list_title_6'), 'switch', 'content/switchs', $model . '_id'],
                ['btn', lang('content_list_title_7'), 'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        $btn = [
            [lang('查看推荐商品'), 'frame', lang('查看推荐商品'), 'fa fa-fw fa-th', 'layui-btn-normal', 'Recommend/goodsList', 'id'],
            [lang('修改推荐位'), 'frame', lang('修改推荐位'), 'fa fa-fw fa-pencil-square-o', 'layui-btn-normal', 'Recommend/edit', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Recommend/del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $top_btn = [
            [lang('comm_btn_add'), 'frame', lang('comm_add_frame_title'), 'fa fa-fw fa-plus', 'layui-btn-normal', 'Recommend/add'],
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Recommend/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);
        $recommenList = api('shop','Recommend','lists');
        $this->assign('data', $recommenList['list']);
        // 获取分页显示
        $this->assign('page', $recommenList['page']);
        return $this->fetch('sys@Base/table');
    }


    /**
     * 添加推荐位
     * @return mixed
     */
    public function add()
    {
        $form['web_title'] = lang('添加规格');   // 页面标题
        $form['action'] = url('save');		//表单提交的目的路径

        $form_fields = [
            [lang('推荐位名称'),'text','name','',lang('推荐位名称'),lang('推荐位名称'),[],'required'],

            [lang('模板调用标识'),'text','position','',lang('模板调用标识'),lang('模板调用标识'),[]],

            [lang('所属分类'),'linkselect','cate_tree',0,'',lang('所属分类'),'Goods/ajaxGetSubCate'],

            [lang('推荐位图片'),'img','img','','','',''],

            [lang('model_form_field_sort'),'text','sort','100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }


    /**
     * 保存推荐位
     * @return mixed
     */
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
            $data['add_time'] = request()->time();
            $data['update_time'] = request()->time();
            $res = Db::name('shop_recommend') ->insertGetId($data);
            if ($res!==false) {
                sys_log(lang('推荐位新增成功'),1);  //操作结果写入系统日志
                $this->success(lang('推荐位新增成功'));
            } else {
                sys_log(lang('推荐位新增失败'),0);  //操作结果写入系统日志
                $this->error(lang('推荐位新增失败'));
            }

        }else{
            sys_log(lang('推荐位新增失败'),0);  //操作结果写入系统日志
            $this->error(lang('推荐位新增失败'));
        }
    }


    /**
     * 编辑推荐位
     * @return mixed
     */
    public function edit()
    {
        $id = request()->param('id');
        $info = api('shop','Recommend','recommendInfo',[$id]);
        $form['web_title'] = lang('修改推荐位');   // 页面标题
        $form['action'] = url('update');		//表单提交的目的路径

        $form_fields = [
            ['','hidden','id',$info['id'],'','',[],'required'],

            [lang('推荐位名称'),'text','name',$info['name'],lang('推荐位名称'),lang('推荐位名称'),[],'required'],

            [lang('模板调用标识'),'text','position',$info['position'],lang('模板调用标识'),lang('模板调用标识'),[]],

            [lang('所属分类'),'linkselect','cate_tree',$info['cate_tree'],'',lang('所属分类'),'Goods/ajaxGetSubCate','required'],

            [lang('推荐位图片'),'img','img',$info['img'],'','',''],

            [lang('model_form_field_sort'),'text','sort',$info['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }


    /**
     * 更新推荐位信息
     * @return mixed
     */
    public function update()
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
            $data['update_time'] = request()->time();
            $res = Db::name('shop_recommend') ->where('id',$data['id']) ->update($data);
            if ($res!==false) {
                sys_log(lang('推荐位更新成功'),1);  //操作结果写入系统日志
                $this->success(lang('推荐位更新成功'));
            } else {
                sys_log(lang('推荐位更新失败'),0);  //操作结果写入系统日志
                $this->error(lang('推荐位更新失败'));
            }

        }else{
            sys_log(lang('推荐位更新失败'),0);  //操作结果写入系统日志
            $this->error(lang('推荐位更新失败'));
        }
    }


    /*
     * 删除规格
     * @param $id int 规格ID
     * @return mixed
     */
    public function del($id)
    {
        // 查看内部是否有商品
        $goodsCount = api('shop','Recommend','getGoodsNum',[$id]);
        if( !empty($goodsCount) ){
            $this->error('当前推荐位中尚有商品,请将该推荐位的商品取消推荐后删除。');
        }
        $res =	Db::name('shop_recommend') ->where('id',$id) ->update(['trash'=>1]);
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    /**
     * 批量删除规格
     * @return mixed
     */
    public function dels()
    {
        $data = input('');
        foreach ($data['id'] as $key=>$id){
            $goodsCount = api('shop','Recommend','getGoodsNum',[$id]);
            if( !empty($goodsCount) ){
                $recommend = api('shop','Recommend','recommendInfo',$id);
                $this->error('推荐位['.$recommend['name'].']中尚有商品,请将该推荐位的商品取消推荐后删除。');
            }
        }
        $ids = implode(',', $data['id']);
        $res = Db::name('shop_recommend') ->where('id','IN',$ids) ->update(['trash'=>1]);
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }


    /**
     * 查看推荐位内的商品
     * @return mixed
     */
    public function goodsList()
    {
        $id = request()->param('id');
        // 是否显示表格的选择列？
        $table['show_check'] = 1;

        // 筛选功用
        $filter = [
            'method' => 'get',
            'action' => 'Recommend/ajaxGoodsFilter',
            'post_id' => 'goods_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('comm_filter_key_title'), 'text', 'key', '', '商品名称']
            ]
        ];

        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'], true);
            $table_head['btn'] = ['btn', lang('content_list_title_7'), 'btn'];
        } else {
            $table_head = [
                ['id', 'ID', 'text'],
                ['goods_title', lang('商品名称'), 'text'],
                ['start_time', lang('开始推荐时间'), 'text'],
                ['end_time', lang('结束推荐时间'), 'text'],
                ['sort', lang('content_list_title_5'), 'input', 'Recommend/sort','id'],
                ['status', lang('content_list_title_6'), 'switch', 'Recommend/switchs', 'id'],
                ['btn', lang('content_list_title_7'), 'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        $btn = [
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Recommend/delGoods', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $top_btn = [
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Recommend/delGoods'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);
        $recommenList = api('shop','Recommend','goodsLists',[$id]);
        $this->assign('data', $recommenList['list']);
        // 获取分页显示
        $this->assign('page', $recommenList['page']);
        return $this->fetch('sys@Base/table');
    }


    /**
     * 删除推荐位中的商品
     * @return mixed
     */
    public function delGoods()
    {

    }


    /**
     * 修改状态
     * @return mixed
     */
    public function switchs()
    {

    }


    /**
     * 修改排序
     * @return mixed
     */
    public function sort()
    {

    }
}