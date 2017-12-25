<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 商城模块通用函数库
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
use think\Db;


// 获取猜你喜欢商品列表
// @param $limit 获取条数
function getLoveGoods($limit='10')
{
    $user_id = session('user.id');
    $goods = [];
    if (empty($user_id)) {      //如果用户未登录，则取商城端口销量最高的
        $goods = Db::name('shop_goods') ->order(['sales_sum'=>'DESC']) ->limit($limit) ->select();
    } else {
        // 取用户已购买过的商品
        $orders = Db::name('shop_order') ->field('GROUP_CONCAT(id) as orders') ->where('user_id',$user_id) ->cache(true) ->find();
        $order_goods = Db::name('shop_order_goods') ->field('GROUP_CONCAT(goods_id) as goods') ->where('order_id','IN',$orders['orders']) ->cache(true) ->find();
        // 取用户购物车中的商品
        $cart_goods = Db::name('shop_cart') ->field('GROUP_CONCAT(goods) as goods') ->where('user_id',$user_id) ->cache(true) ->find();
        // 取用户收藏的商品
        $favor_goods = Db::name('shop_goods_collect') ->field('GROUP_CONCAT(goods_id) as goods') ->where('user_id',$user_id) ->cache(true) ->find();
        // 拼接用户以上所有商品的ID
        $goods_ids = implode(',', [$order_goods['goods'],$cart_goods['goods'],$favor_goods['goods']]);
        $goods_ids = array_unique(explode(',', $goods_ids));
        // 获得用户上面所有商品的分类ID
        $cate_ids = Db::name('shop_goods') ->field('GROUP_CONCAT(cat_id) as cats') ->where('id','IN',$goods_ids) ->cache(true) ->find();
        $cate_ids = array_unique(explode(',', $cate_ids['cats']));
        $goods = Db::name('shop_goods') ->where('cat_id','IN',$cate_ids) ->order(['sales_sum'=>'DESC']) ->limit($limit) ->select();
    }
    return $goods;
}


// 用户增加经验
function geiv_exp($uid,$exp,$type='')
{
    // 添加用户的支付经验
    $exp = empty($exp) ? 1 : $exp;
    Db::name('users')->where('id',$uid)->setInc('experience',$exp);
    // 获取用户当前等级及当前经验值

    $user = Db::name('users') ->field('level,experience') ->where('id',$uid)->find();
    // 更新用户的等级信息
    $experience = $user['experience'];
    // 取得当前经验时用户应得的等级
    $lv = Db::name('user_level') ->where('points','<',$experience) ->order('id DESC') ->find();

    if ($lv>$user['level']) {       //如果应得等级高于用户当前等级，则更新用户等级，并发放奖励
        $res = Db::name('users')->where('id',$uid) ->setField('level',$lv['id']);
        if($res!==false){
            // 向用户发放升级奖励积分
            Db::name('users') ->where('id',$uid) ->setField('pay_points',$lv['lv_give_points']);

        }
    }
}


/**
 * 写入交易消息
 * @param int $order_id
 * @param string $title
 * @param string $message
 */
function sendOrderMessage($order_id,$title,$message){
    //获取用户ID
    $user_id = getTableValue('shop_order','id='.$order_id,'user_id');
    $savaData['user_id'] = $user_id;
    $savaData['order_id'] = intval($order_id);
    $savaData['title'] = htmlspecialchars($title);
    $savaData['message'] = htmlspecialchars($message);
    //插入操作
    $insert = Db::name('shop_order_message')->insert($savaData);
    if( $insert === false ){
        return false;
    }
    return true;
}


/**
 * 实例化促销api
 * @param $model
 */
function promotion($model = ''){
    $promotionApi  = new \app\shop\api\Promotion($model);
    return $promotionApi;
}


/**
 * 实例化订单api
 */
function order(){
    $orderApi = new \app\shop\api\Order();
    return $orderApi;
}


/**
 * 添加记录到订单日志表
 * @param int $order_id 订单ID
 * @param int $action_user 操作管理员ID
 * @param int $order_status 订单状态
 * @param int $action_note 操作备注
 * @param int $status_desc 状态描述
 * @return boolean
 */
function insert_order_action($order_id,$action_user,$order_status,$action_note,$status_desc){
    // 设置保存信息
    $actionData['order_id'] = $order_id;
    $actionData['action_user'] = $action_user;
    $actionData['order_status'] = $order_status;
    $actionData['shipping_status'] = getTableValue('shop_order','id='.$order_id,'is_send');
    $actionData['pay_status'] = intval(getTableValue('shop_order','id='.$order_id,'is_pay'));
    $actionData['action_note'] = $action_note;
    $actionData['status_desc'] = $status_desc;
    $actionData['log_time'] = NOW_TIME;
    Db::name('shop_order_action')->insert($actionData);
}


