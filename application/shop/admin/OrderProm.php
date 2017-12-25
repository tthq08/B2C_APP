<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 订单促销后台管理模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\admin;


use app\sys\controller\AdminBase;
use app\shop\model\ShopPromotionOrder;
use think\Db;
use app\common\JunCreater\JCreater;
use app\shop\validate\OrderProm as OrderValidate;

class OrderProm extends AdminBase
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->_DB = new ShopPromotionOrder();
    }

    /**
     * 订单促销列表，所以订单促销活动
     * 显示数据：
     *      促销标题
     *      促销方式
     *      促销标准（满多少金额达到促销标准）
     *      促销类型
     *      优惠体现
     *      开始时间
     *      结束时间
     *      状    态
     *      操    作
     */
    public function index()
    {
        //获取当前数据
        $field = 'order_id as id,title,type,money,expression,start_time,end_time,status';
        $panicList = $this->_DB->field($field)->order('order_id')->paginate(tb_config('list_rows',1,$this->lang));
        //配合后台表格
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        //title定义
        $table_head = [
            ['id','ID','text'],
            ['title',lang('prom_table_title'),'text'],
            ['type',lang('prom_table_type'),'text'],
            ['money',lang('prom_table_money'),'text'],
            ['expression',lang('prom_table_expression'),'text'],
            ['start_time',lang('start_time'),'text'],
            ['end_time',lang('end_time'),'text'],
            ['status',lang('content_list_title_6'),'switch','OrderProm/switchs','id'],
            ['btn',lang('content_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','OrderProm/edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','OrderProm/del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);
        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','OrderProm/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','OrderProm/del'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);
        // 获取分页显示
        $page = $panicList->render();
        $this->assign('page', $page);
        $this->assign($table);
        $this->assign('data',$panicList);
        return $this->fetch('sys@Base:table');

    }

    public function add()
    {
        //================  表单项定义 =================
        $form['action'] = 'save';      //表单提交的目的路径
        $form['web_title'] = lang('prom_order_add_title');   // 页面标题
        $this->assign($form);
        //================  表单项定义 end =================
        //查找所有用户等级
        $user_level = Db::name('user_level')->where('status',1)->field('id,level_name')->select();
        $this->assign('user_level',$user_level);
        //获取优惠类型
        $discount_type = Db::name('shop_discount_type')->where('status',1)->select();
        $this->assign('discount_type',$discount_type);
        return $this->fetch('add');

    }

    public function edit()
    {
        //查找所有用户等级
        $user_level = Db::name('user_level')->field('id,level_name')->select();
        $this->assign('user_level',$user_level);
        //获取抢购数据
        $orderData = $this->_DB->where('order_id',$this->request->param('id'))->find();
        $orderData['user_group_arr'] = explode(',',$orderData['user_group']);
        //获取优惠体现
        $prom = new Promotion();
        $expression = $prom->getDiscountForm($orderData['type'],$orderData['expression']);
        //获取优惠类型
        $discount_type = Db::name('shop_discount_type')->where('status',1)->select();
        $form['web_title'] = lang('prom_order_edit_title').'：&nbsp;&nbsp;'.$orderData['title'];   // 页面标题
        $this->assign($form);
        $this->assign('expression',$expression);
        $this->assign('discount_type',$discount_type);
        $this->assign('orderData',$orderData);
        return $this->fetch();

    }

    public function save()
    {
        $postData  = $this->post_data;
        //数据筛选
        $validate = new OrderValidate();
        $vaData = $validate->check($postData);
        if( !$vaData ) {
            $this->error($validate->getError());
        }
        //数据验证通过，验证商品是否存在
        unset($postData['file']);
        $postData['type'] = $postData['discount_type'];
        unset($postData['discount_type']);
        //确认是编辑还是增加
        if( !empty($postData['order_id']) )
        {
            $order_id = $postData['order_id'];
            unset($postData['order_id']);
            Db::startTrans();
            try {
                //保存进抢购表
                $this->_DB->where('order_id',$order_id)->update($postData);
                //保存进促销表
                $promData['start_time'] = $postData['start_time'];
                $promData['end_time'] = $postData['end_time'];
                Db::name('shop_promotion')->where(['p_type'=>4,'p_id'=>$order_id])->update($promData);
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
        }else{
            //保存抢购信息
            Db::startTrans();
            try {
                //保存进抢购表
                $savePanic = $this->_DB->insertGetId($postData);
                //保存进促销表
                $promData['p_type'] = 4;
                $promData['p_id'] = $savePanic;
                $promData['start_time'] = $postData['start_time'];
                $promData['end_time'] = $postData['end_time'];
                Db::name('shop_promotion')->insert($promData);
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $this->success(lang('prom_panic_save_success'));
    }

    public function del($id = '')
    {
        //删除当前抢购活动
        if( $id == '' ) {
            $this->error(lang('PromID_is_null'));
        }
        //删除当前促销
        if( is_array($id) ) {
            Db::startTrans();
            try{
                $this->_DB->where('order_id in('.$id.')')->delete();
                Db::name('shop_promotion')->where(['p_type'=>4])->where('p_id in('.$id.')')->delete();
                    Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
        }else{
            Db::startTrans();
            try{
                $this->_DB->where('order_id',$id)->delete();
                Db::name('shop_promotion')->where(['p_type'=>4,'p_id'=>$id])->delete();
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $this->success(lang('delete_success'));
    }

    public function switchs()
    {
        if (request()->isPost()) {
            $data = input();
            $dbContent = Db::name('shop_promotion_order');
            $res = $dbContent->where('order_id', $data['id'])->setField('status', $data['val']);

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