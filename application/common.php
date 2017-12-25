<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
use think\Db;
// 调用业务公共函数库
require("function.php");



/*发送消息
* @param $tp int 接收人类型 0:直接接收方式(手机号，邮箱地址等)，1:用户
* @param $to string 接收人
* @param $scene string 场景标志
* @param $data array 附加数据
* @param $country string 国家代码  仅TBMALL项目需要
*/
function sendMsg($tp,$to='',$scene='',$data=[],$country='82')
{
    $msgModel = Db::name('admin_sms_model') ->where(['scene'=>$scene,'status'=>1]) ->order(['sort'=>'ASC','id'=>'DESC']) ->find();
    if (empty($msgModel)) {
        // 没有当前场景或场景没有设置消息模板
        return ['code'=>'10001','msg'=>lang('comm_msg_model_null')];
    } else {
        $types = explode(',', $msgModel['types']);
        $plugs = count($types);
        $succ_num = 0;
        foreach ($types as $key => $plug) {
            //实例化消息插件
            $Msg = new plugins\message\Message($plug);
            $content = $msgModel['content'];
            if (!empty($data)) {
                foreach ($data as $field => $val) {
                    // 依次替换掉消息模板中所有的变量
                    $content = str_replace("{".$field."}", $val, $content);
                }
            }
            if ($tp==0) {
                $result = $Msg->sendMsg($to,$msgModel['md_title'],$content,$country);
            } else {
                $result = $Msg->sendUserMsg($to,$msgModel['md_title'],$content,$country);
            }
            if ($result['code']==1) {
                $succ_num ++;
            }
        }
        if ($succ_num==$plugs) {
            // 发送成功
            return ['code'=>1,'msg'=>lang('comm_msg_send_success')];
        }else{
            // 部分或所有方式发送失败，请重试
            return ['code'=>-1,'msg'=>lang('comm_msg_send_fail')];
        }
    }
}

/**
 * 隐藏手机、电话号码中间部分，以*代替
 * @param int $phone 电话号码
 * @return mixed
 **/
function hidetel($phone){
    $IsWhat = preg_match('/(0[0-9]{2,3}[\-]?[2-9][0-9]{6,7}[\-]?[0-9]?)/i',$phone); //固定电话
    if($IsWhat == 1){
        return preg_replace('/(0[0-9]{2,3}[\-]?[2-9])[0-9]{3,4}([0-9]{3}[\-]?[0-9]?)/i','$1****$2',$phone);
    }else{
        return  preg_replace('/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$phone);
    }
}


/**
 * 隐藏邮箱地址中间部分，以*代替
 * @param string $mail 邮箱地址
 * @return mixed
 **/
function hidemail($mail){
    $left = substr($mail, 0 ,2);
    $mail_arr = explode('@', $mail);
    $right = end($mail_arr);
    return $left.'****@'.ltrim($right,'@');
}




/**
 * 密码加密
 * @param $passwd
 * @return string
 */
function encrypt_pwd($passwd)
{

    if(!empty($passwd)){
        //encrypt
        $pass = md5(md5($passwd . 'biz'));
        //return encrypt password
        return $pass;
    }else{
        return false;
    }
}

/**
 * 校验日期格式是否正确
 *
 * @param string $date 日期
 * @param string $formats 需要检验的格式数组
 * @return boolean
 */
function checkDateIsValid($date, $formats = array("Y-m-d", "Y/m/d")) {
    $unixTime = strtotime($date);
    if (!$unixTime) { //strtotime转换不对，日期格式显然不对。
        return false;
    }
//校验日期的有效性，只要满足其中一个格式就OK
    foreach ($formats as $format) {
        if (date($format, $unixTime) == $date) {
            return true;
        }
    }
    return false;
}

/**
 * 地址函数-U
 * @param string $url
 * @param string|array $params
 * @param string $suffix
 * @param boolean $domain
 * @return string
 */
function U($url,$params = [],$suffix='',$domain=false){
    return url($url,$params,$suffix,$domain);
}


/**
 * 创建用户token
 * @return string
 */
function create_token()
{
    //set token str
    $tokenStr = microtime().rand(1000,9999);
    //encrty token
    $encrtyToken = md5(md5($tokenStr.'jungo'));
    //Intercept encrty token
    $token = substr($encrtyToken,1,30);
    //return token
    return $token;
}


/**
 * base加密函数
 * @param $json
 * @param $type
 * @return  string
 */
function base_encrypt($arr,$type = 'user')
{
    //check $json is not json data
    $json = json_encode($arr,true);
    //Mosaic encrypt string
    $encryptStr = $json.'_-'.$type.'_-'.rand(1000,9999);
    //encrypt
    $encryptStr = base64_encode($encryptStr);
    //return encryptstr
    return $encryptStr;
}


/**
 * base解密函数
 * @param $encrypt string
 * @return arr
 */
function base_decrypt($encryptStr)
{
    //decrypt
    $decryptStr = base64_decode($encryptStr);
    //explode str
    $decryptArr = explode('_-', $decryptStr);

    $decryptArrRe['data'] = $decryptArr[0];
    $decryptArrRe['type'] = $decryptArr[1];

    return $decryptArrRe;
}


/**
 *===============================================================================================
 * login_status
 * @param $userId  null
 * @return string
 *===============================================================================================
 */
function login_status( $userId )
{
    //check $userId isnot int
    if( is_int($userId) )
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $fd = $redis->get('bizim:online:'.$userId.':fd');
        if( !empty($fd) )
        {
            return '在线';
        }else
        {
            return '离线';
        }
    }else{
        return 'Pleace entry int data';
    }
}


