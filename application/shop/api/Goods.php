<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商品管理接口
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\api;

use think\Cache;
use think\Db;
use think\Log;
use think\Exception;

class Goods
{

    /**
     * 获取商品分类详细信息
     * @param $cate_id
     * @return array
     */
    public function getCateInfo($cate_id)
    {
        if( empty(cache('category:'.$cate_id )) )
        {
            // 获取分类信息
            $cate_info = Db::name('goods_category')->where('id',$cate_id)->find();
            cache('category:'.$cate_id,$cate_info,'','shop-category');
        }
        else{
            $cate_info = cache('category:'.$cate_id);
        }
        return $cate_info;
    }

    /**
     * 获取下级分类
     * @param $cate_id
     * @return array
     */
    public function getCateChildList($cate_id)
    {
        if( empty( cache('category_list:'.$cate_id) ) )
        {
            // 获取子级
            $childList = Db::name('goods_category')->where('pid',$cate_id)->select();
            cache('category_list:'.$cate_id,$childList,'','shop-category');
        }else{
            $childList = cache('category_list:'.$cate_id);
        }
        return $childList;
    }
    

    /**
     * 获取规格商品库存
     * @param int $goods_id
     * @param int $spec_id
     * @return int
     */
    public static function getStock($goods_id,$spec_id = '')
    {
        if( !empty($spec_id) )
        {
            //获取库存
            $goods_stock = getTableValue('shop_spec_price','id='.$spec_id,'store_count');
            return intval($goods_stock);
        }
        //获取商品库存
        $goods_stock = getTableValue('shop_goods','id='.$goods_id,'stock');
        return intval($goods_stock);

    }


    /**
     * 减少库存
     */
    function reduce_stock($order){
        //获取流水内的所有商品
        $order_goods_arr = Db::name('shop_order_goods')->where('order_id in('.$order.')')->field('id,goods_id,spec_key,order_id,goods_num')->select();
        foreach ($order_goods_arr as $item){
            //修改库存
            //使用文件锁，锁定商品，防止高并发下库存错误
            $lock_file = getGoodsLockFile($item['order_id']);
            $fp = fopen($lock_file,"w+");
            if( flock($fp , LOCK_EX) ){
                if( !empty($item['spec_key']) ){
                    $stock = Db::name('shop_spec_price')->where(['goods_id'=>$item['goods_id'],'key_sign'=>$item['spec_key']]) ->value('store_count');
                    if ($stock<$item['goods_num']) {
                        throw new Exception("库存不足", 1);

                    }
                    Db::name('shop_spec_price')->where(['goods_id'=>$item['goods_id'],'key_sign'=>$item['spec_key']])->setDec('store_count',$item['goods_num']);
                }else{
                    $stock = Db::name('shop_goods')->where(['id'=>$item['goods_id']]) ->value('stock');
                    if ($stock<$item['goods_num']) {
                        throw new Exception("库存不足", 1);

                        // return ['code'=>0,'msg'=>'库存不足'];
                    }
                    Db::name('shop_goods')->where('id',$item['goods_id'])->setDec('stock',$item['goods_num']);
                }
                flock($fp , LOCK_UN);
            }
            fclose($fp);
        }
    }


    /**
     * 获取商品信息,不包含商品介绍内容。
     * @param int $goods 商品ID
     * @param string $field 字段
     * @return array
     */
    public function goodsInfo($goods,$constraint = false)
    {
        if( empty(cache('goodsInfo:'.$goods)) || $constraint == true ){
            $goodsInfo = Db::name('shop_goods')->where('id',$goods)->find();
            if( empty($goods) ){
                return null;
            }
            cache('goodsInfo:'.$goods,$goodsInfo,'','shop-goods');
        }else{
            $goodsInfo = cache('goodsInfo:'.$goods);
        }
        return $goodsInfo;
    }


