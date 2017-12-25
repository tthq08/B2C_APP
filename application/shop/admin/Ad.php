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

namespace app\shop\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Ad extends AdminBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 自定义导航位
     * @return mixed
     */
    public function positionList()
    {
        $this->assign('show_check',1);
        $table_head = [
            ['id','ID','text'],
            ['name',lang('nav_position_table_name'),'text'],
            ['position',lang('nav_position_table_position'),'text'],
            ['start_time',lang('nav_position_table_start_time'),'text'],
            ['end_time',lang('nav_position_table_end_time'),'text'],
            ['btn',lang('ad_list_table_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        $btn = [
            [lang('nav_btn_list'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-align-left','layui-btn-normal','Ad/navlist','id'],
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Ad/editPosition','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delPosition','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $top_btn = [
            [lang('position_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Ad/addPosition'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delPosition'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $adPosition = Db::name('shop_nav_position') ->order('id desc') ->paginate(tb_config('list_rows',1,$this->lang));

        $ad_position_list = $adPosition ->all();

        foreach ($ad_position_list as $key=>$item) {
            if( !empty($item['start_time']) ){
                $ad_position_list[$key]['start_time'] = datetime_format($item['start_time']);
                $ad_position_list[$key]['end_time'] = datetime_format($item['end_time']);
            }else{
                $ad_position_list[$key]['start_time'] = '永久';
                $ad_position_list[$key]['end_time'] = '';
            }

        }

        $page = $adPosition->render();
        $this->assign('data',$ad_position_list);
        $this->assign('page', $page);

        return $this->fetch('sys@Base/table');
    }


    /**
     * 添加导航位
     * @return mixed
     */
    public function addPosition()
    {
        $form['web_title'] = lang('nav_position_form_add_title');   // 页面标题
        $form['action'] = url('savePosition');      //表单提交的目的路径

        $this->assign($form);
        return $this->fetch('');
    }


    /**
     * 编辑广告位
     * @param $id
     * @return mixed
     */
    public function editPosition($id)
    {
        $form['web_title'] = lang('nav_position_form_edit_title');   // 页面标题
        $form['action'] = url('savePosition');      //表单提交的目的路径

        $position = api('shop','Nav','getPosition',$id);
        $position['start_time'] = datetime_format($position['start_time']);
        $position['end_time'] = datetime_format($position['end_time']);

        if( empty($position) ){
            $this->error('该导航位不存在');
        }
        $this->assign($form);
        $this->assign('position',$position);
        return $this->fetch();
    }


    /**
     * 保存导航位
     * @return mixed
     */
    public function savePosition()
    {
        $data = $this->post_data;
        $rule = ['name' => 'require'];
        $message = ['name.require'=>'请输入导航位的名称'];
        $check = api('sys','Verification','valiCheck',[$rule,$data,$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        $saveData = [
            'name' => $data['name'],
            'position' => $data['position'],
            'icon' => $data['icon'],
            'start_time' => strtotime($data['start_time']),
            'end_time' => strtotime($data['end_time']),
            'status' => $data['status'],
        ];
        // 检测是否有重复
        if( !empty($saveData['position']) ) {
            $checkRepeat = api('shop','Nav','getFromPosition',[$saveData['position']]);
            if( !empty($checkRepeat) ) {
                if (empty($data['id'])) {
                    $this->error('该模板调用标识已被使用,请重新选择/输入。');
                } else {
                    if ($data['id'] != $checkRepeat['id']) {
                        $this->error('该模板调用标识已被使用,请重新选择/输入。');
                    }
                }
            }

        }
        // 保存
        if( empty($data['id']) ) {
            $save = api('shop','Nav','savePosition',[$saveData]);
        }else {
            $save = api('shop','Nav','updatePosition',[$data['id'],$saveData]);
        }
        if( $save === true ){
            $this->success('保存成功!');
        }
        $this->error('添加失败!');
    }


    /**
     * 删除导航位
     * @return mixed
     */
    public function delPosition()
    {
        $param = request()->param();
        $del = api('shop','Nav','delPosition',[$param['id']]);
        if( $del === true ){
            $this->success('删除成功!');
        }
        $this->error('删除失败!');
    }




    // =======================================================================================
    //                                     自定义导航管理
    // =======================================================================================
    /**
     * 自定义导航列表
     * @param int $id 导航位id
     * @return mixed
     */
    public function navlist()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['name',lang('nav_list_table_name'),'text'],
            ['url',lang('nav_list_table_url'),'text'],
            ['is_show',lang('nav_list_table_show'),'switch','Ad/nav_setval','id'],
            ['is_new',lang('nav_list_table_target'),'switch','Ad/nav_setval','id'],
            ['sort',lang('nav_list_table_sort'),'input','Ad/nav_setval','id'],
            ['btn',lang('ad_list_table_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Ad/editnav','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delnav','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('nav_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Ad/addnav'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Ad/delsnav'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $Ad = Db::name('shop_nav') ->order(['sort'=>'ASC','id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $ad_list = $Ad ->all();

        $page = $Ad->render();
        $this->assign('data',$ad_list);
        $this->assign('page', $page);

        return $this->fetch('sys@Base/table');
    }

    // 添加导航
    public function addnav()
    {
        $form['web_title'] = lang('nav_form_add_title');   // 页面标题
        $form['action'] = url('savenav');      //表单提交的目的路径

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('nav_form_field_name'),'text','name','','','',[],'required'],
            [lang('nav_form_field_url'),'text','url','','','',[],'required'],
            [lang('nav_form_field_show'),'radio','is_show',1,'','',[lang('nav_form_opt_disable'),lang('nav_form_opt_enable')]],
            [lang('nav_form_field_target'),'radio','is_new',0,'','',[lang('nav_form_opt_disable'),lang('nav_form_opt_enable')]],
            [lang('nav_form_field_sort'),'text','sort','50'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function savenav()
    {
        if (request()->isPost()) {
            $data = $this -> post_data;
            $res = Db::name('shop_nav') ->insert($data);
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

    // 编辑导航
    public function editnav($id)
    {
        $form['web_title'] = lang('nav_form_edit_title');   // 页面标题
        $form['action'] = url('updatenav');      //表单提交的目的路径

        $nav = Db::name('shop_nav') ->find($id);
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('nav_form_field_name'),'text','name',$nav['name'],'','',[],'required'],
            [lang('nav_form_field_url'),'text','url',$nav['url'],'','',[],'required'],
            [lang('nav_form_field_show'),'radio','is_show',$nav['is_show'],'','',[lang('nav_form_opt_disable'),lang('nav_form_opt_enable')]],
            [lang('nav_form_field_target'),'radio','is_new',$nav['is_new'],'','',[lang('nav_form_opt_disable'),lang('nav_form_opt_enable')]],
            [lang('nav_form_field_sort'),'text','sort',$nav['sort']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    // 更新导航数据
    public function updatenav()
    {
        if (request()->isPost()) {
            $data = $this -> post_data;
            $res = Db::name('shop_nav') ->update($data);
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


    // 修改导航字段值
    public function nav_setval($id)
    {
        $data = input('');
        if (isset($data['field']) && isset($data['val'])) {
            $field = $data['field'];
            $value = $data['val'];
        } else {
            $field = $data['field_name'];
            $value = $data['field'];
        }
        
        $res =  Db::name('shop_nav') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    // 删除广告
    public function delnav($id)
    {
        if(empty($id)){
            $this->error(lang('comm_delete_error'));
        }

        $res =  Db::name('shop_nav') ->delete($id);
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    public function delsnav()
    {
        $data = input('');
        $ids = implode(',', $data['id']);
        if(empty($ids)){
            $this->error(lang('comm_delete_error'));
        }
        $res =  Db::name('shop_nav') ->where('id','IN',$ids) ->delete();
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }


    public function ajaxGetNavType()
    {
        
    }
}
