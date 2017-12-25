<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 微信墙控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\mshop\home;

use think\Controller;
use think\Db;

/**
 * 前台公用基础控制器
 * Class HomeBase
 * @package app\Sys\controller
 */
class Wxwall extends Controller
{
	public function show()
	{
		$this->assign('timeStamp',time());
		$msgs = Db::name('wex_wall_msg')->where('wall_id',tb_config('wex_wall_sign',1)) ->order('id desc') ->limit(100) ->select();
		$msgs = array_reverse($msgs,true);
		$msgss = [];
		foreach ($msgs as $key => $msg) {
			$user = Db::name('wex_wall_user') ->where('openid',$msg['openid']) ->find();
			$msg = array_merge($msg,$user);
			$msgss[] = $msg;
		}
		// dump($msgss);
		$this->assign('msg_list',$msgss);
		$last = end($msgs);
		$max_id = $last['id'];
		$max_id = empty($max_id)?0:$max_id;
		$this->assign('last_id',$max_id);
		// dump($max_id);
		return $this->fetch();
	}

	public function ajaxGetMsg($max_id)
	{
		$msg = Db::name('wex_wall_msg')->where('wall_id',tb_config('wex_wall_sign',1)) ->where('id','>',$max_id) ->find();
		if ($msg) {
			$user = Db::name('wex_wall_user') ->where('openid',$msg['openid']) ->find();
			unset($user['id']);
			$msg = array_merge($msg,$user);
			$users = Db::name('wex_wall_user')->where('wall_id',tb_config('wex_wall_sign',1)) ->count('id');
			$msg['join_users'] = $users;
			return ['code'=>1,'msg'=>'获取成功','data'=>$msg];
		}else{
			return ['code'=>0,'msg'=>'获取失败'];
		}
	}

	public function getJoinUsers()
	{
		$wall_id = tb_config('wex_wall_sign',1);
		// 取出已中奖用户
		$winers = Db::name('wex_wall_winner') ->field('GROUP_CONCAT(openid) as winner') ->where('wall_id',$wall_id) ->find();
		$winers = empty($winers['winner'])?'':$winers['winner'];

		$users = Db::name('wex_wall_user') ->field('id,openid,headimgurl as avatar,nickname as name') ->where('openid','NOT IN',$winers) ->where(['wall_id'=>$wall_id,'is_joiner'=>1]) ->select();
		// dump($users);
		if ($users) {
			$user_num = count($users);
			return ['code'=>1,'msg'=>'获取成功','data'=>$users,'nums'=>$user_num];
		}else{
			return ['code'=>0,'msg'=>'获取失败'];
		}
	}

	public function ajaxAddWiner($openid,$timestamp)
	{
		$data = [
			'wall_id' => tb_config('wex_wall_sign',1),
			'openid' => $openid,
			'add_time' => date('Y-m-d H:i:s'),
			'timestamp' => $timestamp
		];
		$res = Db::name('wex_wall_winner') ->insert($data);
		if ($res!==false) {
			return ['code'=>1,'msg'=>'新增成功'];
		} else {
			return ['code'=>0,'msg'=>'新增失败'];
		}		
	}	

	// ======================================================================================
	// 								微信功能
	// ======================================================================================
	public function valid()
    {
        $getMsg = input();
        if (isset($getMsg['echostr'])) {
	        if($this->checkSignature()){
	        	echo $getMsg['echostr'];
	        	exit;
	        }
        }else{
        	// $token_expires_in = session('');
        	// 根据微信的access_token有效时间来确定是否需要重新获取access_token,在access_token到期5分钟前重新获取
        	if (!cache('wex_access_token')) {
        		$this->get_access_token();
        	}
        	$this->responseMsg();
        }
    }

    // 验证微信
	private function checkSignature()
	{
		$data = input();
        $signature = $data["signature"];
        $timestamp = $data["timestamp"];
        $nonce = $data["nonce"];	
	        		
		$token = tb_config('wex_token',1);
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

	public function responseMsg()
    {
        $postStr = file_get_contents("php://input");  //获取post数据
        if (!empty($postStr)){  //判断获取到的数据是否为空
            libxml_disable_entity_loader(true);  //防止因libxml错误缓冲导致的安全问题
               $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);  //解析XML数据
                $reType = trim($postObj->MsgType);
        switch ($reType) {
          	case "text" :  //消息类型，text为普通文本类型
               	$resultStr = $this->handleText ( $postObj );
             	break;
          	case "image" :
                $resultStr = $this->handleImg ( $postObj );
             	break;
          	case "voice" :
                $resultStr = $this->handleVio ( $postObj );
             	break;  
          	case "location" :
             	$resultStr = $this->handleLoc ( $postObj );
             	break;                          
          	case "event" :
             	$resultStr = $this->handleEvent ( $postObj );
             	break;
         	default :
         		$resultStr = "其他消息类型或格式错误: " ;  //不符合上述类型或格式错误的提示
             	break;
        }
                echo $resultStr;
        }else {
            echo "未获取到数据";  //未获取到数据的提示
            exit;   //结束程序执行
        }
	}

