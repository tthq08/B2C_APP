<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 公共数据验证
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\api;

use think\Db;
use think\Validate;

class Verification
{

    /**
     * 验证手机号码
     * @param string $mobile 手机号码
     * @param string $code 验证码
     * @return bool
     */
    public function check_sms_code($mobile='',$code='')
    {
        $sess_id = session_id();
        $code_serv = Db::name('admin_sms_code') ->where(['mobile'=>$mobile,'session_id'=>$sess_id,'is_used'=>'0']) ->where('end_time','>', NOW_TIME) ->order(['id'=>'DESC']) ->find();
        // 不论验证码是否验证通过，均将该验证码弃用
        Db::name('admin_sms_code') ->where('id',$code_serv['id']) ->setField('is_used',1);
        if (empty($code_serv) || $code!=$code_serv['code']) {
            return false;
        }else{
            return true;
        }
    }


    /**
     * 数据验证
     * @param string|array $rule 数据验证规则
     * @param string|array $data 需要验证的数据
     * @param array $message 错误的返回信息
     * @return mixed
     */
    public function valiCheck($rule,$data,$message = [])
    {
        // 实例化验证器
        $validate = new Validate($rule,$message);
        // 验证
        $checkData = $validate->check($data);
        if( $checkData == false ){
            return ['code'=>0,'error'=>$validate->getError()];
        }
        return ['code'=>1,'error'=>''];

    }

}