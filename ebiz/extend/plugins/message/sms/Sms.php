<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 发送手机短信插件
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace plugins\message\sms;
use plugins\message\Message;

class Sms extends Message
{
	public $config;
    public $oauth;
    public $class_obj;

	public function __construct(){
        $msgPlugin = include "config.php";// 找到登录插件的配置
        $config_val = $msgPlugin['config'];
        foreach ($config_val as $key => $config) {
            $config_value[$config['name']] = $config['value'];
        }
        $this->config = $config_value;
    }

    /*  发送内容
    *	@param $to string 	用户ID 
    *	@param $title string 	短信标题，默认为空 
    *   @param $content string 	短信内容 
    *	@param $country string 	手机所在国家
    */
    public function sendUserMsg($to='',$title='',$content='',$country='82')
	{
		$user = db('users','',false) ->where('id',$to) ->find();
		// dump($user);
		if (empty($user)) {
			return ['code'=>10002,'msg'=>'用户没有设置手机号，无法发送。'];
		} else {
			$result = $this->sendMsg($user['mobile'],$title,$content,$user['country_code']);
			return $result;
		}		
	}

    /*  发送内容
    *	@param $mobile string 	接收手机号 
    *	@param $title string 	短信标题，默认为空 
    *   @param $content string 	短信内容 
    *	@param $country string 	手机所在国家
    */
    public function sendMsg($mobile='',$title='',$content='',$country='82')
	{
		$config = $this->config;
	    $sms_url = $config['sms_api'];
	    $sms_user = $config['sms_key'];
	    $sms_pwd = $config['sms_secret'];


	    // $soap = new SoapClient($sms_url.'?wsdl');
	    // $param = array_to_object(['username'=>$sms_user,'password'=>$sms_pwd,'mobileNum'=>$mobile,'content'=>$content,'productNum'=>'1001']);
	    // $result = $soap->__soapcall('SubmitSms', array($param));
	    // $result = $result->SubmitSmsResult;
	    // if(strlen($result)==18){
	    //     return true;
	    // }else{
	    //     return $result;     //返回平台报错信息
	    // }
	    
	    // if (strpos($mobile, '.') === false) {
	    //     $country = '82';
	    // }else{
	    //     $code = explode('.', $mobile);
	    //     $country = ltrim($code[0],'+');
	    //     $mobile = $code[1];
	    // }
	    $param = [
	        'id' => $sms_user,
	        'pwd' => $sms_pwd,
	        'from' => $config['sms_from_num'],
	        'to_country' => $country,
	        'to' => $mobile,
	        'message' => $config['sms_sign_key'].$content,
	        'report_req' => '1'
	    ];
	    // dump($param);
	    // TBMALL项目短信接口
	    $url = $sms_url . '?' . http_build_query($param);
	    // echo $url;
	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    $xml = simplexml_load_string($response);

    	$msgLog = ['msg_tp'=>'sms','msg_to'=>$mobile,'msg_content'=>$content,'msg_add'=>date('Y-m-d H:i:s'),'msg_status'=>0];
	    if ($xml->messages->message->err_code != 'R000') {
	    	db('admin_msg_log','',false) ->insert($msgLog);  // 失败发送记录写入数据库
	        return ['code'=>10003,'msg'=>$xml->messages->message->err_code];
	    } else {
            $msgLog['msg_status'] = 1;
            db('admin_msg_log','',false) ->insert($msgLog);	 // 成功发送记录写入数据库
	        return ['code'=>1,'msg'=>'发送成功'];
	    }
	}
}