/**
 *===============================================================================================
 * error_msg
 * @param int $error_code
 * @param string $swoole defult:false
 * @return string or array
 *===============================================================================================
 */
 function error_msg($error_code,$swoole = false)
 {
     //include ../app/error.php
     $error = include APP_PATH .'error.php';
     $error_msg = $error[$error_code];
     //check isnot swoole
     if( $swoole == true ){
         //set errorData
         $errorData['cmd'] = 'error';
         $errorData['error_code'] = $error_code;
         $errorData['error_data'] = $error_msg;
         return $errorData;
     }else{
         return $error_msg;
     }
 }

/**
 * 获取指定路径下的图片的Base64格式数据
 * @param string  $image_file     图片路径
 * @return string
 */
 function base64EncodeImage ($image_file) {
  $base64_image = '';
  $image_info = getimagesize($image_file);
  $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
  $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
  return $base64_image;
}


/**
 * 取指定条件下的数据
 * @param string  $table     表格名
 * @param array $where       条件数组
 * @param array $order       排序数组
 * @param string $nums       欲获取的记录数量
 * @return string
 */
function getTableRows($table='',$where=[],$order=[],$nums)
{
    $value = Db::name($table) ->where($where) ->order($order) ->limit($nums) ->select();
    return $value;
}

/**
 * 取指定条件下表格中的字段值
 * @param string  $table     表格名
 * @param array $where       条件数组
 * @param string $field     欲取值的字段名
 * @return string
 */
function getTableValue($table='',$where=[],$field = '')
{
    if( empty($field) ){
        $value = Db::name($table)->where($where)->find();
    }else{
        $value = Db::name($table) ->where($where) ->value($field);
    }
    return $value;
}

/**
 * 获取地区名称
 * @param int $id 地区ID
 * @return string
 */
function getAddressName($id){
    intval($id);
    //查询
    $address = Db::name('region')->where('id',$id)->find();
    return $address['name'];
}

/**
 * 计算剩余天数
 * @param datetime 日期时间
 * @param is_time 是否计算时间
 * @return int
 */
function leftDate($date,$is_time = false){
    if( $is_time == false ){
        if( strlen($date) > 10 ){
            $date = substr($date,0,10);
        }
        $nowData = date('Y-m-d', NOW_TIME);
        $diff_date = date_diff(date_create($date),date_create($nowData));
        return $diff_date->format('%a');
    }
}


/**
 * 获取当前语言ID
 * @return int
 */
function getLang()
{
    $lang = cookie('think_var');
    $lang_id = Db::name('lang')->where('lang',$lang)->value('id');
    return $lang_id;
}

