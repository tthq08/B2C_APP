<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 会员模块后台主控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\member\admin;

use app\sys\controller\AdminBase;
use app\common\JunCreater\JCreater;
use think\Db;

class Index extends AdminBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $lang = Db::name('lang') ->where('status',1) ->select();
        $this->assign('lang_list',$lang);
        return $this->fetch();
    }

    public function main()
    {
        # code...
    }

    public function lists()
    {
        // 是否显示表格的选择列？
        $this->assign('show_check',1);

        $data = input('');
        $where['transe'] = 0;
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
        if (!empty($data['condition']) && !empty($data['key'])) {
            $where[$data['condition']] = ['LIKE',"%{$data['key']}%"];
        }


        // 将筛选条件原样输出至结果页
        // $mobile = empty($data['mobile'])?'':$data['mobile'];
        $old = empty($data['old'])?'':implode('~', $data['old']);
        $level = empty($data['level'])?'':$data['level'];
        $sex = empty($data['sex'])?'':$data['sex'];
        $reg_time = empty($data['reg_time'])?'':implode('~', $data['reg_time']);
        $last_login = empty($data['last_login'])?'':implode('~', $data['last_login']);
        $sexs = [lang('member_form_filte_sex'),lang('member_form_title_sex_1'),lang('member_form_title_sex_2')];
        $condition = empty($data['condition'])?'':$data['condition'];
        $key = empty($data['key'])?'':$data['key'];
        $conditions = [
            'username'=>lang('member_list_head_username'),
            'nickname'=>lang('member_list_head_nickname'),
            'truename'=>lang('member_list_head_truename'),
            'email'=>lang('member_list_head_email'),
            'mobile'=>lang('member_list_head_phone')
        ];
        // 生成筛选区域页面代码
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'user_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('member_list_head_level'), 'linkage', 'level', $level, '', '', 'ajaxGetLvs'],
                [lang('member_list_filter_old'), 'numarea', 'old', $old],
//                [lang('member_list_filter_sex'), 'radio', 'sex', $sex, '', '',$sexs],
                [lang('member_list_filter_reg'), 'timearea', 'reg_time', $reg_time],
                [lang('member_list_filter_last'), 'timearea', 'last_login', $last_login],
                [lang('member_list_filter_condition'),'select','condition',$condition,'','',$conditions],
                ['','text','key',$key,lang('member_list_filter_key')],
            ]
        ];
        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['level',lang('member_list_head_level'),'text'],
            ['username',lang('member_list_head_username'),'text'],
            ['truename',lang('member_list_head_truename'),'text'],
            ['nickname',lang('member_list_head_nickname'),'text'],
            ['old',lang('member_list_head_old'),'text'],
            ['sex',lang('member_list_head_sex'),'text'],
            ['mobile',lang('member_list_head_phone'),'text'],
            ['reg',lang('member_list_head_reg'),'text'],
            ['reg_time',lang('member_list_head_time'),'text'],
            ['last_login',lang('member_list_head_last'),'text'],
            ['total_amount',lang('member_list_head_total'),'text'],
            ['status',lang('member_list_head_status'),'switch','Index/setval','id'],
            ['btn',lang('member_list_head_action'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('member_btn_reset'),'frame',lang('member_reset_pwd_frame'),'fa fa-fw fa-key','layui-btn-warm','Index/resetPwd','id'],
            [lang('member_btn_recharge'),'frame',lang('member_add_mny_frame'),'fa fa-fw fa-credit-card-alt','','Index/addMny','id'],
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Index/edit','id'],
            [lang('comm_btn_del'),'confirm',lang('comm_del_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Index/del','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [
            [lang('comm_btn_add'),'frame',lang('comm_add_frame_title'),'fa fa-fw fa-plus','layui-btn-normal','Index/add'],
            [lang('comm_btn_dels'),'confirm_form',lang('comm_dels_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Index/dels'],
        ];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // // 表格数据，分页，每页10条
        $users = Db::name('users') ->where($where) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1),'',['query'=>$data]);  //取出当前语言版本下的所有配置项
        // dump(Db::name('users')->getLastSql());
        $sexs = [lang('member_form_title_sex_0'),lang('member_form_title_sex_1'),lang('member_form_title_sex_2')];
        $user_list = $users ->all();
        foreach ($user_list as $key => $user) {
            $user_list[$key]['level'] = getTableValue('user_level',['id'=>$user['level']],'level_name');
            $user_list[$key]['sex'] = $sexs[$user['sex']];
            $regStyle = getTableValue('user_bind',['user_id'=>$user['id']],'oauth');
            $user_list[$key]['reg'] = empty($regStyle)?lang('member_list_reg_self'):strtoupper($regStyle);
            $user_list[$key]['reg_time'] = date('Y-m-d H:i:s',$user['reg_time']);
            $user_list[$key]['last_login'] = empty($user['last_login'])?'-':date('Y-m-d H:i:s',$user['last_login']);

            // 年龄计算
            $birth_year = substr($user['birthday'], 0,4);
            $birth_year = empty($birth_year)?'0000':$birth_year;
            $birth_year = (int)$birth_year;
            $old = (int)date('Y') - $birth_year;
            if ($old == (int)date('Y')) {
                $old = '-';
            }
            if ($old == '0') {
                $old = 1;
            }
            $user_list[$key]['old'] = $old;
        }

        $this->assign('data',$user_list);
        // 获取分页显示
        $page = $users->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    public function ajaxGetLvs()
    {
        $lvs = Db::name('user_level') ->where('status','1') ->column('level_name','id');
        if ($lvs) {
            return ['code' => 1, 'msg' => 'success', 'data' => $lvs];
        } else {
            return ['code' => 0, 'msg' => 'Data null', 'data' => []];
        }
    }

    public function add()
    {
        $form['web_title'] = lang('member_form_window_add_title');   // 页面标题
        $form['action'] = url('save');      //表单提交的目的路径

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            [lang('member_form_title_head'),'img','head_pic'],
            [lang('member_form_title_nick'),'text','nickname','',lang('member_form_title_nick_place'),lang('member_form_title_nick_tips')],
            [lang('member_form_title_phone'),'text','mobile','',lang('member_form_title_phone_place'),lang('member_form_title_phone_tips')],
            [lang('member_form_title_mail'),'text','email','',lang('member_form_title_mail_place'),lang('member_form_title_mail_tips')],
            [lang('member_form_title_pass'),'password','password','',lang('member_form_title_pass_place'),lang('member_form_title_pass_tips'),[],'required'],
            [lang('member_form_title_repass'),'password','repass','',lang('member_form_title_repass_place'),lang('member_form_title_repass_tips'),[],'required'],
            [lang('member_form_title_sex'),'radio','sex',0,'','',[lang('member_form_title_sex_0'),lang('member_form_title_sex_1'),lang('member_form_title_sex_2')]],
            [lang('member_form_title_qq'),'text','qq'],
            [lang('member_form_title_introducer'),'text','recommended_mobile'],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch('sys@Base/form');
    }

    public function save()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            //手机号与邮箱都为空
            if (empty($data['mobile']) && empty($data['email'])) {
                $this->error(lang('member_handle_error_empty'));
            }
            // 再次输入的密码都是空
            if (empty($data['password']) || empty($data['repass'])) {
                $this->error(lang('member_handle_pass_empty'));
            }
            // 再次输入的密码不一致
            if ($data['password'] != $data['repass']) {
                $this->error(lang('member_handle_pass_error'));
            }

            unset($data['repass']);
            $data['password'] = encrypt_pwd($data['password']);
            $user_condition = empty($data['email'])?$data['mobile']:$data['email'];
            // 检测会员是否已经存在
            $user_exists = Db::name('users') ->where('email|mobile',$user_condition) ->find();
            if ($user_exists) {
                if ($user_exists['transe']==1) {
                    $this->error('member_handle_user_exist_dels');
                } else {
                    $this->error('member_handle_user_exist');
                }
            }
            // 如果推荐用户手机不为空,则取出推荐用户名的信息
            if (!empty($data['recommended_mobile'])) {
                $commUser = Db::name('users') ->where('email|mobile',$data['recommended_mobile']) ->find();
                if ($commUser) {
                    $data['introducer'] = $commUser['id'];
                    $tree_path_id = Db::name('users_tree') ->where('uid',$commUser['id']) ->value('tree_path_id');
                    $tree['tree_path_id'] = $tree_path_id.','.$commUser['id'];
                }else{
                    $data['introducer'] = 0;
                    $tree['tree_path_id'] = 0;
                }
            }else{
                $data['introducer'] = 0;
                $tree['tree_path_id'] = 0;
            }
            $data['sysid'] = time().mt_rand(100,9999);
            $res = Db::name('users') ->insertGetId($data);
            if ($res!==false) {
                Db::name('users_tree') ->insert(['uid'=>$res,'pid'=>$data['introducer'],'tree_path_id'=>$tree['tree_path_id']]);
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

    public function edit($id)
    {
        $form['web_title'] = lang('member_form_window_edit_title');   // 页面标题
        $form['action'] = url('update');      //表单提交的目的路径

        $userInfo = Db::name('users') ->find($id);
        $this->assign('head_pic',$userInfo['head_pic']);

        $useable_coupon = Db::name('user_coupon')->where(['is_use'=>0,'user'=>$id,'status'=>1])->where('end_time','>= time',time()) ->count();
        $sexs = [lang('member_form_title_sex_0'),lang('member_form_title_sex_1'),lang('member_form_title_sex_2')];
        $addr = Db::name('user_address') ->where(['user_id'=>$id,'is_default'=>1]) ->find();

        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
        $form_fields = [
            ['','hidden','id',$id],
            [lang('member_form_title_level'),'p','level',getTableValue('user_level',['id'=>$userInfo['level']],'level_name')],
            [lang('member_form_title_points'),'p','pay_points',$userInfo['pay_points']],
            [lang('member_form_title_coupon'),'p','coupon',$useable_coupon],
            [lang('member_form_title_username'),'p','username',$userInfo['username']],
            [lang('member_form_title_nick'),'p','nickname',$userInfo['nickname'],lang('member_form_title_nick_place'),lang('member_form_title_nick_tips')],
            [lang('member_form_title_truename'),'p','truename',$userInfo['truename']],
            [lang('member_form_title_sex'),'p','sex',$sexs[$userInfo['sex']]],
            [lang('member_form_title_birth'),'p','birthday',$userInfo['birthday']],
            [lang('member_form_title_phone'),'p','mobile',$userInfo['mobile']],
            [lang('member_form_title_mail'),'p','email',$userInfo['email']],
            [lang('member_form_title_sms'),'p','sms',$userInfo['sms_msg']],
            [lang('member_form_title_mail_msg'),'p','mail_msg',$userInfo['mail_msg']],
            ['','hr',''],
            [lang('member_form_title_bank'),'p','bank_name',$userInfo['bank_name']],
            [lang('member_form_title_bank_acc'),'p','bank_account',$userInfo['bank_account']],
            [lang('member_form_title_bank_no'),'p','bank_number',$userInfo['bank_number']],
            ['','hr',''],
            [lang('member_form_title_reg_time'),'p','reg_time',date('Y-m-d H:i:s',$userInfo['reg_time'])],
            [lang('member_form_title_last_login'),'p','last_login',date('Y-m-d H:i:s',$userInfo['last_login'])],
            [lang('member_form_title_total_fee'),'p','total_amount',$userInfo['total_amount']],
            ['','hr',''],
            [lang('member_list_head_addr'),'p','addr',$addr['base_addr'].$addr['address'].'  ['.$addr['en_address'].']'],
            [lang('member_list_head_zip'),'p','zip',$addr['clearance_sn']],
            [lang('member_list_head_tgh'),'p','tgh',$addr['zip']],
            ['','hr',''],
            [lang('member_form_title_pass'),'password','password','',lang('member_form_title_pass_place'),lang('member_form_title_pass_tips_edit')],
            [lang('member_form_title_repass'),'password','repass','',lang('member_form_title_repass_place'),lang('member_form_title_pass_tips_edit')],
            [lang('member_form_title_introducer'),'static','recommended_mobile',$userInfo['recommended_mobile']],
        ];
        $JCreater = new JCreater();
        $form['form_Html'] = $JCreater->form_build($form_fields);
        $this->assign($form);
        return $this->fetch();
    }

    public function update()
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            //手机号与邮箱都为空
            if (empty($data['mobile']) && empty($data['email'])) {
                $this->error(lang('member_handle_error_empty'));
            }

            // 再次输入的密码不一致
            if ($data['password'] != $data['repass']) {
                $this->error(lang('member_handle_pass_error'));
            }

            unset($data['repass']);
            if (!empty($data['password'])) {
                $data['password'] = encrypt_pwd($data['password']);
            }else{
                unset($data['password']);
            }
            $user_condition = empty($data['email'])?$data['mobile']:$data['email'];
            // 检测会员是否已经存在
            $user_exists = Db::name('users') ->where('email|mobile',$user_condition) ->find();
            if (!$user_exists) {
                $this->error('member_handle_user_null');
            }
            // 如果推荐用户手机不为空,则取出推荐用户名的信息
            if (!empty($data['recommended_mobile'])) {
                $commUser = Db::name('users') ->where('email|mobile',$data['recommended_mobile']) ->find();
                if ($commUser) {
                    $data['introducer'] = $commUser['id'];
                }
            }

            $res = Db::name('users') ->update($data);
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

    public function del($id)
    {
        // 执行软删除
        $res =  Db::name('users') ->where('id',$id) ->setField('transe','1');
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
        $ids = input('');
        $ids = implode(',', $ids['id']);
        // 执行软删除
        $res =  Db::name('users') ->where('id','IN',$ids) ->setField('transe','1');
        if ($res!==false) {
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        } else {
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_delete_error'));
        }
    }

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

        $res =  Db::name('users') ->where('id',$id) ->setField($field,$value);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function setattr($id)
    {
        $data = input('post.');
        if ($data['field'] == 'is_shop') {
            $introducer = Db::name('users') ->where('id',$id) ->value('introducer');
            if (!empty($introducer)) {
                $this->error(lang('member_shop_error'));
            }
        }
        $res =  Db::name('users') ->where('id',$id) ->setField($data['field'],$data['val']);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }
    }

    public function addMny($id=0)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            // $res = Db::name('users') ->where('id',$data['user_id']) ->setInc('agent_account',$data['account']);
            $res = Db::name('users') ->where('id',$data['user_id']) ->inc('agent_account',$data['account']) ->inc('pay_points',$data['points']) ->update();
            if ($res!==false) {
                $accountLogData = [
                    'user_id' => $data['user_id'],
                    'user_money' => $data['account'],
                    'change_time' => time(),
                    'desc' => '后台管理员充值',
                    'pay_points' => $data['points']
                ];
                //记录用户资金日志表
                Db::name('user_account_log')->insert($accountLogData);
                sys_log(lang('member_recharge_success'),1);  //操作结果写入系统日志
                $this->success(lang('member_recharge_success'));
            } else {
                sys_log(lang('member_recharge_error'),0);  //操作结果写入系统日志
                $this->error(lang('member_recharge_error'));
            }

        }else{
            $form['web_title'] = lang('member_add_mny_frame');   // 页面标题
            $form['action'] = url('');      //表单提交的目的路径

            // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
            $form_fields = [
                ['','hidden','user_id',$id],
                [lang('member_recharge_account'),'number','account','','','',[],'required'],
                [lang('member_recharge_points'),'number','points','','','',[],'required'],
            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_fields);
            $this->assign($form);
            return $this->fetch('sys@Base/form');
        }
    }


    public function resetpwd($id=0)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (empty($data['password']) || empty($data['repass'])) {
                $this->error(lang('member_reset_pass_null'));
            }
            if ($data['password'] != $data['repass']) {
                $this->error(lang('member_reset_pass_different'));
            }
            $password = encrypt_pwd($data['password']);
            $res = Db::name('users') ->where('id',$data['user_id']) ->update(['password'=>$password]);
            if ($res!==false) {
                sys_log(lang('member_reset_pass_success'),1);  //操作结果写入系统日志
                $this->success(lang('member_reset_pass_success'));
            } else {
                sys_log(lang('member_reset_pass_error'),0);  //操作结果写入系统日志
                $this->error(lang('member_reset_pass_error'));
            }

        }else{
            $form['web_title'] = lang('member_reset_window_title');   // 页面标题
            $form['action'] = url('');      //表单提交的目的路径
            $username = Db::name('users') ->where('id',$id) ->value('username');
            // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
            $form_fields = [
                ['','hidden','user_id',$id],
                [lang('member_reset_title_username'),'p','username',$username],
                [lang('member_reset_title_password'),'password','password','','','',[],'required'],
                [lang('member_reset_title_repass'),'password','repass','','','',[],'required'],
            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_fields);
            $this->assign($form);
            return $this->fetch('sys@Base/form');
        }
    }

    // 充值记录
    public function recharge()
    {
        // 筛选条件
        $data = input('');
        $where['id'] = ['>',0];
        if (count($data)==0) {
        }else{
            if (!empty($data['nickname'])) {
                $where['nickname'] = ['like',$data['nickname']];
            }
            if (!empty($data['time_start']) && !empty($data['time_end'])) {
                $where['ctime'] = ['between',[strtotime($data['time_start']),strtotime($data['time_end'])]];
            }
        }
        // dump($where);

        //表格顶部筛选表单
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'recharge_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('enchash_list_filter_condtion_nick'),'text','nickname','',lang('enchash_list_filter_condtion_nick')],
                [lang('recharge_list_filter_condtion_start'),'date','time_start','',lang('recharge_list_filter_condtion_start')],
                [lang('recharge_list_filter_condtion_end'),'date','time_end','',lang('recharge_list_filter_condtion_end')],
            ]
        ];

        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        // 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['user_id',lang('recharge_list_head_title_userid'),'text'],
            ['nickname',lang('recharge_list_head_title_nick'),'text'],
            ['order_sn',lang('recharge_list_head_title_ordersn'),'text'],
            ['account',lang('recharge_list_head_title_account'),'text'],
            ['ctime',lang('recharge_list_head_title_ctime'),'text'],
            ['pay_name',lang('recharge_list_head_title_pay'),'text'],
            ['pay_status',lang('recharge_list_head_title_status'),'text'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        // 设置列表页顶部按钮组
        $top_btn = [];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $recharges = Db::name('user_recharge')->where($where) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有配置项
        $recharge_list = $recharges ->all();
        foreach ($recharge_list as $key => $model) {
            $status = [lang('recharge_list_order_status_opt_0'),lang('recharge_list_order_status_opt_1'),lang('recharge_list_order_status_opt_2')];
            $recharge_list[$key]['pay_status'] = $status[$model['pay_status']];
            $recharge_list[$key]['ctime'] = $model['ctime'];
        }
        $this->assign('data',$recharge_list);
        // 获取分页显示
        $page = $recharges->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    // 取现申请
    public function enchashment()
    {
        // 筛选条件
        $data = input('');
        $where['id'] = ['>',0];
        if (count($data)==0) {
        }else{
            if (!empty($data['accname'])) {
                $where['account_name'] = ['like',"%{$data['accname']}%"];
            }
            if (!empty($data['account'])) {
                $where['account_bank'] = ['like',"%{$data['account']}%"];
            }
            if (!empty($data['time_start']) && !empty($data['time_end'])) {
                $where['create_time'] = ['between',[strtotime($data['time_start']),strtotime($data['time_end'])]];
            }
        }
        // dump($where);

        //表格顶部筛选表单
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'recharge_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('enchash_list_filter_condtion_accname'),'text','accname','',lang('enchash_list_filter_condtion_accname')],
                [lang('enchash_list_filter_condtion_account'),'text','account','',lang('enchash_list_filter_condtion_account')],
                [lang('enchash_list_filter_condtion_start'),'date','time_start','',lang('enchash_list_filter_condtion_start')],
                [lang('enchash_list_filter_condtion_end'),'date','time_end','',lang('enchash_list_filter_condtion_end')],
            ]
        ];

        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        // 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['user_id',lang('enchash_list_head_title_userid'),'text'],
            ['create_time',lang('enchash_list_head_title_time'),'text'],
            ['money',lang('enchash_list_head_title_money'),'text'],
            ['bank_name',lang('enchash_list_head_title_bank'),'text'],
            ['account_bank',lang('enchash_list_head_title_account'),'text'],
            ['account_name',lang('enchash_list_head_title_accName'),'text'],
            ['status',lang('enchash_list_head_title_status'),'text'],
            ['btn',lang('model_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        /*设置表格各行的操作按钮，
        * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数,(显示条件)]
        */
        $btn = [
            [lang('comm_btn_edit'),'frame',lang('comm_edit_frame_title'),'fa fa-fw fa-pencil-square-o','layui-btn-normal','Index/enchash_do','id'],
        ];
        $table['btn_lst'] = JCreater::table_btn($btn);

        // 设置列表页顶部按钮组
        $top_btn = [];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $recharges = Db::name('user_withdrawals')->where($where) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有配置项
        $recharge_list = $recharges ->all();
        foreach ($recharge_list as $key => $model) {
            $status = [lang('enchash_list_order_status_opt_0'),lang('enchash_list_order_status_opt_1'),lang('enchash_list_order_status_opt_2')];
            $recharge_list[$key]['status'] = $status[$model['status']];
            // $recharge_list[$key]['create_time'] = date('Y-m-d H:i:s',$model['create_time']);
        }
        $this->assign('data',$recharge_list);
        // 获取分页显示
        $page = $recharges->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

    public function enchash_do($id)
    {
        if (request()->isPost()) {
            $data = $this->post_data;
            if (empty($data['remark'])) {
                $this->error(lang('enchash_form_error_remark_empty'));
            };
            $account = getTableValue('users',['id'=>$data['user_id']],'frozen_money');
            if ($data['status']=='1') {
                if ($account<$data['money']) {
                    $this->error(lang('enchash_form_error_money_lower'));
                }
            }else{
                // 取出冻结金额和申请金额之间的最小数额,返回至用户余额
                $money_back = $account<$data['money']?$account:$data['money'];
                $res = Db::name('users') ->where('id',$data['user_id']) ->setDec('frozen_money',$money_back);
                $res = Db::name('users') ->where('id',$data['user_id']) ->setInc('user_money',$money_back);
                $withdrawals = Db::name('user_withdrawals') ->find($id);
                // 记入用户资金变动表
                $accountData = [
                    'user_id' => $withdrawals['user_id'],
                    'user_money' =>  $money_back,
                    'frozen_money' =>  -$money_back,
                    'change_time' => time(),
                    'desc' => lang('enchash_account_log_err_desc'),
                ];
                Db::name('user_account_log') ->insert($accountData);
            }
            $res = Db::name('user_withdrawals') ->update($data);
            if ($res!==false) {
                if ($data['status']==1) {
                    // 扣除用户冻结金额
                    $res = Db::name('users') ->where('id',$data['user_id']) ->setDec('frozen_money',$data['money']);
                    if ($res!==false) {
                        $withdrawals = Db::name('user_withdrawals') ->find($id);
                        // 记入用户资金变动表
                        $accountData = [
                            'user_id' => $withdrawals['user_id'],
                            'frozen_money' =>  -$withdrawals['money'],
                            'change_time' => time(),
                            'desc' => lang('enchash_account_log_desc'),
                        ];
                        Db::name('user_account_log') ->insert($accountData);
                        // 插入转账记录
                        $remitData = [
                            'user_id' => $withdrawals['user_id'],
                            'bank_name' => $withdrawals['bank_name'],
                            'account_bank' => $withdrawals['account_bank'],
                            'account_name' => $withdrawals['account_name'],
                            'money' => $withdrawals['money'],
                            'status' => 1,
                            'create_time' => time(),
                            'admin_id' => session('admin_id'),
                            'remark' => $withdrawals['remark'],
                            'withdrawals_id' => $id,
                        ];
                        Db::name('user_remittance') ->insert($remitData);
                    }
                }
                sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                $this->success(lang('comm_update_success'));
            }else{
                sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
                $this->error(lang('comm_update_error'));
            }
            // dump($data);
        }else{
            $enchash = Db::name('user_withdrawals') ->find($id);
            $enchash['username'] = getTableValue('users',['id'=>$enchash['user_id']],'nickname');
            $enchash['account'] = getTableValue('users',['id'=>$enchash['user_id']],'frozen_money');

            $form['web_title'] = lang('enchash_form_win_title');   // 页面标题
            $form['action'] = '';      //表单提交的目的路径
            $status = [lang('enchash_list_order_status_opt_0'),lang('enchash_list_order_status_opt_1'),lang('enchash_list_order_status_opt_2')];

            // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证方式)]
            $form_fields = [
                ['','hidden','id',$id],
                ['','hidden','user_id',$enchash['user_id']],
                ['','hidden','money',$enchash['money']],
                [lang('enchash_form_title_user'),'static','username',$enchash['username']],
                [lang('enchash_form_user_account'),'static','account',$enchash['account']],
                [lang('enchash_form_user_money'),'static','money',$enchash['money']],
                [lang('enchash_form_user_bank'),'static','bank_name',$enchash['bank_name']],
                [lang('enchash_form_user_bank_acc'),'static','account_bank',$enchash['account_bank']],
                [lang('enchash_form_user_bank_name'),'static','account_name',$enchash['account_name']],
                [lang('enchash_form_user_time'),'static','create_time',$enchash['create_time']],
                [lang('enchash_form_user_status'),'static','status',$status[$enchash['status']]],
                [lang('enchash_form_user_remark'),'textarea','remark',$enchash['remark']],
                [lang('enchash_form_user_tips'),'p','',lang('enchash_form_user_tips_text')],

            ];
            $JCreater = new JCreater();
            $form['form_Html'] = $JCreater->form_build($form_fields);

            if ($enchash['status']==0) {
                // 定义自定义表单按钮
                $form['button_diy'] = '<button type="button" class="layui-btn" onclick="confirm(1);">'.lang('enchash_form_btn_comfirm').'</button><button type="button" class="layui-btn layui-btn-danger" onclick="confirm(2);">'.lang('enchash_form_btn_cancle').'</button>';
                // 定义自定义JS脚本
                $form['script_diy'] = 'function confirm(status) {
                        $.ajax({
                            type : "POST",
                            url:$("form").attr("action"),
                            data : $("form").serialize()+"&status="+status,
                            dataType : "json",
                            success: function(data){
                                layer.msg(data.msg,{time:1000},function()
                                {
                                    if (data.code==1) {
                                        location.reload();
                                    }
                                });
                            }
                        });
                    }';
            }else{
                $form['button_diy'] = lang('enchash_form_status_done');
            }
            $this->assign($form);
            return $this->fetch('sys@Base/form');
        }
    }

    // 汇款记录
    public function remit()
    {
        // 筛选条件
        $data = input('');
        $where['id'] = ['>',0];
        if (count($data)==0) {
        }else{
            if (!empty($data['accname'])) {
                $where['account_name'] = ['like',"%{$data['accname']}%"];
            }
            if (!empty($data['account'])) {
                $where['account_bank'] = ['like',"%{$data['account']}%"];
            }
            if (!empty($data['time_start']) && !empty($data['time_end'])) {
                $where['ctime'] = ['between',[strtotime($data['time_start']),strtotime($data['time_end'])]];
            }
        }
        // dump($where);

        //表格顶部筛选表单
        $filter = [
            'method' => 'get',
            'action' => '',
            'post_id' => 'recharge_filter',
            'btn_title' => lang('comm_btn_filter'),
            'fields' => [
                [lang('remit_list_filter_condtion_accname'),'text','accname','',lang('remit_list_filter_condtion_accname')],
                [lang('remit_list_filter_condtion_account'),'text','account','',lang('remit_list_filter_condtion_account')],
                [lang('remit_list_filter_condtion_start'),'date','time_start','',lang('remit_list_filter_condtion_start')],
                [lang('remit_list_filter_condtion_end'),'date','time_end','',lang('remit_list_filter_condtion_end')],
            ]
        ];

        $JCreater = new JCreater();
        $table['filter'] = $JCreater->table_filter($filter);

        // 是否显示表格的选择列？
        $this->assign('show_check',1);
        /*设置表格的表头，表格将按顺序显示设置的表头
        * 设置说明： [字段,标题,类型,(绑定路径),(提交参数),(选项数组),(样式css)]
        */
        $table_head = [
            ['id','ID','text'],
            ['username',lang('remit_list_head_title_user'),'text'],
            ['bank_name',lang('remit_list_head_title_bank'),'text'],
            ['account_bank',lang('remit_list_head_title_account'),'text'],
            ['account_name',lang('remit_list_head_title_accName'),'text'],
            ['money',lang('remit_list_head_title_money'),'text'],
            ['create_time',lang('enchash_list_head_title_time'),'text'],
            ['status',lang('enchash_list_head_title_status'),'text'],
            // ['btn',lang('model_list_title_7'),'btn'],
        ];
        $table['tb_title'] = JCreater::table_header($table_head);

        // 设置列表页顶部按钮组
        $top_btn = [];
        $table['top_btn'] = JCreater::table_btn($top_btn);

        $this->assign($table);

        // 表格数据，分页，每页10条
        $recharges = Db::name('user_remittance')->where($where) ->order(['id'=>'DESC']) ->paginate(tb_config('list_rows',1,$this->lang),'',['query'=>$data]);  //取出当前语言版本下的所有配置项
        $recharge_list = $recharges ->all();
        foreach ($recharge_list as $key => $model) {
            $status = [lang('enchash_list_order_status_opt_0'),lang('enchash_list_order_status_opt_1'),lang('enchash_list_order_status_opt_2')];
            $recharge_list[$key]['status'] = $status[$model['status']];
            $recharge_list[$key]['create_time'] = date('Y-m-d H:i:s',$model['create_time']);
            $recharge_list[$key]['username'] = getTableValue('users',['id'=>$model['user_id']],'nickname');
        }
        $this->assign('data',$recharge_list);
        // 获取分页显示
        $page = $recharges->render();
        $this->assign('page', $page);
        return $this->fetch('sys@Base/table');
    }

}