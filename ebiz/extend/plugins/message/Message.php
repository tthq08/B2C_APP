<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 信息发送接口
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace plugins\message;

class Message
{

    protected $msg_tp;
    protected $msg;

    public function __construct($msg_tp = 'sms')
    {
        //查看
        $this->msg_tp = $msg_tp;

        //实例化快递接口类
        $kdPsr = "plugins\\message\\".$this->msg_tp.'\\'.ucfirst($this->msg_tp);
        $this->msg = new $kdPsr();
        if( class_exists($kdPsr) ){

        }else{
            return '接口不存在';
        }
    }

    // 直接发送至收件号码/地址
    public function sendMsg($to='',$title='',$content='',$country='82')
    {
        // 消息发送
        $re = $this->msg->sendMsg($to,$title,$content,$country);
        return $re;
    }

    // 消息发送至用户对应的号码/地址
    public function sendUserMsg($to='',$title='',$content='',$country='82')
    {
        // 消息发送
        $re = $this->msg->sendUserMsg($to,$title,$content,$country);
        return $re;
    }

}