/**
 * ID加密函数
 * 正式使用时请按照自己的加密方式进行加密解密
 * @param int $id
 * @return string
 */
function ID_KEY($id)
{
    //添加开头字符串
    $start_str  = '_hash_';
    //添加随机数
    $rand_str1 = rand(100000,999999);
    $rand_str2 = rand(1000000,9999999);
    //base64加密
    $lkey = $rand_str1.$id.$rand_str2;
    $lkey_base = base64_encode($lkey.'biz');
    $key = $start_str.$lkey_base;
    return $key;
}

/**
 * ID释放函数
 * @param int $id_key
 * @return string
 */
function ID_RELEASE($id_key)
{
    //获取到$lkey
    $lkey_base = substr($id_key,6,strlen($id_key));
    //去除尾部字符
    $lkey = base64_decode($lkey_base);
    $lkey = substr($lkey,0,strlen($lkey)-3);//尾部字符长度
    $lkey = substr($lkey,6,strlen($lkey));//去掉开始随机数
    $lenK =  strlen($lkey)-7;
    $lkey = substr($lkey,0,$lenK);//去掉尾部随机数
    return $lkey;
}

/**
 * 价格格式化
 * @param int float decimal $price 需要格式化的价格
 * @param int $is_currency 是否需要加上币种符号，默认不需要
 * @return string
 */
function priceFormat($price,$is_currency = 0)
{
    if( intval($price) > 0 || intval($price) === 0){
        $currency = '';
        if( $is_currency == 1 ) {
            $currency = tb_config('web_currency',1,getLang());
        }
        return $currency.number_format($price,2,'.',',');
    }
    return $price;
}


/**
 * 获取支付类型名称
 * @param string $pay_code 插件code
 */
function getPayName($pay_code){

    if( $pay_code ){
        //获取配置文件
        $config = include EXTEND_PATH . 'plugins'.DS.'payment'.DS.$pay_code.DS.'config.php';
        return $config['name'];
    }
    return '';
}

/**
 * 数组转XML
 * @param array $array 要转化为xml的数组
 * @param string $root XML的根元素
 * @return xml
 */