     /**
    * 封装微信回复格式
     */
    function XmlTpl(){  //微信普通文本消息的XML格式
	    $textTpl = "<xml>
	                    <ToUserName><![CDATA[%s]]></ToUserName>
	                    <FromUserName><![CDATA[%s]]></FromUserName>
	                    <CreateTime>%s</CreateTime>
	                    <MsgType><![CDATA[%s]]></MsgType>
	                    <Content><![CDATA[%s]]></Content>
                    </xml>";
	    return $textTpl;               
    }

    function handleText($postObj){   //消息关键词处理函数
        $fromUsername = $postObj->FromUserName;  //获取开发者微信号
        $toUsername = $postObj->ToUserName;    //用户的OpenID
        $keyword = trim($postObj->Content);    //用户输入的信息

        $time = time();  //时间
        //签到
        if ($keyword==tb_config('wex_wall_key',1)) {
    	   	$this->join_lottery($postObj);
        	exit;
        }
        //发言上墙
        if (strpos($keyword,tb_config('wex_wall_msg_sign',1))===0) {
        	$this->send_msg($postObj);
        	exit;
        }
        // 其它发言原样回复
        // $textTpl =  $this->XmlTpl();   //消息格式。调用函数
        // if(!empty( $keyword ))
        // {
        //     $msgType = "text";
        //     // $contentStr = $this->ReText($fromUsername, $keyword);
        //     $contentStr = $keyword;
        //     $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//组装回复
        //     echo $resultStr;
        // }
    }
   	function handleImg($postObj){
        $fromUsername = $postObj->FromUserName; //同上
        $toUsername = $postObj->ToUserName;
        $keyword = $postObj->PicUrl;  //获取图片URL
        $time = time();
        $textTpl =  $this->XmlTpl();  //调用封装的回复XML格式
        if(!empty( $keyword ))
        {
            $msgType = "text";
            $apiurl = file_get_contents('http://api.weiphp.cn/face/?appkey=trialuser&picurl='.$keyword);  //API请求
            $apiurl = str_replace('',"",$apiurl);  //替换函数，详见上同
            $contentStr = $apiurl;
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }
    }
    function handleVio($postObj){
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = $postObj->Recognition;  //语音识别结果
        $time = time();
        $textTpl =  $this->XmlTpl();
        if(!empty( $keyword ))
        {
            $msgType = "text";
            $contentStr = $this->ReText($fromUsername, $keyword);
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }
    }
    function handleLoc($postObj){
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $lx = $postObj->Location_X;  
        $ly = $postObj->Location_Y;
        $wz = $postObj->Label;
        $time = time();
        $textTpl =  $this->XmlTpl();
            $msgType = "text";
            $contentStr = "位置精度:".$lx."位置纬度:".$ly."地理位置:".$wz;   //用户发送位置信息结果
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
  	}
  	/**
 	* 新用户关注回复
	*/
	public function handleEvent($object) {

		$contentStr = "";
		$sysid='';
		$openid='';
		switch ($object->Event) {  //消息类型
			case "subscribe" :   //用户关注事件类型
				$EventKey = $object->EventKey;
				$openid = $object->FromUserName;
				$sysid = ltrim($EventKey,'qrscene_');
				$contentStr = "欢迎关注！";
				break;  //继续执行下一片段
			case 'SCAN':
				$EventKey = $object->EventKey;
				$openid = $object->FromUserName;
				$sysid = $EventKey;
				$contentStr = "";
				break;
			case 'unsubscribe':		//取消关注
				session('user',null);
				break;
			default :  //最终执行，其他类型
				$contentStr = "自定义菜单或其他类型消息";
				break;
		}
		if (!empty($sysid) && !empty($openid)) {
			$is_exist = Db::name('user_spread') ->where(['openid'=>"$openid"]) ->find();
			if (!$is_exist) {
				$res = Db::name('user_spread') ->insert(['openid'=>"$openid",'p_spread'=>"$sysid"]);
			}
		}
		if ($contentStr!="") {
			$resultStr = $this->responseText ( $object, $contentStr );
		} else {
			$resultStr = "";
		}
		
		return $resultStr;
	}
	
	public function responseText($object, $content, $flag = 0) {
		$textTpl = "<xml>
	                    <ToUserName><![CDATA[%s]]></ToUserName>
	                    <FromUserName><![CDATA[%s]]></FromUserName>
	                    <CreateTime>%s</CreateTime>
	                    <MsgType><![CDATA[text]]></MsgType>
	                    <Content><![CDATA[%s]]></Content>
	                    <FuncFlag>%d</FuncFlag>
                    </xml>";  //关注事件的XML数据格式
		$resultStr = sprintf ( $textTpl, $object->FromUserName, $object->ToUserName, time (), $content, $flag );
		return $resultStr;
	}
	private function ReText($fromUsername,$keyword){  //关键词处理函数段
		$apiurl=file_get_contents('http://i.itpk.cn/api.php?question='.$keyword.'&api_key=47016aa198ca53a6e571d2f6a2c57212&api_secret=97cydvok24ve');
	    return $apiurl;
	}

