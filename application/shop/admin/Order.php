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

use app\shop\model\ShopReturnGoods;
use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;
use app\sys\controller\Api;

class Order extends AdminBase
{
	protected function _initialize()
    {
    	parent::_initialize();
    }

    public function index()
    {
        // 筛选条件
        $data = input();

        $whereStatus = '';
        $where['id'] = ['>', 0];
        if (!empty($data['status'])) {
            $data_status = $data['status'] == 100 ? 0 : $data['status'];
            $where['status'] = $data_status;
        }else{
            $whereStatus = '`status` <> -2 and `status` <> -3 and `status` <> -1';
        }
        if (!empty($data['is_pay'])) {
            $data_pay = $data['is_pay']==100?0:$data['is_pay'];
            $where['is_pay'] = $data_pay;
        }
        if (!empty($data['is_send'])) {
            $data_send = $data['is_send']==100?0:$data['is_send'];
            $where['is_send'] = $data_send;
        }
        if (!empty($data['consignee'])) {
            $where['consignee'] = ['like','%'.$data['consignee'].'%'];
        }
        if (!empty($data['order_sn'])) {
            $where['order_sn'] = ['like',"%{$data['order_sn']}%"];
        }

        // 是否显示表格的选择列？
        $table['show_check'] = 0;

        $status = $status2 = config('order_status');
        $status[100] = $status[0];
        unset($status[0]);
        $shipping_status = $shipping_status2 = config('shipping_status');
        $shipping_status[100] = $shipping_status[0];
        unset($shipping_status[0]);
        $pay_status = $pay_status2 = config('pay_status');
        $pay_status[100] = $pay_status[0];
        unset($pay_status[0]);

        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'order_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                ['','select','status',isset($data['status'])?$data['status']:'','','',$status],
                ['','select','is_pay',isset($data['is_pay'])?$data['is_pay']:'','','',$pay_status],
                ['','select','is_send',isset($data['is_send'])?$data['is_send']:'','','',$shipping_status],
                [lang('order_filter_file_consignee'),'text','consignee',isset($data['consignee'])?$data['consignee']:'',lang('order_filter_file_consignee')],
                [lang('order_filter_file_order'),'text','order_sn',isset($data['order_sn'])?$data['order_sn']:'',lang('order_filter_file_order')],
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['order_sn',lang('order_lists_table_order'),'text'],
                ['consignee',lang('order_lists_table_consignee'),'text'],
                ['total_price',lang('order_lists_table_total'),'text'],
                ['payable_price',lang('order_lists_table_need'),'text'],
                ['postage',lang('order_lists_table_postage'),'text'],
                ['status',lang('order_lists_table_status'),'text'],
                ['is_pay',lang('order_lists_table_pay'),'text'],
                ['is_send',lang('order_lists_table_shipping'),'text'],
                ['pay_code',lang('order_lists_table_pay_style'),'text'],
                ['shipping_name',lang('order_lists_table_ship_style'),'text'],
                ['add_time',lang('order_lists_table_time'),'text'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('order_lists_btn_show'),'frame',lang('order_lists_btn_show'),'fa fa-fw fa-eye','layui-btn-normal','Order/show','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Order/del','id',['is_pay','<>','1']],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 未付款订单数
        $order_unpay = Db::name('shop_order') ->where('status',1) ->count();
        $order_unconfirm = Db::name('shop_order') ->where('status',2) ->count();
        $order_unsend = Db::name('shop_order') ->where('status',3) ->count();
        $order_uncomplate = Db::name('shop_order') ->where('status',4) ->count();
        // 设置列表页顶部按钮组
        $top_btn = [
            ['未付款订单['.$order_unpay.']','frame','未付款订单','','layui-btn-normal','Order/lists',['status'=>1]],
            ['付款未处理订单['.$order_unconfirm.']','frame','付款未处理订单','','layui-btn-normal','Order/lists',['status'=>2]],
            ['待发货订单['.$order_unsend.']','frame','','待发货订单','layui-btn-normal','Order/lists',['status'=>3]],
            ['待收货订单['.$order_uncomplate.']','frame','待收货订单','','layui-btn-normal','Order/lists',['status'=>4]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $order = Db::name('shop_order') ->where($where) ->where($whereStatus) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有记录
        // dump(Db::name('shop_order') ->getLastSql());
        $order_list = $order ->all();

        foreach ($order_list as $key => $con) {
            $order_list[$key]['status'] = $status2[$con['status']];
            $order_list[$key]['is_pay'] = $pay_status2[$con['is_pay']];
            $order_list[$key]['is_send'] = $shipping_status2[$con['is_send']];
            $total_weight = $this->getOrderWeight($con['id']);
            $order_list[$key]['postage'] = $con['postage'].'['.$total_weight.'Kg]';
            $order_list[$key]['payable_price'] = $con['payable_price']+$con['postage']-$con['change_mny'];
        }
        $this->assign('data',$order_list);
        // 获取分页显示
        $page = $order->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    /**
     * 无效订单
     * @return mixed
     */
    public function indexNone()
    {
        // 筛选条件
        $data = input();

        $where['id'] = ['>', 0];

        $whereStatus = '`status` = -2 or `status` = -3 or `status` = -1';

        if (!empty($data['is_pay'])) {
            $data_pay = $data['is_pay']==100?0:$data['is_pay'];
            $where['is_pay'] = $data_pay;
        }
        if (!empty($data['is_send'])) {
            $data_send = $data['is_send']==100?0:$data['is_send'];
            $where['is_send'] = $data_send;
        }
        if (!empty($data['consignee'])) {
            $where['consignee'] = ['like','%'.$data['consignee'].'%'];
        }
        if (!empty($data['order_sn'])) {
            $where['order_sn'] = ['like',"%{$data['order_sn']}%"];
        }

        // 是否显示表格的选择列？
        $table['show_check'] = 0;

        $status = $status2 = config('order_status');
        $status[100] = $status[0];
        unset($status[0]);
        $shipping_status = $shipping_status2 = config('shipping_status');
        $shipping_status[100] = $shipping_status[0];
        unset($shipping_status[0]);
        $pay_status = $pay_status2 = config('pay_status');
        $pay_status[100] = $pay_status[0];
        unset($pay_status[0]);

        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'order_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                ['','select','status',isset($data['status'])?$data['status']:'','','',$status],
                ['','select','is_pay',isset($data['is_pay'])?$data['is_pay']:'','','',$pay_status],
                ['','select','is_send',isset($data['is_send'])?$data['is_send']:'','','',$shipping_status],
                [lang('order_filter_file_consignee'),'text','consignee',isset($data['consignee'])?$data['consignee']:'',lang('order_filter_file_consignee')],
                [lang('order_filter_file_order'),'text','order_sn',isset($data['order_sn'])?$data['order_sn']:'',lang('order_filter_file_order')],
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['order_sn',lang('order_lists_table_order'),'text'],
                ['consignee',lang('order_lists_table_consignee'),'text'],
                ['total_price',lang('order_lists_table_total'),'text'],
                ['payable_price',lang('order_lists_table_need'),'text'],
                ['postage',lang('order_lists_table_postage'),'text'],
                ['status',lang('order_lists_table_status'),'text'],
                ['is_pay',lang('order_lists_table_pay'),'text'],
                ['is_send',lang('order_lists_table_shipping'),'text'],
                ['pay_code',lang('order_lists_table_pay_style'),'text'],
                ['shipping_name',lang('order_lists_table_ship_style'),'text'],
                ['add_time',lang('order_lists_table_time'),'text'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('order_lists_btn_show'),'frame',lang('order_lists_btn_show'),'fa fa-fw fa-eye','layui-btn-normal','Order/show','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Order/del','id',['is_pay','<>','1']],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 未付款订单数
        $order_unpay = Db::name('shop_order') ->where('status',1) ->count();
        $order_unconfirm = Db::name('shop_order') ->where('status',2) ->count();
        $order_unsend = Db::name('shop_order') ->where('status',3) ->count();
        $order_uncomplate = Db::name('shop_order') ->where('status',4) ->count();
        // 设置列表页顶部按钮组
        $top_btn = [
            ['未付款订单['.$order_unpay.']','frame','未付款订单','','layui-btn-normal','Order/lists',['status'=>1]],
            ['付款未处理订单['.$order_unconfirm.']','frame','付款未处理订单','','layui-btn-normal','Order/lists',['status'=>2]],
            ['待发货订单['.$order_unsend.']','frame','','待发货订单','layui-btn-normal','Order/lists',['status'=>3]],
            ['待收货订单['.$order_uncomplate.']','frame','待收货订单','','layui-btn-normal','Order/lists',['status'=>4]],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        $order = Db::name('shop_order') ->where($where) ->where($whereStatus) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有记录
        // dump(Db::name('shop_order') ->getLastSql());
        $order_list = $order ->all();

        foreach ($order_list as $key => $con) {
            $order_list[$key]['status'] = $status2[$con['status']];
            $order_list[$key]['is_pay'] = $pay_status2[$con['is_pay']];
            $order_list[$key]['is_send'] = $shipping_status2[$con['is_send']];
            $total_weight = $this->getOrderWeight($con['id']);
            $order_list[$key]['postage'] = $con['postage'].'['.$total_weight.'Kg]';
            $order_list[$key]['payable_price'] = priceFormat($con['payable_price']+$con['postage']-$con['change_mny'],0);
        }
        $this->assign('data',$order_list);
        // 获取分页显示
        $page = $order->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }



    public function lists($status)
    {
        // 是否显示表格的选择列？
        $table['show_check'] = 0;

        $JCreater = new JCreater();

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['order_sn',lang('order_lists_table_order'),'text'],
                ['consignee',lang('order_lists_table_consignee'),'text'],
                ['total_price',lang('order_lists_table_total'),'text'],
                ['payable_price',lang('order_lists_table_need'),'text'],
                ['postage',lang('order_lists_table_postage'),'text'],
                ['status',lang('order_lists_table_status'),'text'],
                ['is_pay',lang('order_lists_table_pay'),'text'],
                ['is_send',lang('order_lists_table_shipping'),'text'],
                ['pay_code',lang('order_lists_table_pay_style'),'text'],
                ['shipping_name',lang('order_lists_table_ship_style'),'text'],
                ['add_time',lang('order_lists_table_time'),'text'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('order_lists_btn_show'),'frame',lang('order_lists_btn_show'),'fa fa-fw fa-eye','layui-btn-normal','Order/show','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Order/del','id',['is_pay','<>','1']],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $this->assign($table);

        $order = Db::name('shop_order') ->where('status',$status) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang));  //取出当前语言版本下的所有记录
        // dump($order);
        $order_list = $order ->all();

        $status2 = config('order_status');
        $shipping_status2 = config('shipping_status');
        $pay_status2 = config('pay_status');

        foreach ($order_list as $key => $con) {
            $order_list[$key]['status'] = $status2[$con['status']];
            $order_list[$key]['is_pay'] = $pay_status2[$con['is_pay']];
            $order_list[$key]['is_send'] = $shipping_status2[$con['is_send']];
            $total_weight = $this->getOrderWeight($con['id']);
            $order_list[$key]['postage'] = $con['postage'].'['.$total_weight.'Kg]';
            $order_list[$key]['payable_price'] = $con['payable_price']+$con['postage']-$con['change_mny'];
        }
        $this->assign('data',$order_list);
        // 获取分页显示
        $page = $order->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }


    public function getOrderWeight($id)
    {
        $orderGoods = Db::name('shop_order_goods') ->where('order_id',$id) ->select();
        $weight = 0;
        foreach ($orderGoods as $key => $value) {
            $weight_goods = Db::name('shop_goods') ->where('id',$value['goods_id']) ->value('weight');
            $weight += $weight_goods*$value['goods_num'];
        }
        $weight = round($weight/1000,2);
        return $weight;
    }


    public function show($id)
    {
        $status = config('order_status');
        $this->assign('status',$status);
        $shipping_status = config('shipping_status');
        $this->assign('shipping_status',$shipping_status);
        $pay_status = config('pay_status');
        $this->assign('pay_status',$pay_status);

        $order = Db::name('shop_order') ->find($id);
        $order['user'] = $this -> getUserInfo($order['user_id']);
        $order['status_str'] = $status[$order['status']];
        $order['address'] = getAddressName($order['province']).getAddressName($order['city']).getAddressName($order['district']).$order['address'];
        $this->assign('order',$order);

        // 获取使用的普通优惠券
        $couponId = $order['coupon_id'];
        $bizCoupon = explode(',',$order['biz_coupon']);
        array_push($bizCoupon,$couponId);
        array_filter($bizCoupon);
        $couponInfo = [];
        foreach ($bizCoupon as $key=>$item){
            if( empty($item) ){
                continue;
            }
            $userCouponInfo = api('shop','Coupon','userCouponInfoFromId',[$item]);
            $coupon = api('shop','Coupon','couponInfo',[$userCouponInfo['coupon']]);
            if( empty($coupon) ){
                continue;
            }
            $couponInfo[$key] = $coupon;
            $couponInfo[$key]['quota'] = api('shop','Coupon','getQuota',[$couponInfo[$key]['discount_type'],$couponInfo[$key]['quota']]);
            $couponLevelInfo = api('shop','Coupon','couponLevelInfo', $couponInfo[$key]['coupon_level']);
            $couponInfo[$key]['coupon_level'] = $couponLevelInfo['name'];
            // 优惠类型
            $couponInfo[$key]['discount_type'] = api('shop','Coupon','discountTypeName',$couponInfo[$key]['discount_type']);
        }
        $this->assign('usedCoupon',$couponInfo);

        $order_goods = Db::name('shop_order_goods') ->where('order_id',$id) ->select();
        $this->assign('order_goods',$order_goods);

        $order_action = Db::name('shop_order_action') ->where('order_id',$id) ->order(['id'=>'DESC']) ->select();
        $this->assign('order_action',$order_action);
        return $this->fetch();
    }

    private function getUserInfo($user_id)
    {
    	$userInfo = Db::name('users') ->find($user_id);
    	$showName = empty($userInfo['nickname'])?$userInfo['mobile']:$userInfo['nickname'];
    	$showConnect = empty($userInfo['mobile'])?$userInfo['email']:$userInfo['mobile'];
    	if (empty($userInfo['nickname'])) {
    		$showUser = $showConnect;
    	} else {
    		$showUser = $showName.'('.$showConnect.')';
    	}
    	return $showUser;
    }

    /**
     * 修改订单状态
     */
    public function update_status(){
        $id = request()->param('id');
        $status = request()->param('status');
        $remark = empty(request()->param('remark')) ? '' : request()->param('remark');
        //修改状态
        if( $id == '' ){
            $this->error(lang('order_update_status_id_failed'));
        }
        switch ($status){
            case 1:
                $data = ['status'=>$status,'is_pay'=>0,'is_send'=>0];
                break;
            case 2:
                $data = ['status'=>$status,'is_pay'=>1,'pay_time'=>date('Y-m-d H:i:s', NOW_TIME),'is_send'=>0];
                break;
            case 3:
                $data = ['status'=>$status,'is_send'=>0];
                //商家确认订单通知
                $title = '卖家确认订单';
                $message = '卖家已经确认订单，宝贝将很快进行发货。';
                sendOrderMessage($id,$title,$message);
                break;
            case 4:
                $data = ['status'=>$status,'is_send'=>1];
                //商家发货通知
                $title = '卖家已经发货';
                $message = '卖家已经发货了，请耐心等待，宝贝将很快到您的手上。';
                sendOrderMessage($id,$title,$message);
                break;
            default:
                $data = ['status'=>$status];
        }
        Db::startTrans();
        try{
            //记录到订单操作表中
            $statusArr = config('order_status');
            insert_order_action($id,session('user.id'),$status,$remark,lang('order_update_status_of_admin').$statusArr[$status]);
            Db::name('shop_order')->where('id',$id)->update($data);
            if( $status == 4 ){
                saveUserDistribution($id);
            }
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
        $this->success(lang('order_update_status_success'));
    }

    /**
     * 修改订单数据
     *
     */
    public function update_order_price(){
        $id = !empty(request()->param('id')) ? request()->param('id') : 0;
        if( intval($id) > 0 ){
            $is_pay = getTableValue('shop_order','id='.$id,'is_pay');
            if( $is_pay == 1 ){
                $this->error(lang('order_is_pay'));
            }
            $data = Db::name('shop_order')->where('id',$id)->field('id,postage,total_price,payable_price,balance_price,points_price,change_mny')->find();
            if( $data == '' ){
                $this->error('cart_order_failed');
            }else{
                $data['money'] = $data['payable_price']-$data['change_mny']+$data['postage'];
                $this->assign('order',$data);
            }
        }else{
            $this->error('cart_order_failed');
        }
        return $this->fetch();
    }

    /**
     * update_order_price_save
     */
    public function update_order_price_save(){
        $id = intval(request()->param('id'));
        if( $id !== '' ){
            //查询订单当前支付状态，如果已支付，不可更改
            $is_pay = getTableValue('shop_order','id='.$id,'is_pay');
            if( $is_pay == 1 ){
                $this->error(lang('order_is_pay'));
            }
            $saveData['postage'] = empty(request()->param('postage')) ? 0 : request()->param('postage');
            $saveData['change_mny'] = empty(request()->param('change_mny')) ? 0 : request()->param('change_mny');
            Db::startTrans();
            try{
                insert_order_action($id,session('user.id'),getTableValue('shop_order','id='.$id,'status'),'',lang('order_update_price_of_admin'));
                Db::name('shop_order')->where('id',$id)->update($saveData);
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                $this->error($exception->getMessage());
            }
            return $this->success(lang('order_update_price_success'));
        }
    }

    public function update_order(){
        $id = !empty(request()->param('id')) ? request()->param('id') : 0;
        if( intval($id) > 0 ){
            $data = Db::name('shop_order')->where('id',$id)->field('id,consignee,phone,province,city,district,address,postage,total_price,payable_price,balance_price,points_price,change_mny,invoice_title,is_pay,is_send')->find();
            if( $data == '' ){
                $this->error('cart_order_failed');
            }else{
                //获取商品列表
                $goodsList = Db::name('shop_order_goods')->where('order_id',$id)->field('id,goods_id,goods_name,spec_title,goods_num,shop_price')->select();
                //获取省份列表
                $provinceList = Api::getChildAddress(0,1);
                //获取城市列表
                $cityList = Api::getChildAddress($data['province'],2);
                //获取区县列表
                $districtList = Api::getChildAddress($data['city'],3);
                $data['money'] = $data['payable_price']-$data['change_mny']+$data['postage'];

                $this->assign('province',$provinceList);
                $this->assign('city',$cityList);
                $this->assign('district',$districtList);
                $this->assign('goods_list',$goodsList);
                $this->assign('order',$data);
            }
        }else{
            $this->error('cart_order_failed');
        }
        return $this->fetch();
    }

    public function update_order_save(){
        $id = intval(request()->param('id'));
        if( $id > 0 ){
            //修改商品数量
            $postData = request()->param();
            $validate = new \app\shop\validate\Order();
            $validateData = $validate->check($postData);
            if( $validateData == false ){
                $this->error($validate->getError());
            }
            $remark = !empty(request()->param('remark')) ? request()->param('remark') : '';
            //设置保存数据
            $saveData['consignee'] = $postData['consignee'];
            $saveData['phone'] = $postData['phone'];
            $saveData['province'] = $postData['province'];
            $saveData['city'] = $postData['city'];
            $saveData['district'] = $postData['district'];
            $saveData['address'] = $postData['address'];
            //保存
            Db::startTrans();
            try{
                insert_order_action($id,session('user.id'),getTableValue('shop_order','id='.$id,'status'),$remark,lang('order_update_info_of_admin'));
                Db::name('shop_order')->where('id',$id)->update($saveData);
                //修改商品数量
                if( !empty($postData['goods_num']) ){
                    foreach ($postData['goods_num'] as $key=>$value){
                        Db::name('shop_order_goods')->where('id',$key)->update(['goods_num'=>$value]);
                    }
                }
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                return $this->error($exception->getMessage());
            }
            return $this->success(lang('order_update_info_success'));
        }
    }

    /**
     * 获取子地址，默认为0
     */
    public function getAddressList($parent_id = 0){
        $level = getTableValue('region','parent_id='.$parent_id,'level');
        $addressList = Api::getChildAddress($parent_id,$level);
        $addressHtml = '';
        if( is_array( $addressList ) && count($addressList) >0 ){
            foreach ($addressList as $address) {
                $addressHtml .= "<option value='{$address['id']}'>{$address['name']}</option>";
            }
        }
        return $this->success($addressHtml);
    }

    /**
     * 订单发货
     * Delivery
     */
    public function delivery(){
        $id = empty(request()->param('id')) ? 0 : request()->param('id');
        $id = intval($id);
        if( $id === 0 ){
            $this->error(lang('cart_order_failed'));
        }
        //获取快递列表
        $shippingList = Db::name('shop_shipping')->where('enabled',1)->field('code,name')->select();

        //订单信息
        $orderInfo = Db::name('shop_order')->where('id',$id)->field('id,order_sn,add_time,postage,consignee,phone,province,city,district,address,zipcode,shipping_code,shipping_sn,invoice_title,is_send,is_pay,status')->find();

        $orderInfo['address'] = getAddressName($orderInfo['province']).getAddressName($orderInfo['city']).getAddressName($orderInfo['district']).$orderInfo['address'];

        if( $orderInfo == '' ){
            $this->error(lang('order_is_not_exist'));
        }
        if( $orderInfo['is_pay'] == 0 ){
            $this->error(lang('order_is_not_pay'));
        }

        //获取商品列表
        $goosList = Db::name('shop_order_goods')->field('goods_name,spec_title,goods_num,shop_price')->where('order_id',$id)->select();

        $this->assign('goods_list',$goosList);
        $this->assign('order',$orderInfo);
        $this->assign('shipping_list',$shippingList);
        return $this->fetch();
    }

    /**
     * 保存发货信息
     */
    public function delivery_save(){
        $id = intval(request()->param('id'));
        if( $id > 0 ){
            //查询订单当前支付状态，如果已支付，不可更改
            $is_pay = getTableValue('shop_order','id='.$id,'is_pay');
            if( $is_pay == 0 ){
                $this->error(lang('order_is_not_pay'));
            }
            //修改商品数量
            $postData = request()->param();
            if( empty($postData['shipping_code']) ){
                $this->error(lang('order_select_shipping'));
            }
            if( empty($postData['shipping_sn']) ){
                $this->error(lang('order_select_shipping_sn'));
            }
            $remark = !empty(request()->param('remark')) ? request()->param('remark') : '';
            //设置保存数据
            $saveData['shipping_code'] = $postData['shipping_code'];
            $saveData['shipping_sn'] = $postData['shipping_sn'];
            $saveData['shipping_name'] = getTableValue('shop_shipping','code=\''.$saveData['shipping_code'].'\'','name');
            $saveData['status'] = 4;
            $saveData['is_send'] = 1;
            $saveData['send_time'] = date('Y-m-d H:i:s', NOW_TIME);
            //保存
            Db::startTrans();
            try{
                insert_order_action($id,session('user.id'),$saveData['status'],$remark,lang('order_update_delivery_of_admin'));
                Db::name('shop_order')->where('id',$id)->update($saveData);
                Db::commit();
            }catch (\Exception $exception){
                Db::rollback();
                return $this->error($exception->getMessage());
            }
            return $this->success(lang('order_update_info_success'));
        }
    }


    /**
     * 发货单
     */
    public function delivery_doc_list(){

        $data = input();

        $where['id'] = ['>',0];
        if (isset($data['is_pay'])) {
            $where['is_pay'] = $data['is_pay'];
        }
        if (isset($data['is_send'])) {
            $where['is_send'] = $data['is_send'];
        }
        if (isset($data['consignee'])) {
            $where['consignee'] = ['like','%'.$data['consignee'].'%'];
        }
        if (isset($data['order_sn'])) {
            $where['order_sn'] = ['like','%'.$data['order_sn'].'%'];
        }


        // 是否显示表格的选择列？
        $table['show_check'] = 1;

        $status = config('order_status');
        $shipping_status = config('shipping_status');
        $pay_status = config('pay_status');
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
        */
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['order_sn',lang('order_lists_table_order'),'text'],
                ['consignee',lang('order_lists_table_consignee'),'text'],
                ['status',lang('order_lists_table_status'),'text'],
                ['is_pay',lang('order_lists_table_pay'),'text'],
                ['is_send',lang('order_lists_table_shipping'),'text'],
                ['shipping_name',lang('order_lists_table_ship_style'),'text'],
                ['add_time',lang('order_lists_table_time'),'text'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            ['','frame',lang('order_lists_btn_show'),'fa fa-fw fa-truck','layui-btn-normal','shop/Order/delivery','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        $this->assign($table);

        $order = Db::name('shop_order') ->where($where) ->where('status>3') ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有记录
        // dump($order);
        $order_list = $order ->all();

        foreach ($order_list as $key => $con) {
            $order_list[$key]['status'] = $status[$con['status']];
            $order_list[$key]['is_pay'] = $pay_status[$con['is_pay']];
            $order_list[$key]['is_send'] = $shipping_status[$con['is_send']];
        }
        $this->assign('data',$order_list);
        // 获取分页显示
        $page = $order->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
        //获取所有已经确认的订单
        $orderList = Db::name('shop_order')->where('status>=3')->select();
        $this->assign('order_list',$orderList);
        return $this->fetch();
    }

    /**
     * 退货单
     */
    public function return_goods_list(){
        if (!empty($model_info['table_fields'])) {
            $table_head = json_decode($model_info['table_fields'],true);
            $table_head['btn'] = ['btn',lang('content_list_title_7'),'btn'];
        }else{
            $table_head = [
                ['id','ID','text'],
                ['delivery_sn',lang('order_return_goods_lists_table_delivery'),'text'],
                ['order_id',lang('order_return_goods_lists_table_order'),'text'],
                ['order_goods_id',lang('order_return_goods_lists_table_order_goods'),'text'],
                ['status',lang('order_return_goods_lists_table_status'),'text'],
                ['user_id',lang('order_return_goods_lists_table_user'),'text'],
                ['reason',lang('order_return_goods_lists_table_reason'),'text'],
                ['remark',lang('order_return_goods_lists_table_ship_remark'),'text'],
                ['add_time',lang('order_return_goods_lists_table_add_time'),'text'],
                ['btn',lang('content_list_title_7'),'btn'],
            ];
        }
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('order_lists_btn_show'),'frame',lang('order_lists_btn_show'),'fa fa-fw fa-eye','layui-btn-normal','shop/Order/return_goods','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Order/del','id'],
        ];
        // 是否显示表格的选择列？
        $table['show_check'] = 0;
        $table['btn_lst'] = JCreater::table_btn($btn);

        $this->assign($table);
        // dump($order);
        $returnGoodsModel = new ShopReturnGoods();
        $returnGoods = $returnGoodsModel->paginate(tb_config('list_rows',1,getLang()));

        $this->assign('data',$returnGoods);
        // 获取分页显示
        $page = $returnGoods->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    /**
     * 退货详情
     */
    public function return_goods(){
        $id = !empty(request()->param('id')) ? request()->param('id') : 0;
        if( intval($id) > 0 ){
            $return_goodsModel = new ShopReturnGoods();
            $data = $return_goodsModel->where('id',$id)->find();
            if( $data == '' ){
                $this->error('return_goods_failed');
            }else{
                $order_goods = Db::name('shop_order_goods')->where('id',getTableValue('shop_return_goods','id='.$id,'order_goods_id'))->find();
                $data['order_goods_shop_price'] = $order_goods['payable_price'];
                $this->assign('return_goods',$data);
            }
            //获取所有状态
            $statusList = config('return_goods');
            $this->assign('status_list',$statusList);
            $this->assign('status',getTableValue('shop_return_goods','id='.$id,'status'));
        }else{
            $this->error('return_goods_failed');
        }
        return $this->fetch();
    }

    /**
     * 保存退货状态信息
     */
    public function return_goods_save(){
        $id = intval(request()->param('id'));
        if( $id > 0 ){
            $saveData['status'] = empty(request()->param('status')) ? 0 : request()->param('status');
            $saveData['remark'] = empty(request()->param('remark')) ? 0 : request()->param('remark');
            try{
                Db::name('shop_return_goods')->where('id',$id)->update($saveData);
            }catch (\Exception $exception){
                $this->error($exception->getMessage());
            }
            return $this->success(lang('order_return_goods_update_success'));
        }
    }

    /**
     * 退款到用户余额
     */
    public function return_money(){
        $id = intval(request()->param('id'));
        if( $id > 0 ){
            //查找退货单
            $return_goods = Db::name('shop_return_goods')->where('id',$id)->field('id,money,returned')->find();
            $return_goods['money'] = $return_goods['money']-$return_goods['returned'];
            $this->assign('return_goods',$return_goods);
            return $this->fetch();
        }else{
            $this->error(lang('return_goods_failed'));
        }
    }

    /**
     * 用户余额保存
     */
    public function return_money_save(){
        $id = intval(request()->param('id'));
        if( $id > 0 ){
            $saveData['returned'] = empty(request()->param('money')) ? 0 : request()->param('money');
            $data['points'] = empty(request()->param('points')) ? '' : request()->param('points');
            $data['remark'] = empty(request()->param('remark')) ? '' : request()->param('remark');

            //修改用户余额
            $user_id = getTableValue('shop_return_goods','id='.$id,'user_id');
            try{
                Db::name('users')->where('id',$user_id)->setInc('user_money',$saveData['returned']);
                if( $data['points'] > 0 ){
                    Db::name('users')->where('id',$user_id)->setInc('pay_points',$data['points']);
                }
                Db::name('shop_return_goods')->where('id',$id)->setInc('returned',$saveData['returned']);
                //记录用户资金变化情况

            }catch (\Exception $exception){
                $this->error($exception->getMessage());
            }
            //用户资金日志
            $userInfo = Db::name('users')->where('id',$user_id)->field('user_money,frozen_money,pay_points')->find();
            $account_log['user_id'] = $user_id;
            $account_log['user_money'] = $userInfo['user_money'];
            $account_log['frozen_money'] = $userInfo['frozen_money'];
            $account_log['pay_points'] = $userInfo['pay_points'];
            $account_log['change_time'] = date('Y-m-d H:i:s', NOW_TIME);
            $account_log['desc'] = lang('平台管理员退款给用户');
            $account_log['order_id'] = getTableValue('shop_return_goods','id='.$id,'order_id');
            $account_log['order_sn'] = getTableValue('shop_order','id='.$account_log['order_id'],'order_sn');
            Db::name('user_account_log')->insert($account_log);
            return $this->success(lang('order_return_goods_returned_success'));
        }
    }
}