    /**
     * 更新商品缓存信息
     * @param $goodsInfo
     * @return mixed
     */
    public function updateGoodsCache($goodsInfo)
    {
        cache('goodsInfo:'.$goodsInfo['id'],$goodsInfo,'','shop-goods');
        return true;
    }


    /**
     * 获取商品及规格详情
     * @param $goods
     * @param $spec_sign
     * @return string
     */
    public function getContent($goods,$spec_sign = '')
    {
        $content = '';
        if( !empty($spec_sign) ){
            // 获取规格详情
            $content = Db::name('shop_spec_content')->where('spec_key',$spec_sign)->value('content');
        }
        if( empty($content) ){
            // 获取商品内容
            $content = Db::name('shop_goods_content')->where('id',$goods)->value('content');
        }
        return $content;
    }



    /**
     * 获取商品及规格SEO数据
     * @param $goods
     * @param $spec_sign
     * @return string
     */
    public function getSeo($goods,$spec_sign = '')
    {
        $content = '';
        if( !empty($spec_sign) ){
            // 获取规格详情
            $content = Db::name('shop_spec_content')->where('spec_key',$spec_sign)->value('content');
        }
        if( empty($content) ){
            // 获取商品内容
            $content = Db::name('shop_goods_content')->where('id',$goods)->field('seo_title,seo_keyword,seo_description')->find();
        }
        return $content;
    }



    /**
     * 获取商品总金额，包含规格，商品数量
     * @param $goods
     * @param $spec_id
     * @return array
     */
    public function totalPrice($goods,$spec_id = '',$goods_num = 1)
    {
        // 获取商品价格
        $goods_shop_price = $this->goodsInfo($goods,'shop_price');
        $goods_shop_price = $goods_shop_price['shop_price'];
        if (empty($spec_id)) {
            $goods_total_price = $goods_shop_price*$goods_num;//商品总金额
        } else {
            //取规格价格
            $spec_price = Db::name('shop_spec_price')->where('id', $spec_id)->value('price');
            $goods_total_price = $spec_price * $goods_num;//商品总金额
        }
        return $goods_total_price;
    }

    /**
     * 检测商品是否失效
     * @param $goods
     * @param $spec
     * @return boolean
     */
    public function invalid($goods,$spec = '')
    {
        // 检测商品是否已经下架
        $status = Db::name('shop_goods')->where('id',$goods)->value('status');
        if( empty($status) || $status == 0 ){
            return false;
        }
        // 检测商品库存
        $stock = $this->getStock($goods,$spec);
        if( empty($stock) || $stock == false ){
            return false;
        }
        return true;
    }


    /**
     * 商品分类上层树结构
     * @param $id
     * @return array
     */
    public function categoryTopTree($id)
    {
        if( empty(cache('categoryTopTree:'.$id)) )
        {
            // 获取当前分类信息
            $categoryInfo = $this->categoryInfo($id);
            $tree = $this->dgTopCategory($categoryInfo,'',$categoryInfo['pid']);
            $tree = array_filter($tree);
            $tree = array_reverse($tree);
            cache('categoryTopTree:'.$id,$tree,'','shop-category');
        }else{
            $tree = cache('categoryTopTree:'.$id);
        }
        return $tree;
    }


    /**
     * 递归获取上层分类树
     * @param $cateInfo
     * @param $cateInfo
     * @return array
     */
    public function dgTopCategory($cateInfo,$tree = [],$pid)
    {
        if ( empty($tree) ) {
            $tree = [];
            $tree[] = $cateInfo;
        }
        // 获取上层
        $parentCategory = $this->categoryInfo($pid);
        $tree[] = $parentCategory;
        if( $parentCategory['pid'] > 0 )
        {
            return $this->dgTopCategory($parentCategory,$tree,$parentCategory['pid']);
        }else{
            return $tree;
        }
    }