function arrayToXml($array,$root)
{
    $xml = "<{$root}>";
    foreach ($array as $key=>$val)
    {
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</{$root}>";
    return $xml;
}

/**
 * XML转数组
 * @param xml $xml 要转化为数组的XML数据
 * @return array
 */
function xmlToArray($xml)
{
    $array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array;
}

/**
 * 选项字符串转数组
 * @param string  $string     要转换的选项字符串
 * @param string $exp         分割各选项的标记字符
 * @param string $opt_exp     分割选项键值的标记字符
 * @return array
 */
function optStr2Arr($string='',$exp=',',$opt_exp=':')
{
    $opt_arr = explode($exp, $string);
    $option = [];
    foreach ($opt_arr as $k => $opt) {
        $opts = explode($opt_exp, $opt);
        if( empty($opts[1]) ){
            $option[$k] = $opts[0];
        }else{
            $option[$opts[0]] = $opts[1];
        }
    }
    return $option;
}

/**
 * 构建层级（树状）数组
 * @param array  $array          要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $lv_name        标记菜单层级的字段名
 * @param string $pid_name       父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree($array, $lv_name='level', $pid_name = 'pid', $child_key_name = 'children')
{
    $maxLv = get_max_lever($array);
    // dump($array);
    for ($i=$maxLv; $i >= 1; $i--) {
        // dump($i);
        foreach ($array as $key => $temp) {
            if (is_array($temp)) {

                if ( !empty($temp[$lv_name]) && $temp[$lv_name]==$i) {
                    if ($i>1) {
                        $array[$temp[$pid_name]][$child_key_name][] = $temp;
                        unset($array[$key]);
                    }
                }
            }
        }
    }
    return $array;
}

/**
 * 构建层级（树状）数组
 * @param array  $array          要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $lv_name        标记菜单层级的字段名
 * @param string $pid_name       父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree2($array, $lv_name='level', $pid_name = 'pid', $child_key_name = 'children')
{
	$maxLv = get_max_lever($array);
    // dump($array);
	for ($i=$maxLv; $i >= 1; $i--) {
        // dump($i);
		foreach ($array as $key => $temp) {
            if (!empty($temp[$lv_name])) {
    			if ($temp[$lv_name]==$i) {
    				if ($i>1) {
    					$array[$temp[$pid_name]][$child_key_name][] = $temp;
    					unset($array[$key]);
    				}
    			}
            }
		}
	}
	return $array;
}

// 取数组中最深的层次
function get_max_lever($array,$lv='level')
{
	$level = 0;
	foreach ($array as $key => $value) {
		if ($value[$lv]>$level) {
			$level = $value[$lv];
		}
	}
	return $level;
}

/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}

/**
 * 将树状数组排列成单列数组
 * @param array  $array          要进行处理的树状数组
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function tree2list($array,$child_key_name='children'){
    $arr_n = [];
	foreach ($array as $key => $arr) {
		if (!empty($arr[$child_key_name])) {
			$arr_new = $arr[$child_key_name];
			unset($arr[$child_key_name]);
			$arr_n[] = $arr;
			$arr_s = tree2list($arr_new);
			$arr_n = array_merge($arr_n,$arr_s);
		}else{
			$arr_n[] = $arr;
		}
	}
	return $arr_n;
}

/**
 * getDir()去文件夹列表，getFile()去对应文件夹下面的文件列表,二者的区别在于判断有没有“.”后缀的文件，其他都一样
 */
 
//获取文件目录列表,该方法返回数组
function getDir($dir) {
    $dirArray[]=NULL;
    if (false != ($handle = opendir ( $dir ))) {
        $i=0;
        while ( false !== ($file = readdir ( $handle )) ) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && !strpos($file,".")) {
                $dirArray[$i]=$file;
                $i++;
            }
        }
        //关闭句柄
        closedir ( $handle );
    }
    return $dirArray;
}

 
//获取文件列表
function getFile($dir,$suffix=true) {
    $fileArray[]=NULL;
    if (false != ($handle = opendir ( $dir ))) {
        $i=0;
        while ( false !== ($file = readdir ( $handle )) ) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." &&strpos($file,".")) {
            	if (!$suffix) {
            		$file = explode('.', $file);
            		$file = $file[0];
            	}
            	
                $fileArray[$i]=$file;
                if($i==100){
                    break;
                }
                $i++;
            }
        }
        //关闭句柄
        closedir ( $handle );
    }
    return $fileArray;
}
 
//调用方法getDir("./dir")……

/**
 * 作用：遍历输出指定目录所有文件名
 * @param string $dir  需要遍历的目录
 * @return array  返回文件名数组
 */
//print_r(listDir('./')); //遍历当前目录
function listDirs($dir='./')
{
    $dir .= substr($dir, -1) == '/' ? '' : '/';
    $dirInfo = array();
    foreach (glob($dir.'*') as $v)
    {
        $dirInfo[] = $v; 
        if(is_dir($v))
        {
            $dirInfo = array_merge($dirInfo, listDirs($v));
        }
    }
    krsort($dirInfo); //反转数组排序，将创建日期由升序转为降序排列
    return $dirInfo;
}

function getDirNum($dir='./')
{
	$dir .= substr($dir, -1) == '/' ? '' : '/';
	$dir_arr	=	scandir($dir);
	$Number	=	count($dir_arr)-2;
	return $Number;
}

/**
 * 作用：将字节数转化为KB，MB，GB等单位
 * @param string $size  需要转换的字节数
 * @return array  返回转换后的单位
 */
function get_real_size($size) 
{
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if($size < $kb) 
    {
       return $size." B";
    }else if($size < $mb) {
       return round($size/$kb,2)." KB";
    }else if($size < $gb) {
       return round($size/$mb,2)." MB";
    }else if($size < $tb) {
       return round($size/$gb,2)." GB";
    }else {
       return round($size/$tb,2)." TB";
    }

}


//获得视频文件的缩略图
function getVideoCover($file,$time = 1,$name ) {
    if(empty($time))$time = '1';//默认截取第一秒第一帧
    //exec("ffmpeg -i ".$file." -y -f mjpeg -ss ".$time." -t 0.001 -s 320x240 ".$name."",$out,$status);
    $str = "ffmpeg -i ".$file." -y -f mjpeg -ss 3 -t ".$time." -s 320x240 ".$name;
    $result = system($str);
    return $result;
}

