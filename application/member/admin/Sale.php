<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 会员模块分销控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\member\admin;

use app\member\model\UserDistribution;
use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Sale extends AdminBase
{

	protected function _initialize()
    {
    	parent::_initialize();
    }

       // 用户关系树
    public function showTree()
    {
        $data = input('');
        if (count($data)>0) {
            $key = $data['key'];
            $id = Db::name('users') ->where('mobile|email',$key) ->value('id');
            $pid = Db::name('users_tree') ->where('uid',$id) ->value('pid');
            $this->assign('id',$pid);
        }else{
            $this->assign('id',0);
        }
        return $this->fetch();
    }

    public function ajaxGetSubTree($id=0)
    {
        $id2 = input('post.id');
        $id = empty($id2)?$id:$id2;
        $users = Db::name('users_tree') ->where('pid',$id) ->select();
        foreach ($users as $key => $user) {
            $users[$key]['name'] =  getTableValue('users',['id'=>$user['uid']],'nickname');
            $isParent = getTableValue('users_tree',['pid'=>$user['uid']],'id');
            $isParent = empty($isParent)?false:true;
            $users[$key]['isParent'] =  $isParent;

        }
        return $users;
    }

    // 分销商管理
    public function salers()
    {
        return $this->fetch();
    }

    // 分销设置
    public function sale_conf()
    {
        //获取配置项
        $config = Db::name('user_distribution_config')->where('status',1)->order('id desc')->find();
        $config['level_proportion'] = json_decode($config['level_proportion']);
        $this->assign('config',$config);
        return $this->fetch();
    }

    /**
     * 保存配置
     * @return mixed
     */
    public function sale_conf_save(){
        $postData = request()->param();
        //保存信息
        $saveData['open'] = empty($postData['open']) ? '' : $postData['open'];
        $saveData['condition'] = empty($postData['condition']) ? '' : $postData['condition'];
        $saveData['money'] = empty($postData['money']) ? '' : $postData['money'];
        $saveData['name'] = empty($postData['name']) ? '' : $postData['name'];
        $saveData['level'] = empty($postData['level']) ? '' : $postData['level'];
        $saveData['pattern'] = empty($postData['pattern']) ? '' : $postData['pattern'];
        $saveData['level_proportion'] = empty($postData['level_proportion']) ? '' : $postData['level_proportion'];
        $saveData['level_proportion'] = json_encode($postData['level_proportion']);
        //保存信息
        try{
            Db::name('user_distribution_config')->insert($saveData);
        }catch (\Exception $exception){
            $this->error($exception->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * 分成日志
     * @return mixed
     */
    public function sale_logs()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check',1);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['user_id','接收分成用户','text'],
            ['user_r','购买商品用户','text'],
            ['order_id','订单号','text'],
            ['order_price','订单金额','text'],
            ['divided_into_price','分成金额','text'],
            ['add_time','添加时间','text'],
            ['is_divided_into','是否已经分成','text'],
            ['btn',lang('member_list_head_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            ['查看详细','frame','查看','fa fa-fw fa-search','layui-btn-normal','Sale/show','id'],
            ['确定分成','confirm','确定分成后将打款至用户余额，您是否确定分成？','glyphicon glyphicon-yen','layui-btn-normal','Sale/determine_into','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Sale/del_logs','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Sale/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // // 表格数据，分页，每页10条
        $distribution = new UserDistribution();
        $user_distribution = $distribution->where('status',1) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有配置项
        $user_distribution_list = $user_distribution ->all();

        $this->assign('data',$user_distribution_list);
        // 获取分页显示
        $page = $user_distribution->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    /**
     * 分成记录查看
     * @param int $id
     * @return mixed
     */
    public function show($id)
    {
        return $this->redirect('shop/Order/show',['id'=>getTableValue('user_distribution','id='.$id,'order_id')]);
    }



    public function dels(){
        $ids = input('');
        $ids = implode(',', $ids['id']);
        // 执行软删除
        $res =  Db::name('user_distribution') ->where('id','IN',$ids) ->delete();
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

    /**
     * 确定分成
     */
    public function determine_into(){
        $postData = request()->param();
        $id = empty($postData['id']) ? '' : intval($postData['id']);
        if( !empty($id) ){
            //获取基本信息
            $distribution = Db::name('user_distribution')->where('id',$id)->find();
            if( empty($distribution) ){
                return false;
            }
            if( $distribution['is_divided_into'] == 1 ){
                $this->error('该奖励已经分成');
            }
            //记录到用户资金日志
            $account_log_data['user_id'] = $distribution['user_id'];
            $account_log_data['user_money'] = $distribution['divided_into_price'];
            $account_log_data['change_time'] = NOW_TIME;
            $account_log_data['desc'] = '订单分成奖励';
            $account_log_data['order_id'] = $distribution['order_id'];
            $account_log_data['order_sn'] = getTableValue('shop_order',$distribution['order_id'],'order_sn');
            Db::startTrans();
            try{
                //打款到用户账户
                Db::name('users')->where('id',$distribution['user_id'])->setInc('sale_account',$distribution['order_price']);
                Db::name('users')->where('id',$distribution['user_id'])->setInc('distribution',$distribution['divided_into_price']);
                //记录用户资金日志表
                Db::name('user_account_log')->insert($account_log_data);
                //更改状态
                Db::name('user_distribution')->where('id',$id)->update(['is_divided_into'=>1]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('分成成功');
        }else{
            $this->error('分成单错误');
        }
    }


    /**
     * 删除分成日志
     */
    public function del_logs(){
        $ids = empty(request()->param('ids')) ? '' : request()->param('ids');
        if( empty($ids) ){
            $ids = empty(request()->param('id')) ? '' : request()->param('id');
        }
        if( empty($ids) ){
            $this->error('日志错误');
        }
        //删除日志
        try{
            Db::name('user_distribution')->delete($ids);
        }catch (\Exception $exception){
            $this->error($exception->getMessage());
        }
        $this->success('删除成功');
    }

}
?>