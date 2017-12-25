<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 促销后台管理模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\admin;

use app\sys\controller\AdminBase;
use app\shop\model\ShopPromotion as ShopPromotionModel;
use think\Request;
use think\Db;
use app\common\JunCreater\JCreater;
use app\shop\validate\PromotionType;


class Promotion extends AdminBase
{
    //基本促销管理表，基础表，包含所有促销
    protected $_PDB;

    /**
     * 控制器初始化
     */
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        //定义$_PDB
        $this->_PDB = new ShopPromotionModel();
    }

    /**
     * 促销活动列表，所有活动列表
     * 数据表: tb_promotion
     * 列表显示:
     *      活动名称
     *      活动类型
     *      活动ID
     *      商品ID
     *      活动开始时间
     *      活动结束时间
     */
    public function index()
    {
        //输出所有活动
        $promList = $this->_PDB->order('prom_id desc')->paginate(tb_config('list_rows',1,$this->lang));
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        //title定义
        $table_head = [
            ['prom_id','ID','text'],
            ['p_type',lang('prom_table_title_p_type'),'text'],
            ['p_id',lang('prom_table_title_p_id'),'text'],
            ['goods',lang('goods_name'),'text'],
            ['start_time',lang('start_time'),'text'],
            ['end_time',lang('end_time'),'text'],
            ['btn',lang('content_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Promotion/edit','prom_id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Promotion/del','prom_id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);
        $page = $promList->render();
        $this->assign('page', $page);
        $this->assign($table);
        $data = $promList ->all();
        // dump($data);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加促销活动
     */
    public function add()
    {
        //================  表单项定义 =================
        $this->assign('step',1);
        $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['action'] = '';      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题
        $model_type = [lang('model_type_option_sys'),lang('model_type_option_usual'),lang('model_type_option_single')];

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('model_form_field_name'),'select','name','',lang('model_form_field_name_place'),lang('model_form_field_name_tips'),[],'required'],
            [lang('model_form_field_title'),'text','title','',lang('model_form_field_title_place'),lang('model_form_field_title_tips'),[],'required'],
            [lang('model_form_field_table'),'text','table','',lang('model_form_field_table_place'),lang('model_form_field_table_tips')],
            [lang('model_form_field_type'),'radio','type','','',lang('model_form_field_tips'),$model_type],
            [lang('model_form_field_status'),'radio','status',1,'','',[lang('model_form_status_disable'),lang('model_form_status_enable')]],
            [lang('model_form_field_sort'),'text','sort','100'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        //================  表单项定义 end =================

        return $this->fetch('sys@Base/form');

    }

    /**
     * 促销信息修改
     */
    public function edit(Request $request)
    {
        //获取ID
        $prom_id = $request->param('prom_id');
        if( intval($prom_id) == 0 )
        {
            return $this->error(lang('PromID_is_null'),'Promotion/index');
        }
        //获取基本信息，转到详细促销修改页面
        $p_type= getTableValue('shop_promotion','prom_id='.$prom_id,'p_type');
        switch ($p_type)
        {
            case 1:
                $ctl = 'PanicBuying';
                break;
            case 2:
                $ctl = 'GroupBuying';
                break;
            case 3:
                $ctl = 'DiscountProm';
                break;
            case 4:
                $ctl = 'OrderProm';
                break;
        }
        if( $ctl == '' )
        {
            return $this->error(lang('Promotion_type_is_not_exist'),'Promotion/index');
        }
        $p_id= getTableValue('shop_promotion','prom_id='.$prom_id,'p_id');
        //$prom = new PanicBuying();
        //$prom->edit($p_id);
        $this->redirect($ctl.'/edit',['id'=>$p_id]);
    }

    /*
     * 删除当前
     */
    public function del(Request $request)
    {
        //获取ID
        $prom_id = $request->param('prom_id');
        if( intval($prom_id) == 0 )
        {
            return $this->error(lang('PromID_is_null'),'Promotion/index');
        }
        //获取基本信息，转到详细促销修改页面
        $p_type= getTableValue('shop_promotion','prom_id='.$prom_id,'p_type');
        switch ($p_type)
        {
            case 1:
                $prom = new PanicBuying();
                break;
            case 2:
                $prom = new GroupBuying();
                break;
            case 3:
                $prom = new DiscountProm();
                break;
            case 4:
                $prom = new OrderProm();
                break;
        }
        if( $p_type == '' )
        {
            return $this->error(lang('Promotion_type_is_not_exist'),'Promotion/index');
        }
        $p_id= getTableValue('shop_promotion','prom_id='.$prom_id,'p_id');
        $prom->del($p_id);
    }

    /**
     * 促销分类列表
     * 数据表: tb_shop_promotion_type
     * 显示当前所有的促销分类
     * 显示列表：
     *      分类ID
     *      分类标题
     *      分类控制器名称
     *      分类数据表
     *      状态
     *      操作
     */
    public function type()
    {
        //================  表格信息定义 =================
        // 是否显示表格的选择列？
        $table['show_check'] = 1;
        //标题title定义
        $table_head = [
            ['id','ID','text'],
            ['title',lang('prom_type_title'),'text'],
            ['controller',lang('prom_type_controller'),'text'],
            ['table',lang('prom_type_table'),'text'],
            ['status',lang('content_list_title_6'),'switch','Promotion/setvalue','id'],
            ['btn',lang('content_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);
        //按钮
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Promotion/type_edit','prom_id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Promotion/del','prom_id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);
        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Promotion/type_add'],
            [lang('comm_btn_del'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Promotion/type_del'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);
        $this->assign($table);
        //==================  表格信息定义 end  ===============
        //获取所有促销项
        $promTypeList = Db::name('shop_promotion_type')->where('status>=0')->paginate(tb_config('list_rows',1,$this->lang));
        $this->assign('data',$promTypeList);
        return $this->fetch('sys@Base/table');
    }

    /**
     * 促销分类添加
     */
    public function type_add()
    {
        //================  表单项定义 =================
        $this->assign('step',1);
        $form['web_title'] = lang('model_form_add_title');   // 页面标题
        $form['action'] = 'type_save';      //表单提交的目的路径
        $form['web_title'] = lang('content_form_add_title');   // 页面标题

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('prom_type_title'),'text','title','',lang('prom_type_title'),'',[],'require'],
            [lang('prom_type_controller'),'text','controller','',lang('prom_type_controller'),'',[],'require'],
            [lang('prom_type_table'),'text','table','',lang('prom_type_table'),'',[],'require'],
            [lang('model_form_field_status'),'radio','status',1,'','',[lang('model_form_status_disable'),lang('model_form_status_enable')]],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        //================  表单项定义 end =================

        return $this->fetch('sys@Base/form');

    }

    /**
     * 促销分类添加，修改保存
     */
    public function type_save()
    {
        $postData =  $this->post_data;
        $validate = new PromotionType();
        $saveData = $validate->check($postData);
        if( !$saveData )
        {
            $this->error($validate->getError(),'Promotion/type');
        }
        //检测ID是否存在，不存在就添加该项

        $typeModel = Db::name('shop_promotion_type');
        if( empty($postData['id']) )
        {
            $save = $typeModel->insert($postData);
        }else{
            $id = $postData['id'];
            if( intval($id) > 0 )
            {
                //编辑
                unset($saveData['id']);
                $save  = $typeModel->where('id',$id)->update($postData);
            }else{
                $this->error('ID_format_error','Promotion/type');
            }
        }
        if( $save == true )
        {
            $this->success('save_success','Promotion/type');
        }else{
            $this->error('save_failed','Promotion/type');
        }
    }

    public function setvalue($id)
    {
        $data = input('');
        if (isset($data['field']) && isset($data['val'])) {
            $field = $data['field'];
            $value = $data['val'];
        } else {
            $field = $data['field_name'];
            $value = $data['field'];
        }

        $res =	Db::name('shop_promotion_type') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    /**
     * 促销分类编辑
     *
     */
    public function type_edit()
    {

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
        $this->assign('inputtype',$type);
        //是否有查询条件
        if( $this->request->isAjax() == false ){
            //获取所有数据
            $form['action'] = 'select_goods';
            //->where('id not in(select `tb_shop_promotion_goods`.goods_id from `tb_shop_promotion_goods`)')
            $goods_list = Db::name('shop_goods') ->where(['trash'=>0]) ->field('id,title,shop_price,stock')->order('id desc')->paginate(tb_config('list_rows',1,$this->lang));
            $form['web_title'] = lang('model_form_add_title');   // 页面标题
            $form['action'] = 'select_goods';      //表单提交的目的路径
            $form['web_title'] = lang('content_form_add_title');   // 页面标题
            $form_fields = [
                [lang('cate_form_field_parent'),'linkselect','pid',0,'',lang('cate_form_field_parent_tips'),'Goods/ajaxGetSubCate']
            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_fields);
            $this->assign($form);
            $this->assign('goods_list',$goods_list);
            return $this->fetch();
        }else{
            //条件筛选
            $pid_f = $this->request->param('pid_f') ? $this->request->param('pid_f') : '';
            $pid_s = $this->request->param('pid_s') ? $this->request->param('pid_s') : '';
            $pid = $this->request->param('pid') ? $this->request->param('pid') : '';
            //设定分类条件
            if( $pid !== '' ) {
                $pid = $pid;
            }elseif ( $pid_s !== '' ){
                $category = Db::name('goods_category')->where('FIND_IN_SET('.$pid_s.',parent_id_path)')->column('id');
                $pid = $category;
            }elseif ( $pid_f !== '' ){
                $category = Db::name('goods_category')->fetchSql()->where('FIND_IN_SET('.$pid_f.',parent_id_path)')->column('id');
                $pid = $category;
            }
            //设置关键字条件
            $keyStr = $this->request->param('search_str') ? $this->request->param('search_str') : '';
            //设置查询条件
            $where = '';
            if( $pid !== '' ) {
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
            //->where('id not in(select `tb_shop_promotion_goods`.goods_id from `tb_shop_promotion_goods`)')
            $goods_list = Db::name('shop_goods')->where($where)->where('id not in(select `tb_shop_promotion_goods`.goods_id from `tb_shop_promotion_goods`)')->field('id,title,shop_price,stock')->order('id desc')->paginate(tb_config('list_rows',1,$this->lang));
            $this->assign('goods_list',$goods_list);
            return $this->fetch('select_goods_table');
        }
    }

    /**
     * 获取商品规格
     */
    public function getGoodsSpec()
    {
        $type = $this->request->param('type') == 'checkbox' ? $this->request->param('type') : 'radio';
        $this->assign('inputtype',$type);
        //检测商品存不存在
        $goods_id = request()->param('goods_id');
        $id = Db::name('shop_goods')->where('id',$goods_id)->value('id');
        if( $id !== '' ) {
            $spec_list = Db::name('shop_spec_price')->field('id,goods_id,key_sign,key_name,price,store_count')->where('goods_id',$id)->select();
            $checked_spec = json_decode(htmlspecialchars_decode(urldecode(request()->param('spec_json'))),true);
            //取出数组所有键，用逗号拼接，用于判断是否已经选中,
            $specstr= '';
            if ( is_array($checked_spec)){
                $specstr = implode(',',array_keys($checked_spec));
            }
            $this->assign('specstr',$specstr);
            $this->assign('checked_spec',$checked_spec);
            $this->assign('spec_list',$spec_list);
            return $this->fetch();
        }else{
            $this->error(lang('goods_does_not_exist'));
        }
    }

    /**
     * 获取优惠体现输入表单
     *
     */
    public function getDiscountForm($id = '',$value = '')
    {
        $discountId = $id ?  $id : request()->param('id');
        $E_Type = Db::name('shop_discount_type')->where('id',$discountId)->value('type');
        if ( $E_Type == '' || $E_Type == 0 ) {
            $this->error(lang('discount_type_is_failed'));
        }
        $value = $value ? $value : request()->param('value');
        switch ($E_Type){
            case 1:
                //打折
                $reData = '<label class="layui-form-label">'.lang("discount_1").'</label>
                    <div class="layui-input-block">
                        <input type="text" name="expression" style="width: 80px;" placeholder="%" value="'.$value.'" autocomplete="off" class="layui-input">
                    </div>';
                break;
            case 2:
                //金额
                $reData = '<label class="layui-form-label">'.lang("discount_2").'</label>
                    <div class="layui-input-block">
                        <input type="text" name="expression" style="width: 80px;" placeholder="'.tb_config('web_currency',1).'0.00" value="'.$value.'" autocomplete="off" class="layui-input">
                    </div>';
                break;
            case 3:
                //代金券
                //查找所有代金券
                $coupon = Db::name('shop_coupon')->where('status',1)->where('end_time >='.date('Y-m-d H:i:s', NOW_TIME))->field('id,name')->select();
                $reData = '<label class="layui-form-label">'.lang('select_coupon').'</label><div class="layui-input-block" style="width: 200px;"><select name="city" lay-verify="required">';
                foreach ($coupon as $c){
                    $reData .= "<option value='{$c['id']}'>{$c['name']}</option>";
                }
                $reData .= '</div></select>';
                break;
            case 4:
                //积分
                $reData = '<label class="layui-form-label">'.lang('discount_3').'</label>
                    <div class="layui-input-block">
                        <input type="text" name="expression" style="width: 80px;"  placeholder="0" value="'.$value.'" autocomplete="off" class="layui-input">
                    </div>';
                break;
            case 5:
                //免邮机会次数
                $reData = '<label class="layui-form-label">'.lang('discount_5').'</label>
                    <div class="layui-input-block">
                        <input type="text" name="expression" style="width: 80px;"  placeholder="1" value="'.$value.'" autocomplete="off" class="layui-input">
                    </div>';
                break;
        }
        if( $value != '' ){
            return $reData;
        }
        $this->success($reData);
    }
}