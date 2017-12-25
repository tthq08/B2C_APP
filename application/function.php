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


// 为方便系统核心升级，业务开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件
use think\Db;


/**
 * 获取消息发送插件列表
 * @return mixed
 */
function getMsgPlugins($getname=false)
{
    //取plugins 目录下的所有目录
    $plugins_dir = EXTEND_PATH.'plugins/message';
    $dirs = array_map('basename', glob($plugins_dir.'/*', GLOB_ONLYDIR));
    // dump($dirs);
    if ($dirs === false || !file_exists($plugins_dir)) {
        return false;
    }

    foreach ($dirs as $key => $plug) {
        $plug_dir = $plugins_dir.'/'.$plug;
        $conf_dir = $plug_dir.'/config.php';
        // dump($conf_dir);
        $config = include $conf_dir;
        // $config['icon'] = base64EncodeImage($plug_dir.'/'.$config['icon']);
        if ($getname) {
            $msg_list[$config['code']] = $config['name'];
        } else {
            $msg_list[$config['code']] = $config;
        }

    }
    return $msg_list;
}


/**
 * 取当前位置附近的数据，由近及远排序
 * @param string $table  查询记录的表
 * @param array $field  表中存放坐标的字段，格式：['lat'=>'pos_lat','lng'=>'pos_long']
 * @param array $pos  要查询数据的位置坐标，格式：['lat'=>'22.539013','lng'=>'113.942501']
 * @param int $limit  要查询的记录数
 * @return mixed
 */
function getNearByList($table,$field=[],$pos=[],$limit=1)
{
    $lat = $pos['lat'];
    $lng = $pos['lng'];
    $list = Db::query("select *,(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(".$lat."-".$field['lat'].")/360),2)+COS(3.1415926535898*".$lat."/180)* COS(".$field['lat']." * 3.1415926535898/180)*POW(SIN(3.1415926535898*(".$lng."-".$field['lng'].")/360),2))))*1000 as juli from `".$table."` order by juli asc limit ".$limit);
    return $list;
}

/**
* 计算两个坐标点之间的距离。返回距离单位为米
*
* @param int $lat1  坐标1纬度值
* @param int $lng1  坐标1经度值
* @param int $lat2  坐标2纬度值
* @param int $lng2  坐标2经度值
*/ 
function GetDistance($lat1, $lng1, $lat2, $lng2){ 
    define('PI',3.1415926535898);
    define('EARTH_RADIUS',6378.137);
    $radLat1 = $lat1 * (PI / 180);
    $radLat2 = $lat2 * (PI / 180);
   
    $a = $radLat1 - $radLat2; 
    $b = ($lng1 * (PI / 180)) - ($lng2 * (PI / 180)); 
   
    $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2))); 
    $s = $s * EARTH_RADIUS; 
    $s = round($s * 10000) / 10000; 
    return $s*1000; 
}

/**
 * 获取指定广告位ID下的指定数量的广告
 * @param int $pid  广告位ID
 * @param int $nums  输出广告数量
 * @return array
 */
function getAdList($pid,$nums=1,$where='')
{
    $now = NOW_TIME;
    $where = empty($where)?'id>0':$where;
    $ad_list = Db::name('admin_ad') ->where(['pid'=>$pid,'isdspy'=>1])->where($where) ->where("start_time<=$now AND end_time>=$now") ->limit($nums) ->select();
    return $ad_list;
}


/**
 * 获取指定广告位ID下的指定数量的广告
 * @param int $position  模板调用标识
 * @param int $nums  输出广告数量
 * @return array
 */
function tempAdList($position,$nums=1,$where='')
{
    $now = NOW_TIME;
    // 获取广告位模板标识
    $pidList = Db::name('admin_ad_position')->where('position',$position)->column('id');
    if( empty($pidList) )
    {
        return [];
    }
    $where = empty($where)?'id>0':$where;
    $ad_list = Db::name('admin_ad') ->where('isdspy',1) ->where('pid','in',$pidList)->where($where) ->where("start_time<=$now AND end_time>=$now") ->limit($nums) ->select();

    return $ad_list;
}