    /**
     * 获取商品分类
     * @param $pid
     * @return array
     */
    public function categoryList($pid = '',$level = '',$condition = '',$sort = '')
    {
        if( empty($pid) ) {
            $pid = 0;
        }
        $where = '';
        if( !empty($level) ){
            $where = '`level` = '.$level;
        }
        // 获取当前
        if( empty(cache('category_list:'.$pid)) ){
            $list = Db::name('goods_category')->where($where)->where('is_show',1)->where('trash',0)->where($condition)->order($sort)->order('sort asc')->where('pid',(int)$pid)->select();
            if ( isset($pid) && $pid >= 0 ){
                cache('category_list:'.$pid,$list,'','shop-category');
            }
        }else{
            $list = cache('category_list:'.$pid);
        }
        // 查找
        return $list;
    }

    /**
     * 商品分类详情
     * @param $cate_id
     * @return array
     */
    public function categoryInfo($cate_id)
    {
        if( empty(cache('category:'.$cate_id)) ){
            $info = Db::name('goods_category')->find($cate_id);
            cache('category:'.$cate_id,$info,'','shop-category');
        }else{
            $info = cache('category:'.$cate_id);
        }
        return $info;
    }



    /**
     * 创建货号
     * @return mixed
     */
    public function createGoodsSn()
    {
        $code = rand(10000,99999).date('YmdHis').rand(10000,99999);
        $code = get_char($code);
        return $code;
    }



