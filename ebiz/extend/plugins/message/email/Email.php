<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 发送email邮件插件
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace plugins\message\email;
use plugins\message\Message;

class Email extends Message
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
    * @param string $subject   邮件标题
	* @param string $content   邮件内容(html模板渲染后的内容)
	* @throws Exception
	* @throws phpmailerException
    */
    public function sendUserMsg($to='',$title='',$content='',$country='82')
    {
        $email = db('users','',false) ->where('id',$to) ->value('email');
        if (empty($email)) {
            return ['code'=>10002,'msg'=>'用户没有设置邮箱，无法发送。'];
        } else {
            $result = $this->sendMsg($email,$title,$content,$country);
            return $result;
        }
    }

    /**
     * 邮件发送
     * @param $to    接收人
     * @param string $subject   邮件标题
     * @param string $content   邮件内容(html模板渲染后的内容)
     * @throws Exception
     * @throws phpmailerException
     */
    public function sendMsg($to='',$subject='',$content='',$country='86'){
        vendor('phpmailer.PHPMailerAutoload'); //调用phpmailer 扩展
        // 读取插件的配置信息
        $config = $this->config;
        $subject = empty($subject)?'系统通知 - '.tb_config('web_seo_name',1):$subject;
        $mail = new \PHPMailer();
        $mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;
        //调试输出格式
        //$mail->Debugoutput = 'html';
        //smtp服务器
        $mail->Host = $config['mail_smtp_serv'];
        //端口 - likely to be 25, 465 or 587
        $mail->Port = $config['mail_smtp_port'];

        if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;
        //用户名
        $mail->Username = $config['mail_account'];
        //密码
        $mail->Password = $config['mail_password'];
        //Set who the message is to be sent from
        $mail->setFrom($config['mail_account']);
        //回复地址
        //$mail->addReplyTo('replyto@example.com', 'First Last');
        // dump($mail);
        //接收邮件方
        if(is_array($to)){
            foreach ($to as $v){
                $mail->addAddress($v);
            }
        }else{
            $mail->addAddress($to);
        }

        $mail->isHTML(true);// send as HTML
        //标题
        $mail->Subject = $subject;
        //HTML内容转换
        $mail->msgHTML(htmlspecialchars_decode($content));
        //Replace the plain text body with one created manually
        //$mail->AltBody = 'This is a plain-text message body';
        //添加附件
        //$mail->addAttachment('images/phpmailer_mini.png');
        //send the message, check for errors
        $result = $mail->send();
        $msgLog = ['msg_tp'=>'email','msg_to'=>$to,'msg_title'=>$subject,'msg_content'=>$content,'msg_add'=>date('Y-m-d H:i:s'),'msg_status'=>0];
        if ($result) {
            $msgLog['msg_status'] = 1;
            $res = ['code'=>1,'msg'=>'发送成功'];
        }else{
            $res = ['code'=>10003,'msg'=>'发送失败'];
        }
        db('admin_msg_log','',false) ->insert($msgLog);
        return $res;
    }
}