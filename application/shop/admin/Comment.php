<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/25
 * Time: 下午5:41
 */

namespace app\shop\admin;


use app\sys\controller\AdminBase;
use think\Db;
use app\common\JunCreater\JCreater;

class Comment extends AdminBase
{


    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        // 是否显示表格的选择列？
        $table['show_check'] = 1;

        $where = '';
        $goods = '';
        if( !empty(request()->param('goods')) ){
            $goods = request()->param('goods');
            if( is_numeric($goods) ){
                $where = '`goods_id`='.$goods;
            }else{
                $goods_id_list = Db::name('shop_goods')->where('title','like','%'.$goods.'%')->column('id');
                if( !empty($goods_id_list) ){
                    $where = '`goods_id` in('.implode(',',$goods_id_list).')';
                }
            }
        }

        // 筛选功用
        $filter = [
            'method' => 'get',
            'action' => 'Comment/index',
            'post_id' => 'goods_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                ['输入商品ID或标题进行筛选', 'text', 'goods', $goods]
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'], true);
            $table_head['btn'] = ['btn', lang('content_list_title_7'), 'btn'];
        } else{
            $table_head = [
                ['id', 'ID', 'text'],
                ['user_id', '评价用户', 'text'],
                ['goods_id', '被评价的商品', 'text'],
                ['content', '评价的内容', 'text'],
                ['goods_rank', '评价等级', 'text'],
                ['add_time', '评价的时间', 'text'],
                ['is_show', '是否显示', 'switch', 'comment/switchs', 'id'],
                ['btn', lang('content_list_title_7'), 'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        $btn = [
            ['查看评价', 'frame', lang('comm_edit_frame_title'), 'fa fa-fw fa-eye', 'layui-btn-normal', 'Comment/show', 'id'],
            [lang('comm_btn_del'), 'confirm', lang('comm_del_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Comment/del', 'id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $top_btn = [
            [lang('comm_btn_dels'), 'confirm_form', lang('comm_dels_confirm_msg'), 'fa fa-fw fa-trash-o', 'layui-btn-danger', 'Comment/del'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $conten = Db::name('shop_goods_comment')->where($where)->order(['id' => 'DESC'])->paginate(tb_config('list_rows',1));  //取出当前语言版本下的所有记录
        $content_list = $conten->all();

        foreach ($content_list as $key=>$comment){
            $userName = getTableValue('users','id='.$comment['user_id'],'email');

            $content_list[$key]['user_id'] = '<a href="'.url('member/index/edit',['id'=>$comment['user_id']]).'" target="_black">'.$userName.'</a>';

            $goodsTitle = getTableValue('shop_goods','id='.$comment['goods_id'],'title');
            $content_list[$key]['goods_id'] = '<a href="'.rurl('shop/goods/goodsinfo',['id'=>$comment['goods_id']]).'" target="_black">'.$goodsTitle.'</a>';

            if( $comment['goods_rank'] == 3 ){
                $content_list[$key]['goods_rank'] = '好评';
            }elseif ( $comment['goods_rank'] == 2 ){
                $content_list[$key]['goods_rank'] = '中评';
            }elseif( $comment['goods_rank'] == 1 ){
                $content_list[$key]['goods_rank'] = '差评';
            }
        }


        $this->assign('data', $content_list);
        // 获取分页显示
        $page = $conten->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    /**
     *
     */
    public function show($id)
    {

        return $this->fetch();
    }


    /**
     * 优惠商品删除，根据管理员权限设置删除方式
     */
    public function del($id = '')
    {
        //获取要删除的总表ID
        $res = Db::name('shop_goods_comment')->where('id ','in',$id)->delete();
        //删除当前促销
        if ($res !== false) {
            sys_log(lang('comm_delete_success'), 1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        }else{
            sys_log(lang('comm_delete_error'), 0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }


    /**
     *
     */
    public function switchs()
    {
        if (request()->isPost()) {
            $data = input();

            $res = Db::name('shop_goods_comment')->where('id', $data['id'])->setField('is_show', $data['val']);

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
}