    /**
     * 插入商品
     * @param $data
     * @return int
     */
    public function insert($data){

        // 插入数据
        $content = '';
        if( !empty($data['content']) ){
            $content = $data['content'];
            unset($data['content']);
        }
        if( empty($data['goods_sn']) ){
            $data['goods_sn'] = $this->createGoodsSn();
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        $getId = Db::name('shop_goods')->insertGetId($data);
        // 插入商品详情表
        $insertContent = Db::name('shop_goods_content')->insert(['goods'=>$getId,'content'=>$content]);
        return $getId;
    }


    /**
     * 插入商品数据
     * @param $data
     * @return mixed
     */
    public function insertImg($data)
    {
        foreach ($data as $img){
            $getId = Db::name('shop_goods_images')->insert($img);
        }
    }


    /**
     * 插入商品属性
     * @param $data
     * @return mixed
     */
    public function insertAttr($data,$goods_id)
    {
        foreach ($data as $item)
        {
            $item['goods_id'] = $goods_id;
            $getId = Db::name('shop_goods_attr')->insert($item);
        }

    }


    /**
     * 插入商品规格价格数据
     * @param $data
     * @return mixed
     */
    public function insertSpecPrice($data,$goods_id)
    {
        foreach ($data as $item) {
            $item['goods_id'] = $goods_id;
            $getId = Db::name('shop_spec_price')->insert($item);
        }

    }


    /**
     * 推荐商品
     * @param int $id
     * @return mixed
     */
    public function doRecommend($id)
    {
        $upData = [
            'is_comm' => 1,
        ];
        try{
            Db::name('shop_goods')->where('id',$id)->update($upData);
        }catch (\ErrorException $e)
        {
            Log::error(json_encode($e));
            return false;
        }
        // 更新商品缓存
        $goods = $this->goodsInfo($id);
        $goods['is_comm'] = 1;
        cache('goodsInfo:'.$id,$goods,'','shop-goods');
        return true;
    }


    /**
     * 取消推荐商品
     * @param int $id
     * @return mixed
     */
    public function cancelRecommend($id)
    {
        $upData = [
            'is_comm' => 0,
        ];
        try{
            Db::name('shop_goods')->where('id',$id)->update($upData);
        }catch (\ErrorException $e)
        {
            Log::error(json_encode($e));
            return false;
        }
        // 更新商品缓存
        $goods = $this->goodsInfo($id);
        $goods['is_comm'] = 0;
        cache('goodsInfo:'.$id,$goods,'','shop-goods');
        return true;
    }


    /**
     * 修改商品状态
     * @param int $goods_id
     * @return bool
     */
    public function updateGoodsStatus($goods_id,$status)
    {
        $upData = [
            'status' => $status,
            'update_time' => date('Y-m-d H:i:s'),
        ];
        try{
            $sold_out = Db::name('shop_goods')->where('id',$goods_id)->update($upData);
        }catch (\ErrorException $e){
            // 记录日志
            return false;
        }
        $goodsInfo = $this->goodsInfo($goods_id);
        $goodsInfo['stauts'] = $status;
        $goodsInfo['update_time'] = $upData['update_time'];
        $this->updateGoodsCache($goodsInfo);
        return true;
    }

    /**
     * 商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
     * @param int $goods_id  商品id
     * @param int $width 生成缩略图的宽度
     * @param int $height 生成缩略图的高度
     * @reutnr string
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

        return '/'.$path.$goods_thumb_name;
    }


    /**
     * 保存店铺规格
     * @param $data
     * @return mixed
     */
    public function saveSpecItem($data)
    {
        try {
            $save = Db::name('shop_spec_item')->insertGetId($data);
        } catch (\ErrorException $e)
        {
            Log::error(json_encode($e));
            return false;
        }
        return $save;
    }


    /**
     * 获取商品品牌信息
     * @param $id
     * @return mixed
     */
    public function getBrand($id,$updateCache = false)
    {
        if( $updateCache === true ){
            $brand = Db::name('shop_brand')->where('id',$id)->find();
            cache('brand:'.$id,$brand,'','shop-brand');
            return $brand;
        }
        // 获取名称
        if( empty(cache('brand:'.$id)) ){
            $brand = Db::name('shop_brand')->where('id',$id)->find();
            cache('brand:'.$id,$brand,'','shop-brand');
            return $brand;
        }else{
            return cache('brand:'.$id);
        }
    }


    /**
     * 获取属性信息
     * @param $id
     * @return mixed
     */
    public function getAttr($id)
    {
        // 获取名称
        if( empty(cache('attribute:'.$id)) ){
            $name = Db::name('shop_attribute')->where('id',$id)->find();
            cache('attribute:'.$id,$name,'','shop-attribute');
            return $name;
        }else{
            return cache('attribute:'.$id);
        }
    }


    /**
     * 获取规格信息
     * @param $id
     * @return mixed
     */
    public function getSpec($id)
    {
        // 获取名称
        if( empty(cache('spec:'.$id)) ){
            $name = Db::name('shop_spec')->where('id',$id)->find();
            cache('spec:'.$id,$name,'','shop-spec');
            return $name;
        }else{
            return cache('spec:'.$id);
        }
    }



    /**
     * 获取规格项
     * @param $id
     * @return string
     */
    public function getSpecItem($id)
    {
        // 获取名称
        if( empty(cache('spec_item:'.$id)) ){
            $name = Db::name('shop_spec_item')->where('id',$id)->find();
            cache('spec_item:'.$id,$name,'','shop-spec');
            return $name;
        }else{
            return cache('spec_item:'.$id);
        }
    }


    /**
     * 多个数组的笛卡尔积
     * @return mixed
     */
    function combineDika($data) {
        $cnt = count($data);
        $result = array();
        foreach($data[0] as $item) {
            $result[] = array($item);
        }
        for($i = 1; $i < $cnt; $i++) {
            $result = $this->combineArray($result,$data[$i]);
        }
        return $result;
    }

    /**
     * 两个数组的笛卡尔积
     * @return mixed
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
     * 获取分类的品牌分组id
     * @param int $id 分类id
     * @return mixed
     */
    public function getBrandGroup($id)
    {
        // 获取当前分类信息
        $info = $this->getCateInfo($id);
        if( empty($info['brand_group']) || $info['pid'] == 0 )
        {
            if( $info['pid'] == 0 )
            {
                return $info['brand_group'];
            }else{
                return $this->getBrandGroup($info['pid']);
            }
        }else{
            return $info['brand_group'];
        }
    }


    /**
     * 获取分类的属性分组id
     * @param int $id 分类id
     * @return mixed
     */
    public function getAttrGroup($id)
    {
        // 获取当前分类信息
        $info = $this->categoryInfo($id);
        if( empty($info['attr_group']) || $info['pid'] == 0 )
        {
            if( $info['pid'] == 0 )
            {
                return $info['attr_group'];
            }else{
                return $this->getAttrGroup($info['pid']);
            }
        }else{
            return $info['attr_group'];
        }
    }


    /**
     * 获取顶级规格
     * @return mixed
     */
    public function selectTopSpec()
    {
        $data = Db::name('shop_spec')->where('pid',0)->select();
        return $data;
    }


    /**
     * 取当前分类下的规格
     * @param $type
     * @return mixed
     */
    public function typeSpecList($type)
    {
        // 获取当前分类信息
        $info = $this->categoryInfo($type);
        $list = Db::name('shop_spec')->where('cate_id',$type)->select();
        if( empty($list) ){
            return $this->typeSpecList($info['pid']);
        }
        return $list;
    }


    /**
     * 获取当前分类下的品牌
     * @param $cate
     * @return array
     */
    public function getBrandList($cate)
    {
        // 当前当前分类下的品牌
        $brandList = Db::name('shop_brand')->where('FIND_IN_SET('.$cate.',cate)')->select();
        if( empty($brandList) ){
            // 获取当前分类父级id
            $cateInfo = $this->getCateInfo($cate);
            if( $cateInfo['pid'] == 0 ){
                return [];
            }
            return $this->getBrandList($cateInfo['pid']);
        }
        array_push($brandList,['id'=>0,'name'=>'其他']);
        return $brandList;
    }


    /**
     * 获取当前分类下的属性
     * @param $cate
     * @return array
     */
    public function getAttrList($cate)
    {
        if( empty(cache('attributeList:'.$cate)) ){
            $attrGroupId = $this->getAttrGroup($cate);
            // 获取分组
            $attrGroup = Db::name('shop_feature_group')->where('id',$attrGroupId)->value('item');
            $attrList = Db::name('shop_attribute')->where('id','in',$attrGroup)->select();
            cache('attributeList:'.$cate,$attrList,'','shop-attribute');
        }else{
            $attrList = cache('attributeList:'.$cate);
        }
        return $attrList;
    }


    /**
     * 获取商品评论数量
     * @param $goods_id
     * @return mixed
     */
    public function goodsCommentNum($goods_id)
    {
        if( empty(cache('goodsCommentNum:'.$goods_id)) ){
            $num = Db::name('shop_goods_comment')->where('goods_id',$goods_id)->count();
            cache('goodsCommentNum:'.$goods_id,$num,'','shop-goodsComment');
        }else{
            $num = cache('goodsCommentNum:'.$goods_id);
        }
        if( empty($num) ){
            $num = 0;
        }
        return $num;
    }


    /**
     * 获取商品分类
     * @param $pid
     * @return mixed
     */
    public function category($pid)
    {
        // 获取商品分类
        if( empty(cache('categoryList:'.$pid)) ){
            $goodsCategory = Db::name('goods_category')->where('pid',$pid)->select();
            cache('categoryList:'.$pid,$goodsCategory,'','shop-category');
        }else{
            $goodsCategory = cache('categoryList:'.$pid);
        }
        return $goodsCategory;
    }


    /**
     * 获取商品链接
     * @param $id
     * @return string
     */
    public function goodsUrl($id)
    {
        $url = getTableValue('shop_goods','id='.$id,'url');
        if( empty($url) ){
            $url = rurl('shop/goods/goodsinfo',['id'=>$id]);
        }
        return $url;
    }


    /**
     * 获取商品分类链接
     * @param $id
     * @return string
     */
    public function categoryUrl($id)
    {
        $url = getTableValue('goods_category','id='.$id,'url');
        if( empty($url) ){
            $url = rurl('shop/goods/goodslist',['id'=>$id]);
        }
        return $url;
    }


    /**
     * 更新所有商品分类地址
     * @return mixed
     */
    public function updateAllCategoryUrl()
    {
        $list = Db::name('category')->select();
        foreach ($list as $key=>$item){
            Db::name('category')->where('id',$item['id'])->update(['url'=>rurl()]);
        }
    }


    /**
     * 更新商品分类的链接
     * @param $categoryId
     * @param $urlType
     *  [1=>'更新权重为以域名为主,短链为辅(如果短链不存在,使用Route)']
     *  [2=>'更新权重为以短链为主(如果短链不存在,使用Route)'],
     *  [3=>'更新权重为以Route为主的URL']
     * @return mixed
     */
    public function updateCategoryUrl($categoryId,$urlType = 1,$needDomain = false)
    {
        if( empty($categoryId) ){
            $list = Db::name('goods_category')->select();
        }else{
            $list = Db::name('goods_category')->where('id','in',$categoryId)->select();
        }
        $urlType = $urlType ?: 1;
        Db::startTrans();
        try{
            foreach ($list as $key=>$cate){
                $realUrl = 'shop/goods/goodslist?id='.$cate['id'];
                if( $urlType == 2 ){
                    if ( !empty($cate['url_logo']) ){
                        $newUrl = $cate['url_logo'];
                    }else{
                        $newUrl = rurl($realUrl);
                    }
                }else{
                    $isBindDomain = Db::name('domain_bind')->where(['bind_type'=>1,'bind_id'=>$cate['id'],'status'=>1])->find();
                    if( !empty($isBindDomain) ){
                        $newUrl = $isBindDomain['domain'];
                        $usedDomain = true;
                    }elseif ( !empty($cate['url_logo']) ){
                        $newUrl = $cate['url_logo'];
                    }else{
                        $newUrl = rurl($realUrl);
                    }
                }

                if( $needDomain == true && empty($usedDomain) ){
                    $newUrl = request()->domain().$newUrl;
                }
                $url = $newUrl;
                if( empty($usedDomain) ){
                    if( strpos($newUrl,'/') !== 0 ){
                        $url = '/'.$newUrl;
                    }else{
                        $newUrl = substr($newUrl,1);
                    }
                }
                Db::name('goods_category')->where('id',$cate['id'])->update(['url'=>$url]);
                if( $newUrl !== $realUrl ){
                    api('sys', 'Domain', 'diyUrlUpdate', [$newUrl, $realUrl]);
                }
                // 查询是否存在导航
                $nav = Db::name('sys_nav')->where('link_type',2)->where('link_param',$cate['id'])->find();
                if( !empty($nav) ){
                    if( strpos($newUrl,'/') !== 0 ){
                        $newUrl = '/'.$newUrl;
                    }
                    Db::name('sys_nav')->where('link_type',2)->where('link_param',$cate['id'])->update(['link'=>$newUrl.$nav['link_extra_param']]);
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }


    /**
     * 更新商品URL
     * @param $goods
     * @param $urlType
     * @param $needDomain
     * @return mixed
     */
    public function updateGoodsUrl($goods,$urlType = 1,$needDomain = false)
    {
        if( empty($goods) ){
            $goodsId = Db::name('shop_goods')->field('id,cat_id')->select();
        }else{
            $goodsId = Db::name('shop_goods')->where('id','in',$goods)->field('id,cat_id')->select();
        }
        Db::startTrans();
        try{
            foreach ($goodsId as $goodsInfo)
            {
                $realUrl = 'shop/goods/goodsinfo?id='.$goodsInfo['id'];
                $cateInfo = $this->categoryInfo($goodsInfo['cat_id']);

                $url = $cateInfo['url'].'/'.$goodsInfo['id'];
                Db::name('shop_goods')->where('id',$goodsInfo['id'])->update(['url'=>$url]);
                if( strpos($url,'/') === 0  ){
                    $url = substr($url,1);
                }
                api('sys', 'Domain', 'diyUrlUpdate', [$url, $realUrl]);

                // 查询是否存在导航
                $nav = Db::name('sys_nav')->where('link_type',1)->where('link_param',$goodsInfo['id'])->find();
                if( !empty($nav) ){
                    if( strpos($url,'/') !== 0 ){
                        $url = '/'.$url;
                    }
                    Db::name('sys_nav')->where('link_type',1)->where('link_param',$goodsInfo['id'])->update(['link'=>$url.$nav['link_extra_param']]);
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }



    /**
     * 通过参数搜索搜索商品列表
     * @param $search
     * @return mixed
     */
    public function searchGoods($search)
    {
        $where = '`name` like "%'.$search.'%" or `title` like "%'.$search.'%" or `goods_sn` like "%'.$search.'%" or `keywords` like "%'.$search.'%" or `id` = '.$search;

        $data = Db::name('shop_goods')->where($where)->where('trash',0)->select();
        return $data;
    }


    /**
     * 通过参数搜索商品分类列表
     * @param $search
     * @return mixed
     */
    public function searchGoodsCategory($search)
    {
        $where = '`name` like "%'.$search.'%" or `mobile_name` like "%'.$search.'%" or `id` = '.$search;

        $data = Db::name('goods_category')->where($where)->select();
        return $data;
    }



    /**
     * 获取商品图片
     * @param $goodsId
     * @param string $spec 规格组合
     * @return array
     */
    public function goodsImageList($goodsId,$spec = '')
    {
        if( empty(cache('goods_images_list:'.$goodsId.':'.$spec)) ){
            $goods_images_list = [];
            if( !empty($spec) ){
                $goods_images_list = Db::name('shop_spec_image')->where(['goods_id' => $goodsId, 'spec_key' => $spec])->select();
            }
            if( empty($spec) || empty($goods_images_list) ){
                $goods_images_list = Db::name('shop_goods_images')->where('goods_id', $goodsId)->select();
            }
            cache('goods_images_list:'.$goodsId.':'.$spec,$goods_images_list,'','shop-goodsImagesList');
        }else{
            $goods_images_list = cache('goods_images_list:'.$goodsId.':'.$spec);
        }

        return $goods_images_list;
    }


    /**
     * 商品评分集合
     * @param $goods
     * @return mixed
     */
    public function commentFraction($goods)
    {
        if( empty(cache('goodsCommentFraction:'.$goods)) ){
            $base_where = ["id"=>$goods,"is_show"=>1];
            $data = [];
            $data["num_total"] = db("shop_goods_comment","",false) ->where($base_where) ->count();
            $data["num_imgs"] = db("shop_goods_comment","",false) ->where($base_where) ->where("img","<>","") ->count();
            $data["num_good"] = db("shop_goods_comment","",false) ->where($base_where) ->where("goods_rank",">=","4") ->count();
            $data["num_mid"] = db("shop_goods_comment","",false) ->where($base_where) ->where("goods_rank","=","3") ->count();
            $data["num_bad"] = db("shop_goods_comment","",false) ->where($base_where) ->where("goods_rank","<=","2") ->count();
            $data["score_avg"] = db("shop_goods_comment","",false) ->where($base_where) ->avg("goods_rank");
            $data["score_percent"] = round(($data["score_avg"]/5)*100,2);
            cache('goodsCommentFraction:'.$goods,$data,'','shop-goodsComment');
        }else{
            $data = cache('goodsCommentFraction:'.$goods);
        }
        return $data;
    }


}