/**
 * 获取商品锁文件路径名称
 * @param int $goods_id 商品ID
 * @return mixed
 */
function getGoodsLockFile($goods_id){
    $file  = DATA_PATH . '/lock/shop/goods/'.$goods_id.'.lock';
    return $file;
}


/**
 * 实例化价格计算类
 */
function compute(){
    $cou = 'app\shop\api\Compute';
    $compute = new $cou();
    return $compute;
}

/**
 * 获取抢购促销详细信息
 * @param int $id 抢购促销ID
 * @return array
 */
function getPanicInfo($id)
{
    //获取信息
    $panicInfo = Db::name('shop_promotion_panic')->where('panic_id', $id)->where('status',1)->find();
    if( empty($panicInfo) ){
        return [];
    }
    //获取商品信息
    $prom_id = getPromID(1,$panicInfo['panic_id']);
    $panicGoods = Db::name('shop_promotion_goods')->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->find();
    $panicInfo['goods'] = $panicGoods['goods_id'];
    //解析goods_spec
    if( $panicGoods['goods_spec'] !== '' ){
        $panicInfo['goods_spec'] = unserialize($panicGoods['goods_spec']);
    }else{
        $panicInfo['goods_spec'] = '';
    }
    $panicInfo['price'] = $panicGoods['price'];
    return $panicInfo;
}

/**
 * 获取团购促销详细信息
 * @param int $id 团购促销ID
 * @return array
 */
function getGroupInfo($id)
{

    $groupInfo = Db::name('shop_promotion_group')->where('group_id',$id)->where('status',1)->find();

    //获取商品信息
    $prom_id = getPromID(2,$groupInfo['group_id']);
    $groupGoods = Db::name('shop_promotion_goods')->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->find();
    $groupInfo['goods'] = $groupGoods['goods_id'];
    // 解析goods_spec
    if( $groupGoods['goods_spec'] !== '' ){
        $groupInfo['goods_spec'] = unserialize($groupGoods['goods_spec']);
    }else{
        $groupInfo['goods_spec'] = '';
    }
    $groupInfo['price'] = $groupGoods['price'];
    return $groupInfo;
}

/**
 * 获取优惠促销详细信息
 * @param int $id 优惠促销ID
 * @return array
 */
function getDiscountInfo($id,$goods = '')
{

    $discountInfo = Db::name('shop_promotion_discount')->where('discount_id',$id)->where('status',1)->find();
    //获取商品信息
    if( $goods == '' ){
        $where = '1=1';
    }else{
        $where = 'goods_id = '.$goods;
    }
    $prom_id = getPromID(3,$discountInfo['discount_id']);

    if( $goods == '' ){
        $discountGoods = Db::name('shop_promotion_goods')->where($where)->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->select();
        foreach ($discountGoods as $goods){
            $discountInfo['goods'][]['goods'] = $goods['goods_id'];
            $discountInfo['goods'][]['goods_spec'] = explode(',',$goods['goods_spec']);
            $discountInfo['goods'][]['price'] = $goods['price'];
        }
    }else{
        $discountGoods = Db::name('shop_promotion_goods')->where($where)->where('status',1)->where('prom_id',$prom_id)->field('goods_id,goods_spec,price')->find();
        $discountInfo['goods'] = $discountGoods['goods_id'];
        $discountInfo['goods_spec'] = explode(',',$discountGoods['goods_spec']);
        $discountInfo['price'] = $discountGoods['price'];
    }

    return $discountInfo;

}

/**
 * 获取订单促销详细信息
 * @param int $id 订单促销ID
 * @return array
 */
function getOrderInfo($id)
{
    $field = 'order_id,title,description,discount_type,money,expression,use_coupon,use_integral,use_shopping_coupon,user_group,buy_num,end_time,start_time,status';
    $orderInfo = Db::name('shop_promotion_order')->where('order_id',$id)->where('status',1)->field($field)->find();

    return $orderInfo;
}

/**
 * 检测商品是否已经收藏
 * @param int $goods_id 商品ID
 * @param int $user_id 用户ID
 * @return mixed
 */