//获得视频文件的总长度时间和创建时间
function getTime($file){
    $vtime = exec("ffmpeg -i ".$file." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");//总长度
    $ctime = date("Y-m-d H:i:s",filectime($file));//创建时间
    return array('vtime'=>$vtime,
        'ctime'=>$ctime
    );
}


function sendSMS($mobile='',$content='')
{
    //俊网官方短信接口
    $sms_url = tb_config('sms_api',1);
    $sms_user = tb_config('sms_key',1);
    $sms_pwd = tb_config('sms_secret',1);
    $soap = new SoapClient($sms_url.'?wsdl');
    $param = array_to_object(['username'=>$sms_user,'password'=>$sms_pwd,'mobileNum'=>$mobile,'content'=>$content,'productNum'=>'1001']);
    $result = $soap->__soapcall('SubmitSms', array($param));
    $result = $result->SubmitSmsResult;
    if(strlen($result)==18){
        return true;
    }else{
        return $result;     //返回平台报错信息
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
function sendMail($to,$subject='',$content=''){    
    vendor('phpmailer.PHPMailerAutoload'); ////require_once vendor/phpmailer/PHPMailerAutoload.php';
    $mail = new PHPMailer;
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
    $mail->Host = tb_config('smtp_server',1);
    //端口 - likely to be 25, 465 or 587 
    $mail->Port = tb_config('smtp_port',1);
    
    if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = tb_config('smtp_user',1);
    //密码
    $mail->Password = tb_config('smtp_pwd',1);
    //Set who the message is to be sent from
    $mail->setFrom(tb_config('smtp_user',1));
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
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    $result = $mail->send();
    return $result;
}


/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || gettype($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
 
    return (object)$arr;
}


/**
 * transfer中转接口助手函数调用模块api方法
 * @param $module
 * @param $api
 * @param $action
 * @param $param
 */
use api\Transfer;
function api($module,$api,$action,$param=''){
    $transfer = Transfer::getInstance();
    return $transfer->api($module,$api,$action,$param);
}

/**
 * Transfer实例，助手函数
 * @return Transfer
 */
function T(){
    $transfer = Transfer::getInstance();
    return $transfer;
}


/**
 * 获取物流追踪信息
 * @param string $scode
 * @param string $ssn
 * @return array
 */
function kdTrail($scode,$ssc,$plugins='kuaidi100')
{
    //实例化物流插件
    $shippingE = new plugins\shipping\Shipping($plugins);
    $result = $shippingE->getkdTrail($scode,$ssc);
    return $result;
}


/**
 * 数组过滤
 * @param $arr
 * @param $keyArr
 * @return array
 */
function filter_array($arr,$keyArr)
{
    // 把$field转化为数组
    if( !empty($keyArr) )
    {
        if( !is_array($keyArr) ){
            $keyArr = explode(',',$keyArr);
            $keyArr = array_flip($keyArr);
        }
        return array_intersect_key($arr,$keyArr);
    }else{
        return $arr;
    }
}



/**
 * 十进制数转换成62进制
 *
 * @param integer $num
 * @return string
 */
function from10_to62($num) {
    $to = 62;
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ret = '';
    do {
        $ret = $dict[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

/**
 * 62进制数转换成十进制数
 *
 * @param string $num
 * @return string
 */
function from62_to10($num) {
    $from = 62;
    $num = strval($num);
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($num);
    $dec = 0;
    for($i = 0; $i < $len; $i++) {
        $pos = strpos($dict, $num[$i]);
        $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
    }
    return $dec;
}


/**
 * @desc  im:十进制数转换成三十六机制数
 * @param (int)$num 十进制数
 * @return string
 */
function from10_to36($num) {
    $num = intval($num);
    if ($num <= 0)
        return false;
    $charArr = array("0","1","2","3","4","5","6","7","8","9",'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $char = '';
    do {
        $key = ($num - 1) % 36;
        $char= $charArr[$key] . $char;
        $num = floor(($num - $key) / 36);
    } while ($num > 0);
    return $char;
}


/**
 * @desc  im:三十六进制数转换成十机制数
 * @param (string)$char 三十六进制数
 * @return string
 */
function from36_to10($char){
    $array=array("0","1","2","3","4","5","6","7","8","9","A", "B", "C", "D","E", "F", "G", "H", "I", "J", "K", "L","M", "N", "O","P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y","Z");
    $len=strlen($char);
    $sum = '';
    for($i=0;$i<$len;$i++){
        $index=array_search($char[$i],$array);
        $sum+=($index+1)*pow(36,$len-$i-1);
    }
    return $sum;
}

/**
 * 模板赋值函数
 * @param string|array $key
 * @param string|array $value
 * @return mixed
 */
function assign($key, $value = '')
{
    // 初始化模板
    $view = \think\View::instance();
    if( is_array($key) )
    {
        $view->assign($key);
    }else{
        $view->assign($key,$value);
    }
}


/**
 * 返回数据格式
 * @param $code
 * @param $msg
 * @param $data
 * @return mixed
 */
function msg($code,$msg,$data = [])
{
    if ( is_array($msg) ){
        $msgArray = array_keys($msg);
        $msgKey = $msgArray[0];
        $msg = $msg[$msgKey];
    }else{
        $msgKey = 'msg';
    }
    return ['code'=>$code,$msgKey=>$msg,'data'=>$data];
}


/**
 * 日期统一格式化
 * @param int $time
 * @param string $type
 * @return mixed
 */
function datetime_format($time = '',$type = '')
{
    if( empty($time) )
        $time = time();
//    $month = date('F',$time);
//    $date = substr($month,0,3).date('-d,Y',$time);
    $date = date('Y-m-d H:i:s',$time);
    return $date;
}

/**
 * 获取当前页面完整URL地址
 * @param bool $domain 是否携带域名返回地址
 * @return mixed
 */
function get_url($domain=true) {
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
    if ($domain) {
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }else{
        return $relate_url;
    }
}


/**
 * 后台获取前台地址
 * @param $url
 * @param $vars
 * @param $suffix
 * @param $domain
 * @return mixed
 */
function rurl($url = '', $vars = '', $suffix = true, $domain = false)
{
    $url = url($url, $vars, $suffix, $domain);
    $realUrl = array_filter(explode('/', $url));
    if (preg_match('/[a-zA-Z0-9_]*\.php/', $realUrl[1])) {
        $realUrl[1] = '';
    }
    $url = implode('/', $realUrl);
    return $url;
}


/**
 * 获取当前的货币
 * @return string
 */
function web_currency()
{
    $web_currency = tb_config('web_currency',1);
    if( empty($web_currency) ){
        return '¥';
    }
    return $web_currency;
}


/**
 * url过滤
 * @param $url
 * @return string
 */
function filterDiyUrl($url)
{
    $url = preg_replace('/[^\x{4e00}-\x{9fa5}A-Za-z0-9_\/)]/u','-',htmlspecialchars_decode(rtrim($url)));
    return $url;
}


/**
 * ipDump  生产环境下测试BUG使用,仅指定IP显示打印数据
 * @param string $ip 指定IP(公网IP)
 * @param mixed $data 需要打印的数据
 * @return mixed
 */
function ipDump($ip,$data)
{
    if( get_client_ip() == $ip ){
        dump($data);
    }
}

/**
 * ipDump  生产环境下测试BUG使用,仅指定IP显示打印数据
 * @param string $ip 指定IP(公网IP)
 * @param mixed $data 需要打印的数据
 * @return mixed
 */
function ipDie($ip,$data = '')
{
    if( get_client_ip() == $ip && !empty($data) ){
        dump($data);
    }
    die;
}



/**
 * consoleLog 将数据打印到浏览器console中,数组、对象将转化为JSON格式
 * @param mixed $data 需要打印的数据
 * @return mixed
 */
function consoleLog($data)
{
    if( is_array($data) || is_object($data) ){
        $string = '<script>console.log('.json_encode($data).');</script>';
    }else{
        $string = '<script>console.log("'.$data.'");</script>';
    }
    echo $string;
}