	// 获取公众号的access_token,存入系统缓存之中
	private function get_access_token()
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".tb_config('wex_appid',1)."&secret=".tb_config('wex_secret',1);
		$back = httpRequest($url,'GET');
		$back = json_decode($back,true);
		cache('wex_access_token',$back['access_token'],$back['expires_in']);
	}

	private function join_lottery($postObj,$reply=1)
	{
		$fromUsername = $postObj->FromUserName;  //获取开发者微信号
        $toUsername = $postObj->ToUserName;    //用户的OpenID
        $keyword = trim($postObj->Content);    //用户输入的信息
        $time = time();  //时间
        $usrOpenID = $postObj->FromUserName;    //用户的OpenID
        $ACCESS_TOKEN = cache('wex_access_token');
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$ACCESS_TOKEN&openid=$usrOpenID&lang=zh_CN";
        $back = httpRequest($url,'GET');
		$back = json_decode($back,true);
		if (isset($back['errcode'])) {
			$this->get_access_token();
			$ACCESS_TOKEN = cache('wex_access_token');
	        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$ACCESS_TOKEN&openid=$usrOpenID&lang=zh_CN";
	        $back = httpRequest($url,'GET');
			$back = json_decode($back,true);
		}
		unset($back['remark'],$back['groupid'],$back['tagid_list']);
		$back['join_time'] = date('Y-m-d H:i:s');
		$back['wall_id'] = tb_config('wex_wall_sign',1);
		$back['is_joiner'] = $reply;
		$is_join = Db::name('wex_wall_user') ->where(['wall_id'=>$back['wall_id'],'openid'=>$back['openid']]) ->find();
		if (!$is_join) {
			$res = Db::name('wex_wall_user') ->insert($back);
			if ($res!==false) {
				// 签到成功
				$contentStr = tb_config('web_wall_sign_access',1);
			} else {
				// 签到失败
				$contentStr = tb_config('web_wall_sign_error',1);					
			}
			
		}else{
			if ($is_join['is_joiner']==0) {
				$ress = Db::name('wex_wall_user')  ->where(['id'=>$is_join['id']]) ->setField('is_joiner',1);
				if ($ress!==false) {
					// 签到成功
					$contentStr = tb_config('web_wall_sign_access',1);
				} else {
					// 签到失败
					$contentStr = tb_config('web_wall_sign_error',1);
				}
				
			}else{
				// 重复签到
				$contentStr = tb_config('web_wall_sign_again',1);
			}
		}
		if ($reply==1) {
			$textTpl =  $this->XmlTpl();   //消息格式。调用函数
	        $msgType = "text";
	        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//组装回复
	        echo $resultStr;
		}
	}

	private function send_msg($postObj)
	{
		$fromUsername = $postObj->FromUserName;  //获取开发者微信号
        $toUsername = $postObj->ToUserName;    //用户的OpenID
        $keyword = trim($postObj->Content);    //用户输入的信息
        $badword = tb_config('wex_wall_badwords',1);
        $badwords = explode(',', $badword);
        $badword1 = array_combine($badwords, array_fill(0, count($badwords), '**'));
        $str = strtr($keyword, $badword1);
        $time = time();  //时间
        $usrOpenID = $postObj->FromUserName;    //用户的OpenID
        $usrOpenID = json_decode(json_encode($usrOpenID),true);
        $wall_id = tb_config('wex_wall_sign',1);
        //如果用户没有签到，则先行签到
        $is_join = Db::name('wex_wall_user') ->where(['wall_id'=>$wall_id,'openid'=>$usrOpenID[0]]) ->find();
        if (!$is_join) {
        	$this->join_lottery($postObj,0);
        }
        $msg = str_replace(tb_config('wex_wall_msg_sign',1), '', $str);

        $data  =  [
        	'wall_id' => $wall_id,
        	'openid' => $usrOpenID[0],
        	'msg' => $msg,
        	'msg_time' => date('Y-m-d H:i:s')
        ];
        $res = Db::name('wex_wall_msg') ->insert($data);
        if ($res!==false) {
			// 签到成功
			$contentStr = tb_config('web_wall_msg_success',1);
		} else {
			// 签到失败
			$contentStr = tb_config('wex_wall_msg_error',1);
		}
        $textTpl =  $this->XmlTpl();   //消息格式。调用函数
        $msgType = "text";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//组装回复
        echo $resultStr;
	}

}