/**
 * 获取url 中的各个参数  类似于 pay_code=alipay&bank_code=ICBC-DEBIT
 * @param type $str
 * @return type
 */
function parse_url_param($str){
    $data = array();
    $parameter = explode('?',$str);
    $parameter = explode('&',end($parameter));
    foreach($parameter as $val){
        $tmp = explode('=',$val);
        $data[$tmp[0]] = $tmp[1];
    }
    return $data;
}

/**
 * 获取支付列表
 */
function getPayList()
{
    //取plugins 目录下的所有目录
    $plugins_dir = EXTEND_PATH.'plugins/payment';
    $dirs = array_map('basename', glob($plugins_dir.'/*', GLOB_ONLYDIR));
    // dump($dirs);
    if ($dirs === false || !file_exists($plugins_dir)) {
        return false;
    }

    $cilent = isMobile();
    if ($cilent) {  //手机端
        $cilent_tp = 1;
    } else {    //PC端
        $cilent_tp = 2;
    }

    foreach ($dirs as $key => $plug) {
        $plug_dir = $plugins_dir.'/'.$plug;
        $conf_dir = $plug_dir.'/config.php';
        // dump($conf_dir);
        $config = include $conf_dir;
        if (($config['scene']==$cilent_tp || $config['scene']==0) && $config['status']==1) {
            $config['icon'] = base64EncodeImage($plug_dir.'/'.$config['icon']);
            $pay_list[$config['code']] = $config;
        }
    }
    return $pay_list;    
}


/**
 * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
 * @return boolean
 */
function isMobile()
{
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;        
    }

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
        // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
    // 如果只支持wml并且不支持html那一定是移动设备
    // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
            return false;
 } 

/**
 * 检查数据表是否存在
 * @param string $table_name 附加表名
 * @return string
 */
function table_exist($table_name = '')
{
    return true == Db::query("SHOW TABLES LIKE '{$table_name}'");
}

/**
 * 获取指定标志的设置数据
 * @param string  $key          要获取的设置标记
 * @param int $is_value       是否只取value内容
 * @param int $lang          要取的语言版本
 * @return array|bool
 */
function tb_config($key='',$is_value=1,$lang=1)
{
    if( empty(cache('config:'.$key)) ) {
        $config = db('admin_config', '', false)->where(['name' => $key, 'lang' => $lang])->find();
        if (empty($config)) {
            $config = db('admin_config', '', false)->where(['name' => $key, 'lang' => 1])->find();
            if( empty($config) ){
                return null;
            }
        }
        cache('config:'.$key,$config,'system-config');
    }else{
        $config = cache('config:'.$key);
    }
    if ($config['type']=='array') {
        $config['value'] = optStr2Arr($config['value']);
    }
    if ($is_value==0) {
        return $config;
    } else {
        return $config['value'];
    }
}

/**
 * 获取用户密码加密
 * @param int  $uid     用户ID
 * @return array|null
 */
function encrypt($pass='')
{
    $result = md5($pass . config('salt'));
    return $result;
}

/**
 * 获取用户的角色信息
 * @param int  $uid     用户ID
 * @return array|null
 */
function getUserRole($uid)
{
    if ($uid==1) {
        $role_info = [
            'id' => 0,
            'title' => '超级管理员'
        ];
    } else {
        $role_id = Db::name('auth_group_access') ->where('uid',$uid) ->value('group_id');
        $role_info = Db::name('auth_group') ->find($role_id);
    }
    
    return $role_info;
}

/**
 * 记录系统日志
 * @param string  $msg     日志内容
 * @param int $status      日志类型,0:失败，1:成功
 * @return null
 */
function sys_log($msg,$status=1,$admin='')
{
	$module = request()->module();
	$controller = request()->controller();
	$action = request()->action();
    $admin = empty(session('admin_name'))?$admin:session('admin_name');
	$logData = [
		'module' => $module,
		'controller' => $controller,
		'action' => $action,
		'status' => $status,
		'msg' => $msg,
		'admin' => $admin,
		'log_time' => date('Y-m-d H:i:s')
	];
	Db::name('log') ->insert($logData);
}

