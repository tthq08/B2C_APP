<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块后台主控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Ad extends AdminBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    // 广告列表
    public function adlist($id=0)
    {
        $data = request()->param();
        $whereFilter = '';
        if( !empty($data['position']) ){
            $whereFilter = '`pid`='.$data['position'];
        }
        if( !empty($data['ad_name']) ){
            $whereFilter = empty($where) ? '' : ' and ';
            $whereFilter .= '`ad_name` like "%'.$data['ad_name'].'%" or `ad_title` like "%'.$data['ad_name'].'%"';
        }
        // 是否显示表格的选择列？
        $this->assign('show_check',1);

        // 获取广告位
        $adPosition = Db::name('admin_ad_position')->order('sort')->field('id,position_name,position')->select();
        $positions = [];
        foreach ($adPosition as $key=>$position){
            $positions[$position['id']] = $position['position_name'].'_'.$position['position'];
        }

        // 筛选
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'order_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                ['','select','position',isset($data['position'])?$data['position']:'','','',$positions],
                ['名称&标题','text','ad_name',isset($data['ad_name'])?$data['ad_name']:'','名称&标题'],
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);
        // 表格数据
        $table_head = [
            ['id','ID','text'],
            ['ad_name',lang('ad_list_table_name'),'text'],
            ['pid',lang('ad_list_table_position'),'text'],
            ['ad_pic',lang('ad_list_table_img'),'img'],
            ['ad_link',lang('ad_list_table_url'),'text'],
            ['target',lang('ad_list_table_target'),'switch','Ad/setval','id'],
            ['isdspy',lang('ad_list_table_show'),'switch','Ad/setval','id'],
            ['sort',lang('ad_list_table_sort'),'input','Ad/setval','id'],
            ['btn',lang('ad_list_table_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Ad/editad','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delad','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Ad/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $Ad = Db::name('admin_ad') ->where($whereFilter) ->order(['sort'=>'ASC','id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $ad_list = $Ad ->all();
        $Position = Db::name('admin_ad_position')->select();
        foreach($ad_list as $k =>$v){
            foreach($Position as $k1 => $v1){
                if($v['pid']==$v1['id']){
                    $ad_list[$k]['pid'] = $Position[$k1]['position_name'];
                }
            }
        }
        $page = $Ad->render();
        $this->assign('data',$ad_list);
        $this->assign('page', $page);

        return $this->fetch('sys@Base/table');
    }

    // 添加广告
    public function add()
    {
        $form['web_title'] = lang('ad_form_add_title');   // 页面标题
        $form['action'] = url('save');		//表单提交的目的路径

        $position = Db::name('admin_ad_position') ->where('status',1) ->column('position_name','id');
        $media_type = Db::name('admin_ad') ->column('media_type','id');
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('ad_form_name'),'text','ad_name','',lang('ad_form_name_place'),lang('ad_form_name_tips'),[],'required'],
            [lang('ad_form_title'),'text','ad_title','',lang('ad_form_title_place'),lang('ad_form_title_tips'),[],'required'],
            [lang('ad_form_pid'),'select','pid',0,'',lang('ad_form_pid_tips'),$position,'required'],
            [lang('ad_form_start_time'),'datetime','start_time','','',lang('ad_form_start_time_tips'),[],'required'],
            [lang('ad_form_end_time'),'datetime','end_time','','',lang('ad_form_end_time_tips'),[],'required'],
            [lang('ad_form_pic'),'img','ad_pic'],
            [lang('ad_form_video'),'text','ad_video','http://',lang('ad_form_video_place'),lang('ad_form_video_tips')],
            [lang('ad_form_link'),'text','ad_link','http://','',lang('ad_form_link_tips')],
            [lang('ad_form_target'),'radio','target',0,'',lang('ad_form_target_tips'),[lang('ad_form_target_target_0'),lang('ad_form_target_target_1')]],
            [lang('ad_form_isdisplay'),'radio','isdspy',0,'',lang('ad_form_isdisplay_tips'),[lang('ad_form_isdisplay_opt_1'),lang('ad_form_isdisplay_opt_2')]],
            [lang('ad_form_sort'),'text','sort','50'],
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
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $res = Db::name('admin_ad') ->insert($data);
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

    // 编辑广告
    public function editad($id)
    {
        $form['web_title'] = lang('ad_form_edit_title');   // 页面标题
        $form['action'] = url('update');      //表单提交的目的路径

        $ad = Db::name('admin_ad') ->find($id);

        $position = Db::name('admin_ad_position') ->where('status',1) ->column('position_name','id');
        $media_type = Db::name('admin_ad') ->column('media_type','id');
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('ad_form_name'),'text','ad_name',$ad['ad_name'],lang('ad_form_name_place'),lang('ad_form_name_tips'),[],'required'],
            [lang('ad_form_title'),'text','ad_title',$ad['ad_title'],lang('ad_form_title_place'),lang('ad_form_title_tips'),[],'required'],
            [lang('ad_form_pid'),'select','pid',$ad['pid'],'',lang('ad_form_pid_tips'),$position,'required'],
            [lang('ad_form_start_time'),'datetime','start_time',date('Y-m-d H:i:s',$ad['start_time']),'',lang('ad_form_start_time_tips'),[],'required'],
            [lang('ad_form_end_time'),'datetime','end_time',date('Y-m-d H:i:s',$ad['end_time']),'',lang('ad_form_end_time_tips'),[],'required'],
            [lang('ad_form_pic'),'img','ad_pic',$ad['ad_pic']],
            [lang('ad_form_video'),'text','ad_video',$ad['ad_video'],lang('ad_form_video_place'),lang('ad_form_video_tips')],
            [lang('ad_form_link'),'text','ad_link',$ad['ad_link'],'',lang('ad_form_link_tips')],
            [lang('ad_form_target'),'radio','target',$ad['target'],'',lang('ad_form_target_tips'),[lang('ad_form_target_target_0'),lang('ad_form_target_target_1')]],
            [lang('ad_form_isdisplay'),'radio','isdspy',$ad['isdspy'],'',lang('ad_form_isdisplay_tips'),[lang('ad_form_isdisplay_opt_1'),lang('ad_form_isdisplay_opt_2')]],
            [lang('ad_form_sort'),'text','sort',$ad['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 更新广告数据
    public function update()
    {
        if (request()->isPost()) {
            $data = $this -> post_data;
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $res = Db::name('admin_ad') ->update($data);
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

    // 修改广告字段值
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
        
        $res =  Db::name('admin_ad') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    // 删除广告
    public function delad($id)
    {
        if(empty($id)){
            $this->error(lang('comm_delete_error'));
        }
        $res =	Db::name('admin_ad') ->delete($id);
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
        $res =  Db::name('admin_ad') ->where('id','IN',$ids) ->delete();
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }



    // 广告位列表
    public function positionlist()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['position_name',lang('adpst_list_table_name'),'text'],
            ['position',lang('adpst_list_table_position'),'text'],
            ['ad_width',lang('adpst_list_table_width'),'text'],
            ['ad_height',lang('adpst_list_table_height'),'text'],
            ['position_desc',lang('adpst_list_table_desc'),'text'],
            ['status',lang('adpst_list_table_statute'),'switch','Ad/pst_setval','id'],
            ['sort',lang('adpst_list_table_sort'),'input','Ad/pst_setval','id'],
            // ['attach',lang('adpst_form_attach'),'input','Ad/pst_setval','id'],
            ['btn',lang('ad_list_table_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('adpst_list_row_btn_show'),'frame',lang('adpst_list_row_btn_show'),'fa fa-fw fa-eye','layui-btn-normal','Ad/adlist','id'],
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Ad/editpst','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delpst','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Ad/addpst'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delspst'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $Ad = Db::name('admin_ad_position') ->order(['sort'=>'ASC','id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $ad_list = $Ad ->all();

        $page = $Ad->render();
        $this->assign('data',$ad_list);
        $this->assign('page', $page);

        return $this->fetch('sys@Base/table');
    }

    // 添加广告位
    public function addpst()
    {
        $form['web_title'] = lang('adpst_form_add_name');   // 页面标题
        $form['action'] = url('savepst');      //表单提交的目的路径

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('adpst_form_name'),'text','position_name','','','',[],'required'],
            [lang('adpst_form_position'),'text','position','','','',[],'required'],
            [lang('adpst_form_width'),'number','ad_width','','',lang('adpst_form_width_tips')],
            [lang('adpst_form_height'),'number','ad_height','','',lang('adpst_form_height_tips')],
            [lang('adpst_form_desc'),'textarea','position_desc'],
            [lang('adpst_form_status'),'radio','status',1,'','',[lang('adpst_form_status_disable'),lang('adpst_form_status_enable')]],
            [lang('adpst_form_sort'),'text','sort','50'],
            [lang('adpst_form_attach'),'text','attach','','',lang('adpst_form_default')],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function savepst()
    {
        if (request()->isPost()) {
            $data = $this -> post_data;
            $res = Db::name('admin_ad_position') ->insert($data);
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

    // 编辑广告位
    public function editpst($id)
    {
        $form['web_title'] = lang('adpst_form_edit_name');   // 页面标题
        $form['action'] = url('updatepst');      //表单提交的目的路径

        $position = Db::name('admin_ad_position') ->find($id);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('adpst_form_name'),'text','position_name',$position['position_name'],'','',[],'required'],
            [lang('adpst_form_position'),'text','position',$position['position'],'','',[],'required'],
            [lang('adpst_form_width'),'number','ad_width',$position['ad_width'],'',lang('adpst_form_width_tips')],
            [lang('adpst_form_height'),'number','ad_height',$position['ad_height'],'',lang('adpst_form_height_tips')],
            [lang('adpst_form_desc'),'textarea','position_desc',$position['position_desc']],
            [lang('adpst_form_status'),'radio','status',$position['status'],'','',[lang('adpst_form_status_disable'),lang('adpst_form_status_enable')]],
            [lang('adpst_form_sort'),'text','sort',$position['sort']],
            [lang('adpst_form_attach'),'text','attach',$position['attach']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 更新广告位数据
    public function updatepst()
    {
        if (request()->isPost()) {
            $data = $this -> post_data;
            $res = Db::name('admin_ad_position') ->update($data);
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


    // 修改广告位字段值
    public function pst_setval($id)
    {
        $data = input('');
        if (isset($data['field']) && isset($data['val'])) {
            $field = $data['field'];
            $value = $data['val'];
        } else {
            $field = $data['field_name'];
            $value = $data['field'];
        }
        
        $res =  Db::name('admin_ad_position') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    // 删除广告
    public function delpst($id)
    {
        if(empty($id)){
            $this->error(lang('comm_delete_error'));
        }

        // 阻止仍有广告的广告位删除
        $isexist = Db::name('admin_ad') ->where('pid',$id) ->find();
        if ($isexist!==false) {
            $this->error(lang('adpst_list_del_error_exist',[$isexist['ad_name']]));
        }
        $res =  Db::name('admin_ad_position') ->delete($id);
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    public function delspst()
    {
        $data = input('');
        $ids = implode(',', $data['id']);
        if(empty($ids)){
            $this->error(lang('comm_delete_error'));
        }
        $isexist = Db::name('admin_ad') ->where('pid','IN',$ids) ->find();
        if ($isexist !== null) {
            $this->error(lang('adpst_list_del_error_exist',[$isexist['ad_name']]));
        }
        $res =  Db::name('admin_ad_position') ->where('id','IN',$ids) ->delete();
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

}
