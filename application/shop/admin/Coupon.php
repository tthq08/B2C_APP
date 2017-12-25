<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 优惠券后台管理模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Cache;
use think\Log;
use think\Request;
use think\Db;
use think\Validate;

class Coupon extends AdminBase
{
    /**
     * 优惠券后台管理系统
     * 优惠券发放方式：Payment method
     *      1、会员发放
     *      2、注册发放
     *      3、邀请发放
     *      4、线下发放
     *      5、促销活动
     *
     * 优惠券种类：type
     *      1、金额优惠
     *      2、折扣优惠
     *      3、免邮优惠
     */
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Cache::clear();
    }


    public function index()
    {

        $couponArr = api('shop','Coupon','selectCoupon');
        // 获取分页显示
        $page = $couponArr->render();

        $couponArr = json_decode(json_encode($couponArr),1);
        $couponArr = $couponArr['data'];

        foreach ($couponArr as $key=>$c){
            // 格式化时间
            $couponArr[$key]['use_start_time_d'] = date('Y-m-d H:i:s',$c['use_start_time']);
            $couponArr[$key]['use_end_time_d'] = date('Y-m-d H:i:s',$c['use_end_time']);
            $couponArr[$key]['send_start_time_d'] = date('Y-m-d H:i:s',$c['send_start_time']);
            $couponArr[$key]['send_end_time_d'] = date('Y-m-d H:i:s',$c['send_end_time']);

            // 发放方式
            $couponArr[$key]['send_type_str'] = api('shop','Coupon','sendTypeName',$c['send_type']);
            // 优惠类型
            $couponArr[$key]['discount_type_str'] = api('shop','Coupon','discountTypeName',$c['discount_type']);
            // 券类型
            $couponLevelInfo = api('shop','Coupon','couponLevelInfo',$c['coupon_level']);
            $couponArr[$key]['level_name'] = $couponLevelInfo['name'];
        }

        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        //title定义
        $table_head = [
            ['id','ID','text'],
            ['name','优惠券名称','text'],
            ['level_name','券等级','text'],
            ['send_type_str','发放方式','text'],
            ['discount_type_str','优惠类型','text'],
            ['quota','额度','text'],
            ['num','优惠券数量','text'],
            ['use_start_time_d','开始使用时间','text'],
            ['use_end_time_d','结束使用时间','text'],
            ['btn',lang('content_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);
        $btn = [
            [lang('send_coupon'),'frame',lang('send_coupon'),'si si-logout','layui-btn-normal','Coupon/send_coupon','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Coupon/del','id'],
            [lang('send_coupon'),'frame',lang('send_coupon'),'glyphicon glyphicon-fullscreen','layui-btn-normal','Coupon/detail','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);
        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Coupon/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Coupon/del'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        foreach ($couponArr as $key=>$item) {
            $quota_type = '';
            if( $item['discount_type'] == 1 ){
                $quota_type = '%';
            }elseif( $item['discount_type'] == 2 ){
                $quota_type = tb_config('web_coupon_currency',1);
            }
            $couponArr[$key]['quota'] = $item['quota'].$quota_type;
            if( $item['send_type'] == 8 ){
                $hierarchy = json_decode($item['hierarchy'],1);
                $quota = '';
                foreach ($hierarchy as $h=>$vo){
                    $quota .='人数: '.$vo['peopel_num'].' , 额度: '.$vo['quota'].$quota_type.'</br>';
                }
                $couponArr[$key]['quota'] = $quota;
            }
        }

        $this->assign('page', $page);
        $this->assign($table);
        $this->assign('data',$couponArr);
        return $this->fetch('sys@Base:table');
    }


    /**
     * 查看优惠券详细信息
     * @return mixed
     */
    public function detail()
    {
        $id = request()->param('id');
        $couponInfo = api('shop','Coupon','couponInfo',[$id]);

        $couponLevelInfo = api('shop','Coupon','couponLevelInfo',$couponInfo['coupon_level']);
        $this->assign('levelInfo',$couponLevelInfo);

        // 查找用户等级
        $user_level = api('member','User','selectLevel');
        $this->assign('user_level',$user_level);

        // 获取优惠券类型
        $discount_type_name = api('shop','Coupon','discountTypeName',[$couponInfo['discount_type']]);
        $this->assign('discount_type_name',$discount_type_name);

        $send_type_name = api('shop','Coupon','sendTypeName',[$couponInfo['send_type']]);
        $this->assign('send_type_name',$send_type_name);

        if( !empty($couponInfo['goods']) ){
            $couponInfo['bind_type'] = '商品';
            $bind_data = explode(',',$couponInfo['goods']);
            foreach ( $bind_data as $k=>$value )
            {
                $goods = api('shop','Goods','goodsinfo',[$value]);
                $couponInfo['bind_data'][$value] = $goods["title"];
            }
        }elseif( !empty($couponInfo['goods_category']) ){
            $couponInfo['bind_type'] = '商品分类';
            $bind_data = explode(',',$couponInfo['goods_category']);
            foreach ( $bind_data as $k=>$value )
            {
                $goods = api('shop','Goods','categoryInfo',[$value]);
                $couponInfo['bind_data'][$value] = $goods["name"];
            }
        }else{
            $couponInfo['bind_type'] = '无绑定';
            $couponInfo['bind_data'] = [];
        }

        // 会员等级
        $couponInfo['user_level'] = explode(',',$couponInfo['user_group']);

        // 店铺名称
        if( empty( $couponInfo['shop_id'] ) ){
            $couponInfo['shop_name'] = '无店铺';
        }else{
            $couponInfo['shop_name'] = api('cust','Cust','getShopName',[$couponInfo['shop_id']]).'['.$couponInfo['shop_id'].']';
        }

        $this->assign('coupon',$couponInfo);
        return $this->fetch();
    }


    /**
     * 查看优惠码情况
     * @return mixed
     */
    public function code_list()
    {
        $id = request()->param();
        $id = empty($id['id']) ? session('code_list_id') : $id['id'];
        session('code_list_id',$id);
        $codeList = api('shop','Coupon','code_list',[$id]);

        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        //title定义
        $table_head = [
            ['id','ID','text'],
            ['code','优惠码','text'],
            ['is_receive','是否已经领取','text'],
            ['user','领取用户','text'],
            ['receive_time','领取时间','text'],
            ['status','状态','text'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        $codeArray = [];
        foreach ($codeList as $key=>$code){
            $codeArray[$key] = $code;
            $codeArray[$key]['is_receive'] = empty($code['is_receive']) ? '否' : '是';
            if ( !empty($code['user']) ){
                $codeArray[$key]['user'] = getTableValue('users','id='.$code['user'],'nickname');
            }else{
                $codeArray[$key]['user'] = '';
            }
        }


        $this->assign('page', $codeList->render());
        $this->assign($table);
        $this->assign('data',$codeArray);
        return $this->fetch('sys@Base:table');
    }


    /**
     * 优惠券删除，根据管理员权限设置删除方式
     */
    public function del($id = '')
    {
        //删除当前抢购活动
        if( $id == '' ) {
            $this->error('');
        }
        if( is_array($id)){
            $id = implode(',',$id);
        }
        $del = Db::name('shop_coupon')->where('id in('.$id.')')->update(['status'=>-1]);
        if( $del === false ){
            $this->success(lang('delete_failed'));
        }
        $this->success(lang('delete_success'));
    }


    /**
     * 添加优惠券
     */
    public function add()
    {
        //================  表单项定义 =================
        $form['action'] = 'save';      //表单提交的目的路径
        $form['web_title'] = '添加优惠券';   // 页面标题
        $this->assign($form);
        //================  表单项定义 end =================
        // 获取优惠券等级
        $coupon_level = api('shop','Coupon','couponLevel');
        $this->assign('coupon_level',$coupon_level);

        // 查找用户等级
        $user_level = api('member','User','selectLevel');
        $this->assign('user_level',$user_level);

        return $this->fetch('add');

    }


    /**
     * 保存优惠券
     */
    public function save()
    {
        $data = $this->post_data;

        // 处理插入数据
        $rule = [
            'coupon_level' => 'require|number',
            'name' => 'require',
            'send_type' => 'require|number',
            'num' => 'require|number',
            'use_start_time' => 'require',
            'use_end_time' => 'require',
            'send_start_time' => 'require',
            'send_end_time' => 'require',
        ];

        $validate = api('sys','Verification','valiCheck',[$rule,$data]);
        if( $validate['code'] == 0 )
        {
            $this->error($validate['error']);
        }

        $data['use_start_time'] = strtotime($data['use_start_time']);
        $data['use_end_time'] = strtotime($data['use_end_time']);
        $data['send_start_time'] = strtotime($data['send_start_time']);
        $data['send_end_time'] = strtotime($data['send_end_time']);

        if( $data['send_type'] == 8 ){
            if( empty($data['hierarchy']) ){
                return $this->error('请填写发放层级!');
            } else{
                $data['hierarchy'] = json_encode($data['hierarchy']);
            }
        }

        if( !empty($data['send_user_level']) ){
            $data['send_user_level'] = implode(',',$data['send_user_level']);
        }

        if( !empty($data['bind_type']) ){
            // 选择商品
            if( $data['bind_type'] == 1 && !empty($data['goods']) ){
                $data['goods'] = implode(',',array_keys($data['goods']));
            }elseif( $data['bind_type'] == 1 && !empty($data['goods_category']) ){
                $data['goods_category'] = implode(',',array_keys($data['goods_category']));
            }
            unset($data['bind_type']);
        }
        Db::startTrans();
        try{
            api('shop','Coupon','insert',[$data]);
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            Log::error($exception);
            $this->error($exception->getMessage());
        }
        return $this->success('提交成功');
    }


    /**
     * 更新优惠券信息
     * @return mixed
     */
    public function update()
    {
        $data = $this->post_data;

        // 处理插入数据
        $rule = [
            'name' => 'require',
            'num' => 'require|number',
            'use_start_time' => 'require',
            'use_end_time' => 'require',
        ];

        $validate = new Validate($rule);
        $check = $validate->check($data);
        $data['use_start_time'] = strtotime($data['use_start_time']);
        $data['use_end_time'] = strtotime($data['use_end_time']);
        $data['send_start_time'] = strtotime($data['send_start_time']);
        $data['send_end_time'] = strtotime($data['send_end_time']);
        if( $check == false ){
            $this->error($validate->getError());
        }

        Db::startTrans();
        try{
            api('shop','Coupon','update',[$data]);
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
        return $this->success('更新成功');
    }

    /**
     * 修改优惠券状态
     * @return mixed
     */
    public function update_status()
    {
        $id = request()->param('id');
        $value = request()->param('status');
        $update = api('shop','Coupon','update_status',[$id,$value]);
        if( $update['code'] == 0 ){
            $this->error($update['error']);
        }
        $this->success('更新成功');
    }


    /**
     * 修改优惠券首页显示
     * @return mixed
     */
    public function update_index_show()
    {
        $id = request()->param('id');
        $value = request()->param('index_show');
        $update = api('shop','Coupon','update_index_show',[$id,$value]);
        if( $update['code'] == 0 ){
            $this->error($update['error']);
        }
        $this->success('更新成功');
    }


    /**
     * 发放优惠券
     * @param $id
     * @return mixed
     */
    public function send_coupon($id)
    {
        // 查看优惠券的发放类型
        $coupon = api('shop','Coupon','couponInfo',$id);

        $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['action'] = 'select_goods';      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题

        $this->assign($form);
        // 输出用户列表
        $userList = $this->select_user();
        $this->assign('user_list',$userList);

        $this->assign('coupon',$coupon);
        return $this->fetch();
    }

    /**
     * 发送
     * @return mixed
     */
    public function send()
    {
        // 解析所有用户
        $id = request()->post('id');
        $userList = request()->post('user');
        $userList = explode(',',$userList);
        Db::startTrans();
        try{
            foreach ($userList  as $user){
                api('shop','CouponSend','send_coupon',[$user,$id]);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return $this->error($e->getMessage());
        }
        return $this->success('发放成功');
    }


    /**
     * 发放到当前条件下的所有会员
     * @return mixed
     */
    public function sendAll()
    {
        $id = request()->post('id');
        $where = empty(session('search_data')) ? '' : session('search_data');
        $userList = Db::name('users')->where('status',1)->where($where)->column('id');
        Db::startTrans();
        try{
            foreach ($userList  as $user){
                api('shop','CouponSend','send_coupon',[$user,$id]);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            Log::error($e);
            $this->error($e->getMessage());
        }
        $this->success('发放成功');
    }


    /**
     * 选择用户
     * @return mixed
     */
    public function select_user()
    {
        $where = '';
        $where['transe'] = 0;
        // 根据筛选条件生成查询代码
        // if (!empty($data['mobile'])) {
        //     $where['mobile'] = ['LIKE',"%{$data['mobile']}%"];
        // }
        session('search_data',null);
        $data = request()->param();
        if (!empty($data['old'][0]) && !empty($data['old'][1])) {
            $year = date('Y');
            $today = date('md');
            $start = (int)$year - (int)$data['old'][1];
            $end = (int)$year - (int)$data['old'][0];
            $start .= $today;
            $end .= $today;
            $birth_arae = $start.','.$end;
            $where['birthday'] = ['BETWEEN',$birth_arae];
        }
        if (!empty($data['level'])) {
            $where['level'] = $data['level'];
        }
        if (!empty($data['sex'])) {
            $where['sex'] = $data['sex'];
        }
        if (!empty($data['reg_time'][0]) && !empty($data['reg_time'][1])) {
            $where['reg_time'] = ['BETWEEN time',$data['reg_time']];
        }
        if (!empty($data['last_login'][0]) && !empty($data['last_login'][1])) {
            $where['last_login'] = ['BETWEEN time',$data['last_login']];
        }
        if (!empty($data['condition']) && !empty($data['keyword'])) {
            $where[$data['condition']] = ['LIKE',"%{$data['keyword']}%"];
        }

        // 将筛选条件原样输出至结果页
        // $mobile = empty($data['mobile'])?'':$data['mobile'];

        session('search_data',$where);
        $this->assign($data);
        $this->assign('user_level',api('member','User','selectLevel'));
        // 获取用户列表
        $listRow = tb_config('list_row',1);
        $userList = Db::name('users')->where('status',1)->order('last_login desc')->where($where)->field('id,nickname,mobile,level,sysid')->paginate($listRow);
        $this->assign('user_list',$userList);
        if( empty($data) ){
            return $this->fetch();
        }
        return $userList;
    }


    /**
     * 等级模块
     * @return mixed
     */
    public function levelTemp()
    {
        $id = request()->post('id');
        $fetch = 'level_add_'.$id;
        // 查找用户等级
        $user_level = api('member','User','selectLevel');
        $this->assign('user_level',$user_level);

        // 获取优惠券类型
        $coupon_type = api('shop','Coupon','discountType');
        $this->assign('coupon_type',$coupon_type);

        // 获取优惠券发放类型
        $payment_type = api('shop','Coupon','sendType');
        $this->assign('payment_type',$payment_type);

        // 获取优惠券等级信息
        $couponLevelInfo = api('shop','Coupon','couponLevelInfo',$id);
        $this->assign('levelInfo',$couponLevelInfo);

        $temp =  $this->fetch($fetch);
        $this->success($temp);
    }


    /**
     * 输出绑定方式内容
     * @return mixed
     */
    public function ajax_bind_type()
    {
        if( empty(request()->param('type')) ){
            $this->error('请选择绑定类型');
        }
        return $this->fetch('bind_type_'.request()->param('type'));
    }

    /**
     * 选择商品方法
     * 前台通过ajax提供指令
     * cmd：select_type  选择分类
     * cmd: select_goods 选择商品
     */
    public function select_goods()
    {
        $type = $this->request->param('type') == 'checkbox' ? $this->request->param('type') : 'radio';
        $inputname = empty($this->request->param('inputname')) ? $this->request->param('inputname') : 'goods';
        $this->assign('inputtype',$type);
        $this->assign('inputname',$inputname);
        //是否有查询条件
        if( $this->request->isAjax() == false ){
            $shop_id = 0;
            if( !empty(request()->param('shop_id')) ){
                $shop_id =  request()->param('shop_id');
            }
            //获取所有数据
            $form['action'] = 'select_goods';
            //->where('id not in(select `tb_shop_promotion_goods`.goods_id from `tb_shop_promotion_goods`)')
            if( $shop_id > 0 ){
                $goods_list = Db::name('shop_goods') ->where(['trash'=>0]) ->where('shop_id',$shop_id) ->field('id,title,shop_price,stock')->order('id desc')->paginate(tb_config('list_rows',1,$this->lang));

            }else{
                $goods_list = Db::name('shop_goods') ->where(['trash'=>0]) ->field('id,title,shop_price,stock')->order('id desc')->paginate(tb_config('list_rows',1,$this->lang));

            }
            $form['web_title'] = lang('model_form_add_title');   // 页面标题
            $form['action'] = 'select_goods';      //表单提交的目的路径
            $form['web_title'] = lang('content_form_add_title');   // 页面标题
            $form_fields = [
                [lang('cate_form_field_parent'),'linkselect','pid',0,'',lang('cate_form_field_parent_tips'),'Goods/ajaxGetSubCate']
            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_fields);
            $this->assign($form);
            $this->assign('shop_id',$shop_id);
            $this->assign('goods_list',$goods_list);
            return $this->fetch();
        }else{
            //条件筛选
            $pid_f = $this->request->param('pid_f') ? $this->request->param('pid_f') : '';
            $pid_s = $this->request->param('pid_s') ? $this->request->param('pid_s') : '';
            $pid = $this->request->param('pid') ? $this->request->param('pid') : '';
            $shop_id = $this->request->param('shop_id') ? $this->request->param('shop_id') : '';

            //设定分类条件
            if( $pid !== '' ) {

            }elseif ( $pid_s !== '' ){
                $category = Db::name('goods_category')->where('FIND_IN_SET('.$pid_s.',parent_id_path)')->column('id');
                $pid = $category;
            }elseif ( $pid_f !== '' ){
                $category = Db::name('goods_category')->where('FIND_IN_SET('.$pid_f.',parent_id_path)')->column('id');
                $pid = $category;
            }
            //设置关键字条件
            $keyStr = $this->request->param('search_str') ? $this->request->param('search_str') : '';
            //设置查询条件
            $where = '';
            if( $pid !== '' ) {
                if(is_array($pid)){
                    $pid = implode(',',$pid);
                }
                $where = ' `cat_id` in('.$pid.') ';
            }
            if( $keyStr !== '' ) {
                if ($where !== '') {
                    $where .= ' or ';
                }
                $where .= ' `title` like "%'.$keyStr.'%"';
            }
            //是否是新品或者推荐
            if( $this->request->param('comm') ){
                if( $where !== '' ) {
                    $where .= ' or ';
                }
                if($this->request->param('comm') == 1) {
                    //推荐
                    $where .= ' `is_comm` = 1 ';
                }elseif ($this->request->param('comm') == 2){
                    //新品
                    $where .= ' `is_new` = 1 ';
                }
            }

            if( !empty($shop_id) ){
                $where2 = '`shop_id`='.$shop_id;
            }else{
                $where2 = '';
            }
            //->where('id not in(select `tb_shop_promotion_goods`.goods_id from `tb_shop_promotion_goods`)')
            $goods_list = Db::name('shop_goods')->where($where)->where($where2)->field('id,title,shop_price,stock')->order('id desc')->paginate(tb_config('list_rows',1,$this->lang));
            $this->assign('goods_list',$goods_list);
            return $this->fetch('select_goods_table');
        }
    }


    /**
     * 获取商品分类
     * 三级联动选择
     * @return mixed
     */
    public function goodsCategory()
    {
        if( !empty(request()->param('pid')) ){
            $pid = request()->param('pid');
        }else{
            $pid = 0;
        }
        $goodsCategory = api('shop','Goods','category',$pid);
        if( empty($goodsCategory) ){
            $this->error('该分类没有下级分类');
        }
        $categoryTemp = '<option value="">请选择</option>';
        foreach ($goodsCategory as $category)
        {
            $categoryTemp .= '<option value="'.$category['id'].'">'.$category['name'].'</option>';
        }
        $this->success($categoryTemp);
    }


    /**
     * 查找店铺
     * @param int $sysid
     * @return mixed
     */
    public function selectShop($sysid)
    {
        $userList = api('cust','Cust','selectCustFromSysid',[$sysid,'id,shop_name,sysid']);
        $userListHtml = '';
        if( !empty($userList)){
            foreach ($userList as $user){
                $userListHtml .= '<option value="'.$user['id'].'" id="shop'.$user['id'].'">'.$user['shop_name'].'('.$user['sysid'].')</option>';
            }
        }
        if( empty($userListHtml) ){
            $this->error('当前条件下没有找到店铺');
        }
        $this->success($userListHtml);
    }


    /**
     * 获取邀请发放层级
     * @return mixed
     */
    public function ajaxHierarchy()
    {

        return $this->fetch();
    }


    /**
     * 优惠券配置项
     * @return mixed
     */
    public function config()
    {
        // 查询所有优惠券等级
        $couponLevelList = api('shop','Coupon','couponLevel');
        $this->assign('level_list',$couponLevelList);

        // 获取优惠券类型
        $coupon_type = api('shop','Coupon','discountType');
        $this->assign('coupon_type',$coupon_type);

        // 获取优惠券发放类型
        $payment_type = api('shop','Coupon','sendType',[true]);
        $this->assign('payment_type',$payment_type);

        return $this->fetch();
    }


    /**
     * 发放方式配置
     * @return mixed
     */
    public function saveSendTypeConfig()
    {

        $data = request()->param();
        $rule = [
            'name' => 'require',
        ];
        $message = [
            'name.require' => '请输入发放方式的名称',
        ];
        $check = api('sys','Verification','valiCheck',[$rule,$data,$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        // 保存数据
        $id = $data['id'];

        $UPData = [
            'name' => $data['name'],
            'explain' => empty($data['explain']) ? '' : $data['explain'],
            'status' => empty($data['status']) ? '' : $data['status'],
        ];

        // 修改数据
        try{
            Db::name('shop_coupon_send_type')->where('id',$id)->update($UPData);
        }catch (\ErrorException $e){
            $this->error($e->getMessage());
        }
        $this->success('更新成功!');

    }


    /**
     * 券等级配置
     * @return mixed
     */
    public function saveLevelConfig()
    {
        $data = request()->param();
        $rule = [
            'name' => 'require',
            'en_name' => 'require',
        ];
        $message = [
            'name.require' => '请输入券的名称',
            'en_name.require' => '请输入券的英文标识',
        ];
        $check = api('sys','Verification','valiCheck',[$rule,$data,$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        // 保存数据
        $id = $data['id'];

        $UPData = [
            'name' => $data['name'],
            'en_name' => $data['en_name'],
            'explain' => empty($data['explain']) ? '' : $data['explain'],
            'sketch' => empty($data['sketch']) ? '' : $data['sketch'],
            'description' => empty($data['description']) ? '' : $data['description'],
        ];

        // 修改数据
        try{
            Db::name('shop_coupon_level')->where('id',$id)->update($UPData);
        }catch (\ErrorException $e){
            $this->error($e->getMessage());
        }
        $this->success('更新成功!');
    }


    /**
     * 有使用该优惠券的订单列表
     * @param
     */





}