/**
 * 获取客户端IP
 * @param int  $type     IP地址类型.0:ip地址原生字符，1:转换成10位数字型IP地址
 * @return string
 */
function get_client_ip($type = 0) { 
    $type       =  $type ? 1 : 0; 
    static $ip  =   NULL; 
    if ($ip !== NULL) return $ip[$type]; 
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']); 
        $pos    =   array_search('unknown',$arr); 
        if(false !== $pos) unset($arr[$pos]); 
        $ip     =   trim($arr[0]); 
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) { 
        $ip     =   $_SERVER['HTTP_CLIENT_IP']; 
    }elseif (isset($_SERVER['REMOTE_ADDR'])) { 
        $ip     =   $_SERVER['REMOTE_ADDR']; 
    } 
    // IP地址合法验证 
    $long = ip2long($ip); 
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0); 
    return $ip[$type]; 
}

/**
 * 获取指定模块下的目录树
 * @param string  $module  欲获取目录树的模块
 * @return array
 */
function getMenuTree($module='admin')
{
    $lang = cookie('think_var');
    $menu     = [];
    $auth_rule_list = Db::name('auth_rule')->where(['module'=>$module])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
    foreach ($auth_rule_list as $value) {
        //取出所有菜单在当前语言版本下的显示标题
        $menu_title = json_decode($value['title'],true);
        $value['title'] = $menu_title[$lang];
        $menu[$value['id']] = $value;
    }
    $menu = !empty($menu) ? array2tree($menu) : [];
    return $menu;
}

/**
 * 生成签名信息
 * $secretKey 产品私钥
 * $params 接口请求参数，不包括signature参数
 */
function WY_signature($secretKey,$params){
    ksort($params);
    $buff="";
    foreach($params as $key=>$value){
        $buff .=$key;
        $buff .=$value;
    }
    $buff .= $secretKey;
    return md5(mb_convert_encoding($buff, "utf8", "auto"));
}


/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}

/**
 * 商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param string $img_path  原始图片路径
 * @param string $width     生成缩略图的宽度
 * @param string $height    生成缩略图的高度
 * @return mixed
 **/
function common_thumb_img($img_path,$width,$height){

    if(empty($img_path))
        return '';
    $ext = '.'.pathinfo($img_path,PATHINFO_EXTENSION);
    $fileName = basename($img_path,$ext);
    // dump($fileName);die;
    //判断缩略图是否存在]
    $thumb_path = ROOT_PATH."/public/uploads/image/thumb/";


    if(!is_dir($thumb_path)){
        mkdir($thumb_path);
        chmod($thumb_path,0777);
    }

    $path = $thumb_path.$fileName."/";
    $goods_thumb_name ="img_thumb_{$fileName}_{$width}_{$height}";

    $tempPath = UPLOAD_PATH.'image/thumb/'.$fileName.'/';
    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return $tempPath.$goods_thumb_name.'.jpg';
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return $tempPath.$goods_thumb_name.'.jpeg';
    if(file_exists($path.$goods_thumb_name.'.gif'))  return $tempPath.$goods_thumb_name.'.gif';
    if(file_exists($path.$goods_thumb_name.'.png'))  return $tempPath.$goods_thumb_name.'.png';

    $thumb = $img_path;
    if(empty($thumb)) return '';

    $thumb = '.'.$thumb; // 相对路径
    if(!file_exists($thumb)) return '';

    $image = \think\Image::open($thumb);

    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();

    //生成缩略图
    if(!is_dir($path)){
        mkdir($path);
        chmod($path,0777);
    }

    $image->thumb($width, $height,2)->save($path.$goods_thumb_name); //按照原图的比例生成一个最大为$width*$height的缩略图并保存

    return $tempPath.$goods_thumb_name;
}