function getGoodsCollectStatus($goods_id,$user_id = ''){
    if (empty($user_id) && !empty(session('user.id'))){
        $user_id = session('user.id');
    }
    $goods = intval($goods_id);
    $user_id = intval($user_id);
    //查询
    $goodsCollectId = Db::name('shop_goods_collect')->where(['goods_id'=>$goods,'user_id'=>$user_id,'status'=>1])->value('id');
    if( $goodsCollectId != '' ){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取订单状态名称
 * @param int $status 订单状态
 * @return array
 */
function getOrderStatusName($status)
{
    $config = config('order_status');

    return $config[$status];
}

/**
* 获取指定分类ID下的指定数量的文章
* @param int $cid  分类ID
* @param int $nums  输出文章数量
 * @return mixed
*/ 
function getArticleList($cid,$nums=10,$order = '')
{
    $now = NOW_TIME;
    $art_list = Db::name('web_content') ->where(['cid'=>$cid,'trash'=>0]) ->limit($nums) ->order($order) ->select();
    return $art_list;
}

/**
 * 添加用户分成信息
 * @param int $order_id
 * @return bool
 */
function saveUserDistribution($order_id){
    //获取订单信息
    $orderInfo  = Db::name('shop_order')->where('id',$order_id)->field('user_id,payable_price,change_mny')->find();
    //获取分成配置
    $disConfig = getUserDistributionConfig();
    if( !empty($disConfig) ){
        if( $disConfig ){
            //获取用户上级别
            $user_r = $orderInfo['user_id'];
            $userTree = Db::name('users_tree')->where('uid',$user_r)->value('tree_path_id');
            $userTree = explode(',',$userTree);
            $userTree = array_reverse($userTree);
            //设置保存数据
            $saveData['user_r'] = $user_r;
            $saveData['order_id'] = $order_id;
            $saveData['order_price'] = $orderInfo['payable_price']-$orderInfo['change_mny'];
            $saveData['add_time'] = date('Y-m-d H:i:s');
            $saveData['divided_into_time'] = date('Y-m-d H:i:s',strtotime('+2 days'));
            //查看分成级别
            foreach ($disConfig['level_proportion'] as $k=>$v){
                //$k=>级别，$v=>分成比例
                if( !empty($userTree[$k]) ){
                    $user_id = $userTree[$k];
                    $saveData['user_id'] = $user_id;
                    //计算分成金额
                    $saveData['divided_into_price'] = ($orderInfo['payable_price']-$orderInfo['change_mny'])*($v/100);
                    Db::name('user_distribution')->insert($saveData);
                }
            }
        }
    }
}



/**
 * 优惠券类加载函数
 * @return
 */
function coupon(){
    $coupon = new app\shop\api\Coupon();
    return $coupon;
}

/**
 * 获取总表ID
 * @param $type 促销类型：1-抢购、2-团购、3-优惠、4-订单
 * @param $id 促销表ID 具体促销表的ID
 * @return int
 */
function getPromID($type,$id)
{
    $prom_id = Db::name('shop_promotion')->where(['p_type'=>$type,'p_id'=>$id])->value('prom_id');
    if( $prom_id == '' ) {
        return lang('Promotion_is_not_exist');
    }
    return $prom_id;
}

/**
 * 获取促销表名称
 * @param int $prom_id 促销总表ID
 */
function getPromTable($prom_id){
    $prom_type = getTableValue('shop_promotion','id='.$prom_id,'p_type');
    switch ($prom_type){
        case 1:
            return 'shop_promotion_panic';
            break;
        case 2:
            return 'shop_promotion_group';
            break;
        case 3:
            return 'shop_promotion_discount';
            break;
        case 4:
            return 'shop_promotion_order';
            break;
    }
}


/**
 * 获取订单内的商品
 * @param int $id 订单ID
 * @return array
 */
function getOrderGoods($id){
    //获取
    $orderGoodsArr = \think\Db::name('shop_order_goods')->alias('og')->join(config('prefix')."shop_goods g",'og.goods_id = g.id')->field('g.id,g.title as goods_name,g.thumb')->where('order_id',$id)->select();
    $orderGoods = '';
    foreach ($orderGoodsArr as $goods){
        $orderGoods[$goods['id']] = $goods;
        $orderGoods[$goods['id']]['url'] = U('shop/Goods/goods',array('id'=>$goods['id']));
    }
    return $orderGoods;
}

/**
* 获取楼层广告列表
* @param int $cid  分类ID
* @param int $pid  广告位置ID
* @param int $nums  输出广告数量
 * @return mixed
*/ 
function getFloorAd($cid,$pid,$nums=1)
{
    $ad_list = Db::name('goods_categoryimg') ->where(['cid'=>$cid,'position'=>$pid,'is_show'=>1]) ->limit($nums) ->select();
    return $ad_list;
}

/**
 * 多个数组的笛卡尔积
 * @param array $data
 * @return mixed
 */
function combineDika($data) {  
    $cnt = count($data);  
    $result = [];
    foreach($data[0] as $item) {  
        $result[] = array($item);  
    }  
    for($i = 1; $i < $cnt; $i++) {  
        $result = combineArray($result,$data[$i]);  
    }  
    return $result;  
}  
   
/** 
 * 两个数组的笛卡尔积 
 * 
 * @param unknown_type $arr1 
 * @param unknown_type $arr2 
 */  
function combineArray($arr1,$arr2) {  
    $result = array();  
    foreach ($arr1 as $item1) {  
        foreach ($arr2 as $item2) {  
            $temp = $item1;  
            $temp[] = $item2;  
            $result[] = $temp;  
        }  
    }  
    return $result;  
}  

/**
 * 根据分类ID 获取整个分类分支
 * @param string $cat_id 分类id
 * @return string
 */
function getCateLine($cat_id)
{
	$cate[] = $cat_id;
	$pid = Db::name('goods_category') ->where('id',$cat_id) ->value('pid');
	if ($pid!=0) {
		$new_pid = getCateLine($pid);
		array_unshift($cate, $new_pid);
	}else{
		array_unshift($cate, $pid);
	}
	return implode('_', $cate);
}

/**
 * 创建订单号
 * @return string
 */
function createOrderSn()
{
    //当前加上8为随机数
    $date = date("ymdhis", NOW_TIME);
    $date = $date.rand(12345678,87654321);
    return $date;
}

/**
 * 创建流水号
 * @return string
 */
function createSerialSn()
{
    //当前加上8为随机数
    $date = date("ymdhis", NOW_TIME);
    $date = 'biz'.$date.rand(1234,8765);
    return $date;
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param int $goods_id  商品id
 * @param int $width     生成缩略图的宽度
 * @param int $height    生成缩略图的高度
 * @return mixed
 */
function goods_thum_images($goods_id,$width,$height){

     if(empty($goods_id))
         return '';
    //判断缩略图是否存在
    $path = "./uploads/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}";
  
    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg'; 
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg'; 
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif'; 
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png'; 
        
    $thumb = Db::name('shop_goods')->where("id",$goods_id)->value('thumb');
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
    
    //图片水印处理
    // $water = tpCache('water');
    // if($water['is_mark']==1){
    //     $imgresource = './'.$path.$goods_thumb_name;
    //     if($width>$water['mark_width'] && $height>$water['mark_height']){
    //         if($water['mark_type'] == 'img'){
    //             $image->open($imgresource)->water(".".$water['mark_img'],$water['sel'],$water['mark_degree'])->save($imgresource);
    //         }else{
    //             //检查字体文件是否存在
    //             if(file_exists('./zhjt.ttf')){
    //                 $image->open($imgresource)->text($water['mark_txt'],'./zhjt.ttf',20,'#000000',$water['sel'])->save($imgresource);
    //             }
    //         }
    //     }
    // }
    return '/'.$path.$goods_thumb_name;
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img,$goods_id,$width,$height){
    //判断缩略图是否存在
    $path = "./uploads/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";
    // 如果相册数据中有商品规格参数,则图片路径需要加上规格数据
    if (isset($sub_img['spec_key'])) {
        $goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$sub_img['spec_key']}_{$width}_{$height}";
    }
    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
    
    $original_img = '.'.$sub_img['image_url']; //相对路径
    if(!file_exists($original_img)) return '';
    
    // $image = new \Think\Image();
    // $image->open($original_img);
    $image = \think\Image::open($original_img);
    
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    // 生成缩略图
    if(!is_dir($path)){
        mkdir($path);
        chmod($path,0777);
    }
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    return '/'.$path.$goods_thumb_name;
}


/**
 * 通过spec_key 获取spec_name
 * @param $spec_key
 * @return string
 */
function getSpecNameFromKey($spec_key)
{
    $keyArr = explode('_',$spec_key);
    // 通过key查找name
    $nameArr = Db::name('shop_spec_item')->where('id','in',$keyArr)->column('item');
    $name = implode(' ',$nameArr);
    return $name;
}


/**
 * 获取属性名称
 * @param $id
 * @return string
 */
function getFeatureGroupName($id)
{
    if( empty($id) ){
        return '';
    }
    if( empty(cache('attrName:'.$id)) )
    {
        $name = getTableValue('shop_attribute','id='.$id,'attr_name');
        cache('attrName:'.$id,$name);
    }else{
        $name = cache('attrName:'.$id);
    }
    return $name;
}


/**
 * 筛选URL
 * @param string $fileType 筛选类型
 * @param string|array $param url参数
 * @param string $remove 需要去除的url参数
 * @return string
 */
function filterUrl($fileType,$param = '',$remove = '')
{
    if ( empty($GLOBALS[$fileType.'UrlArray']) ){
        $urlArray[0] = request()->url();
    }else{
        $urlArray = $GLOBALS[$fileType.'UrlArray'];
    }
    $lastUrl = empty($urlArray[1]) ? '' : $urlArray[1];
    if( empty($param) ){
        return $urlArray[0].$lastUrl;
    }
    if( $fileType == 'screen' ){
        $screenParam = $GLOBALS['screen'];
        foreach ($param as $key=>$item){
            $screenParam[$key] = trim($item);
        }
        array_filter($screenParam);
        $screen = [];
        foreach ($screenParam as $key=>$item){
            $screen[] .= $key.'_'.trim($item);
        }
        $screen = implode('%_',$screen);
        $nowUrl = 'screen='.$screen;
    }elseif ( $fileType == 'sort' ){
        $sort = empty($GLOBALS['sort']) ? '' : $GLOBALS['sort'];
        foreach ($param as $key=>$item){
            $sort = $key.'_'.trim($item);
        }
        $nowUrl = 'sort='.$sort;
    }elseif ( $fileType == 'brand' ){
        $nowUrl = 'brand='.$param;
    }else{
        return $urlArray[0].$lastUrl;
    }

    if( strpos($urlArray[0],'?') ){
        if( substr($urlArray[0],-1) != '?' ){
            $urlArray[0] = substr($urlArray[0],-1) == '&' ? $urlArray[0] : $urlArray[0].'&';
        }
    }else{
        $urlArray[0] = $urlArray[0].'?';
    }
    $url  = $urlArray[0].$nowUrl;
    if( !empty($lastUrl) ){
        $url .= '&'.$lastUrl;
    }
    return $url;
}

/**
 * 去除筛选参数
 * @param $fileType
 * @param string $remove
 * @return mixed
 */
function removeFilterUrl($fileType,$remove = '')
{
    if ( empty($GLOBALS[$fileType.'UrlArray']) ){
        $urlArray[0] = request()->url();
    }else{
        $urlArray = $GLOBALS[$fileType.'UrlArray'];
    }
    $lastUrl = empty($urlArray[1]) ? '' : $urlArray[1];
    if( empty($remove) ){// 去除全部
        return $urlArray[0].$lastUrl;
    }
    $nowUrl = '';
    if( $fileType == 'screen' ){
        if( !empty($remove) ){
            if( !empty($GLOBALS[$fileType]) ){
                $paramArray = $GLOBALS[$fileType];
                unset($paramArray[$remove]);
                // 拼接
                $param = [];
                foreach ($paramArray as $key=>$item){
                    $param[] = $key.'_'.$item;
                }
                $nowUrl = empty($param) ? '' : 'screen='.implode('%_',$param);
            }
        }
    }
    if( strpos($urlArray[0],'?') ){
        if( substr($urlArray[0],-1) != '?' ){
            $urlArray[0] = substr($urlArray[0],-1) == '&' ? $urlArray[0] : $urlArray[0].'&';
        }
    }else{
        $urlArray[0] = $urlArray[0].'?';
    }
    $url  = $urlArray[0].$nowUrl;
    if( !empty($lastUrl) ){
        $url .= '&'.$lastUrl;
    }
    return $url;
}


/**
 * 获取上层等级名称
 * @param $cat_id
 * @return string
 */
function getCateName($cat_id)
{
    $cateName = Db::name('goods_category') ->where('id','IN',$cat_id) ->column('name','id');
    foreach ($cateName as $key => $value) {
        $cateName[$key] = '<div class="cate_box">'.$value.'</div>';
    }
    $cateNames = implode('', $cateName);
    return $cateNames;
}



/**
 * 保存搜索日志
 * @param $key
 * @return void
 */
function saveSearchLog($key)
{

    $data = [
        'key' => $key,
        'time' => datetime_format(),
        'ip' => get_client_ip(),
        'session_id' => session_id(),
    ];
    if( session('user.id') ){
        $data['user_id'] = session('user.id');
    }
    Db::name('shop_search_log')->insert($data);
}


/**
 * 获取分销分成信息
 * @return array
 */
function getUserDistributionConfig(){
    $config = Db::name('user_distribution_config')->order('id desc')->find();
    if( !empty($config) ){
        $config['level_proportion'] = json_decode($config['level_proportion']);
    }
    return $config;
}

