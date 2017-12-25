<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 项目内置标签库文件
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\common\taglib;

use app\transfer\home\Resource;
use think\template\TagLib;
use think\Db;

/**
 * Jun标签库解析类
 */
class Jun extends Taglib
{

    // 标签定义
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'tbiz'     => ['attr'=>'sql,key,item,result_name','close'=>1,'level'=>3], // Tbiz sql 万能标签
        'adv'        => ['attr'=>'limit,order,where,item','close'=>1],
        'list'      =>  ['attr'=>'model,table,condition,field,order,key,item','close'=>1],
        'detail'    => ['attr'=>'model,table,condition,field,id','close'=>0],
        'tbcss'       => ['attr'=>'rel,type,module,src','close'=>0],
        'tbjs'       => ['attr'=>'module,src','close'=>0],
        'tbimg'       => ['attr'=>'module,src','close'=>0],
        // 商品详情,分类列表
        'category' => ['attr' => 'id,field,key,item', 'close' => 1, 'expression' => true],
        // 商品推送
        'goods_rank' => ['attr' => 'cid,order,limit,key,item', 'close' => 1],
        // 评论分数获取
        'comment_fraction' => ['attr' => 'goods', 'close' => 0],
        // 文章列表
            'article_list' => ['attr' => 'cid,limit,key,item', 'close' => 1],
        // 广告列表
        'ad_list' => ['attr' => 'cid,limit,key,item', 'close' => 1],
        // 首页楼层广告列表
        'floor_ad_list' => ['attr' => 'cid,pid,limit,key,item', 'close' => 1],
        // 获取订单列表
        'order_list' => ['attr' => 'user,field,limit,status,order,key,item', 'close' => 1],
        // 获取订单列表
        'order_detail' => ['attr' => 'id,field,name', 'close' => 0],
        // 获取订单商品列表
        'order_goods_list' => ['attr' => 'id,field,order,key,item', 'close' => 1],
        // 获取购物车商品,user:用户ID或者sessionID，
        'chat_list' => ['attr' => 'user,field,selected,status,limit,key,item', 'close' => 1],
        // 获取用户地址
        'user_address' => ['attr' => 'user,field,key,item', 'close'=>1],
        // 获取商品主图
        'goods_thumb' => ['attr'=>'goods,spec', 'close'=>0],
        // 获取商品展示图片列表
        'goods_images' => ['attr' => 'goods,spec,limit,key,item', 'close'=>1],//goodsImageList
        // banner海报
        'banner' => ['attr' => 'position,sort,num,key,item', 'close' => 1],
        // 选择区域
        'region' => ['attr' => 'pid,key,item', 'close' => 1 ],
        // 尾部文章列表
        'footer_article' => ['attr' => 'key,item', 'close' => 1 ],
        // 获取门户子栏目列表
        'cate_list' => ['attr' => 'pid,key,item', 'close' => 1],
        // 获取自定义导航列表
        'nav_list' => ['attr' => 'key,item', 'close' => 1],
        // 获取分类列表
        'category_list' => ['attr' => 'pid,key,item', 'close' => 1],
        // 获取当前用户登录信息
        'user' => ['attr' => 'key','close' => 0 ],
        // 商品分类上层树结构
        'cate_tree' => ['attr' => 'id,key,item', 'close' => 1],
        // 推荐商品
        'recommend_goods' => ['attr' => 'cid,sort,condition,is_new,key,item', 'close' => 1],

        // 获取导航位
        'nav_position' => ['attr' => 'pid,position,sort,limit,condition,key,item','close' => 1],

        // 获取导航
        'nav' => ['attr' => 'position,sort,limit,condition,key,item','close' => 1],

        // 获取logo
        'logo' => ['attr'=>'','close'=>0],

        // 获取后台配置项
        'config' => ['attr'=>'name','close'=>0],


        'api' => ['attr'=>'action,param,name','close'=>0],


        'friendly_link' => ['attr'=>'limit,key,item','close'=>1],

        'recommend' => ['attr'=>'position,limit,sort,condition,key,item','close'=>1],


        'file_library' => ['attr'=>'','close'=>0],
    ];


    public function TagFile_library($tag,$content)
    {
        $uniqid = uniqid();
        $bootstrap = '<link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>
                <script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
               
                
                <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#'.$uniqid.'" onclick="yibu();">开始演示模态框</button>
                <!-- 模态框（Modal） -->
                <div class="modal fade" id="'.$uniqid.'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body content-'.$uniqid.'">
                            <iframe frameborder="no" id="iframe-136666" name="iframe-136666" scrolling="auto" allowtransparency="true" style="width: 100%; height: 100%;" src="'.url('sys/picture_library/index').'">
                            </iframe>  
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal -->
                </div>
                <script>
                function yibu(){
                    $.ajax({
                        type:"POST",
                        url:"",
                    });
                };
                </script>
                ';

        return $bootstrap;
    }


    /**
     * 获取友情链接
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagGoods_images($tag,$content)
    {
        if( strpos($tag['id'],'$') !== 0 ){
            $tag['id'] = '"'.$tag['id'].'"';
        }
        $tag['spec'] = empty($tag['spec']) ? '""' : $tag['spec'];
        if( strpos($tag['spec'],':') === 0 ){
            $tag['spec'] = substr($tag['spec'],1,strlen($tag['spec'])-1);
        } else if( strpos($tag['spec'],'$') !== 0  ){
            $tag['spec'] = '"'.$tag['spec'].'"';
        }
        $tag['limit'] = empty($tag['limit']) ? '100' : $tag['limit'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $tag['item'] = empty($tag['item']) ? 'nav' : $tag['item'];
        $str = '';
        $str .= '<?php ';
        $str .= '
                $goods_images = api("shop","Goods","GoodsImageList",['.$tag['id'].','.$tag['spec'].','.$tag['limit'].']);
            $i = 0;';

        $str .= 'foreach($goods_images as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';
        return $str;
    }


    /**
     * 获取友情链接
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagFriendly_link($tag,$content)
    {
        $tag['limit'] = empty($tag['limit']) ? '' : $tag['limit'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $tag['item'] = empty($tag['item']) ? 'nav' : $tag['item'];
        $str = '';
        $str .= '<?php ';
        $str .= '
                $links = api("sys","Comm","FriendlyLink",['.$tag['limit'].']);
            $i = 0;';

        $str .= 'foreach($links as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';
        return $str;
    }


    /**
     * API
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagApi($tag,$content)
    {
        $api = [];
        if( !empty($tag['action']) ){
            $action = explode('/',$tag['action']);
            if( count($action) == 1 ){
                $api['module'] = request()->module();
                $api['api'] = request()->controller();
                $api['action'] = $action[0];
            }elseif ( count($action) == 2 ){
                $api['module'] = request()->module();
                $api['api'] = $action[0];
                $api['action'] = $action[1];
            }else{
                $api['module'] = $action[0];
                $api['api'] = $action[1];
                $api['action'] = $action[2];
            }
        }else{
            $api['module'] = request()->module();
            $api['api'] = request()->controller();
            $api['action'] = request()->action();
        }
        $param = empty($tag['param']) ? '' : $tag['param'];
        $html = '<?php ';
        $html .= '$apiData = api("'.$api['module'].'","'.$api['api'].'","'.$api['action'].'",'.$param.');';
        if( empty($tag['name']) ){
            $html .= '
                    if( !is_array($apiData) ){
                        echo $apiData;
                    }else{
                        $api = $apiData;
                    }
            ';
        }else{
            $html .= '$'.$tag['name'].' = $apiData;';
        }
        $html .= '?>';
        return $html;
    }


    /**
     * 获取商品详情
     * @param $tag
     * @return mixed
     */
    public function TagGoods_content($tag)
    {

    }


    /**
     * 获取商品基本信息
     * @param $tag
     * @return mixed
     */
    public function TagGoods($tag)
    {

    }


    /**
     * 获取后台配置项
     * @param $tag
     * @return mixed
     */
    public function TagConfig($tag)
    {
        if( empty($tag['name']) ){
            return '';
        }
        return tb_config($tag['name'],1);
    }


    /**
     * 获取导航位
     * @param $tag
     * @return mixed
     */
    public function TagLogo($tag)
    {
        return tb_config('web_site_logo',1);
    }


    /**
     * 获取导航位
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagNav($tag,$content)
    {
        $tag['pid'] = empty($tag['pid']) ? "''" : $tag['pid'];
        $tag['position'] = empty($tag['position']) ? "''" : $tag['position'];
        $tag['sort'] = empty($tag['sort']) ? '""' : $tag['sort'];
        $tag['condition'] = empty($tag['condition']) ? '"`status` = 1"' : "(`status` = 1) and ".$tag['condition'];
        $tag['limit'] = empty($tag['limit']) ? 10 : $tag['limit'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $tag['item'] = empty($tag['item']) ? 'nav' : $tag['item'];
        $str = '';
        $str .= '<?php ';
        $str .= '
                $nav_list = api("sys","Nav","getNavList",['.$tag['pid'].','.$tag['position'].','.$tag['sort'].','.$tag['condition'].','.$tag['limit'].']);
            $i = 0;';

        $str .= 'foreach($nav_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
                
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 获取导航位
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagNav_position($tag,$content)
    {
        $tag['pid'] = empty($tag['pid']) ? 0 : $tag['pid'];
        $tag['position'] = empty($tag['position']) ? '' : $tag['position'];
        $tag['sort'] = empty($tag['sort']) ? '""' : $tag['sort'];
        $tag['condition'] = empty($tag['pid']) ? '""' : $tag['condition'];
        $tag['limit'] = empty($tag['limit']) ? 10 : $tag['limit'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $tag['item'] = empty($tag['item']) ? 'position' : $tag['item'];
        $str = '';
        $str .= '<?php ';
        $str .= '$position_list = api("sys","Nav","getPositions",["'.$tag['pid'].'","'.$tag['position'].'",'.$tag['sort'].','.$tag['condition'].','.$tag['limit'].']);
            $i = 0;';

        $str .= 'foreach($position_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 推荐商品
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagRecommend_goods($tag,$content)
    {
        $tag['cid'] = empty($tag['cid']) ? 0 : $tag['cid'];
        $tag['sort'] = empty($tag['sort']) ? '""' : $tag['sort'];
        $tag['condition'] = empty($tag['pid']) ? '""' : $tag['condition'];
        $tag['is_new'] = empty($tag['is_new']) ? '""' : $tag['is_new'];
        $tag['limit'] = empty($tag['limit']) ? 10 : $tag['limit'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$goods_list = api("shop","Recommend","recommendGoodsList",['.$tag['cid'].','.$tag['is_new'].','.$tag['sort'].','.$tag['condition'].','.$tag['limit'].']);
            $i = 0;';

        $str .= 'foreach($goods_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;

    }


    /**
     * 获取商品分类列表
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagCategory($tag, $content)
    {
        $tag['pid'] = empty($tag['pid']) ? 0 : $tag['pid'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$category_list = api("shop","Goods","categoryTopTree",['.$tag['pid'].']);
            $i = 0;';

        $str .= 'foreach($category_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
                $'.$tag['item'].'["brothers"] = api("shop","Goods","getCateChildList",[$'.$tag['item'].'["id"]]);
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 商品分类上层树结构
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagCate_tree($tag, $content)
    {
        $tag['id'] = empty($tag['id']) ? 0 : $tag['id'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$category_list = api("shop","Goods","categoryTopTree",['.$tag['id'].']);
            $i = 0;';

        $str .= 'foreach($category_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 获取商品分类列表
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagCategory_list($tag, $content)
    {
        $tag['pid'] = empty($tag['pid']) ? 0 : $tag['pid'];
        $tag['condition'] = empty($tag['condition']) ? '' : $tag['condition'];
        $tag['sort'] = empty($tag['sort']) ? '' : $tag['sort'];
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$category_list = api("shop","Goods","categoryList",['.$tag['pid'].',"","'.$tag['condition'].'","'.$tag['sort'].'"]);
            $i = 0;';

        $str .= 'foreach($category_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 获取导航列表
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagNav_list($tag, $content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$nav_list = api("shop","Common","navList");
            $i = 0;';

        $str .= 'foreach($nav_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;

    }


    /**
     * 获取门户子栏目列表
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagCate_list($tag, $content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$cate_list = api("web","Cate","getChCateList",['.$tag['pid'].']);
            $i = 0;';

        $str .= 'foreach($cate_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;

    }


    /**
     * 尾部文章
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagFooter_article($tag, $content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $str = '';
        $str .= '<?php ';
        $str .= '$article_list = api("web","Article","footerArticleList");
            $i = 0;';

        $str .= 'foreach($article_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 选择区域
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagRegion($tag, $content)
    {
        if( empty($tag['pid']) ){
            $tag['pid'] = 0;
        }
        if( empty($tag['item']) ){
            $tag['item'] = 'region';
        }
        $str = '<?php ';
        $str .= '$cityList = api("sys","Region","getRegionList",['.$tag['pid'].']);';
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i = 0;
            foreach($cityList as $'.$key.' => $item):
                $i++;
                $'.$tag['item'].' = $item; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;
    }


    /**
     * 首页banner海报
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagBanner($tag,$content)
    {
        if( empty($tag['position']) ){
            $tag['position'] = 1;
        }
        if( empty($tag['item']) ){
            $tag['item'] = 'banner';
        }
        $str = '<?php ';
        if( empty($tag['limit']) ){
            $str .= '$adList = tempAdList("'.$tag['position'].'",10);';
        }else{
            $str .= '$adList = tempAdList("'.$tag['position'].'",'.$tag['limit'].');';
        }
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i = 0;
            foreach($adList as $'.$key.' => $item):
                $i++;
                $'.$tag['item'].' = $item; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;
    }



    /**
     * 获取商品主图
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagGoods_thumb($tag,$content)
    {
        // 获取商品图片
        $goods_id = $tag['goods'];
        $spec = empty($tag['spec']) ? '' : $tag['spec'];
        $str = '<?php ';
        $str .= '$spec = "'.$spec.'";';
        $str .= 'if(!empty($spec)) ';
        $str .= '$thumb = db("shop_goods","",false)->where("spec_key","'.$spec.'")->value("image_url");
        ';
        $str .= 'if( empty($thumb) )
                    $thumb = db("shop_goods","",false)->where("id",'.$goods_id.')->value("thumb");
                
        echo $thumb; ?>
        ';
        return $str;
    }


    /**
     * 获取用户的收货地址
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagUser_address($tag,$content)
    {

        $field = empty($tag['field']) ? '' : $tag['field'];
        $str = '<?php ';
        $str .= '
                $user_id = '.$tag["user"].';
                $condition = "`user_id` = $user_id and `status` > 0";';
        if( empty($tag['limit']) ){
            $str .= '$addressList = db("user_address","",false)->where($condition)->field("'.$field.'")->limit(10)->select();';
        }else{
            $str .= '$addressList = db("user_address","",false)->where($condition)->field("'.$field.'")->limit('.$tag['limit'].')->select();';
        }
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i = 0;
            foreach($addressList as $'.$key.' => $item):
                $i++;
                $'.$tag['item'].' = $item; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;

    }


    /**
     * 获取用户当前的购物车内容
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagChat_list($tag,$content)
    {
        // 设置查询条件
        $field = empty($tag['field']) ? '' : $tag['field'];
        $condition = '';
        if( !empty($tag['selected']) ){
            $condition = ' `selected` = '.$tag['selected'];
        }
        $str = '<?php ';
        if( empty($tag['limit']) ){
            $str .= '
                $user = "'.$tag["user"].'";
                 $condition = "`user_id` = $user or `session_id` = "$user";
                 $chatList = db("shop_cart","",false)->where($condition)->where("'.$condition.'")->field("'.$field.'")->limit(10)->select();';
        }else{
            $str .= '$chatList = db("shop_cart","",false)->where($condition)->where("'.$condition.'")->field("'.$field.'")->limit('.$tag['limit'].')->select();';
        }
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i = 0;';
        $str .= 'foreach($chatList as $'.$key.' => $item):';
        $str .= '$i++;';
        $str .= '$'.$tag['item'].' = $item;';
        $goodField = 'title,thumb,stock';
        $str .= '$goods = db("shop_goods","",false)->where("id",$item["goods"])->field("'.$goodField.'")->find();';
        $str .= '$'.$tag['item'].'["goods"] = $goods; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;

    }

    /**
     * 获取订单列表
     * @param $tag
     * @param $content
     * @return mixed
     */
    public function TagOrder_list($tag,$content)
    {
        // 设置查询条件
        $condition = '';
        if( !empty($tag['user']) ){
            $condition = ' `user_id` = '.$tag['user'];
            if( !empty($tag['status']) ){
                $condition = ' and ';
            }
        }
        if( !empty($tag['status']) ){
            $condition = ' `status` = '.$tag['status'];
        }
        $field = empty($tag['field']) ? '' : $tag['field'];
        $order = empty($tag['order']) ? 'id' : $tag['order'];
        $limit = empty($tag['limit']) ? '10' : $tag['limit'];
        $str = '<?php ';
        if( empty($tag['limit']) ){
            $str .= '$pageRow = tb_config("list_row",1,1);';
            $str .= '$orderList = db("shop_order","",false)->where("'.$condition.'")->limit('.$limit.')->field("'.$field.'")->order("'.$order.'")->paginate($pageRow);';
        }else{
            $str .= '$orderList = db("shop_order","",false)->where("'.$condition.'")->limit('.$limit.')->field("'.$field.'")->order("'.$order.'")->limit('.$tag['limit'].')->select();';
        }
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i=0;
                foreach($orderList as $'.$key.' => $item):';
        $str .= '$i++;
                $'.$tag['item'].' = $item; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;
    }

    /**
     * 获取订单中的商品
     * @param $tag
     * @param $content
     * @return string
     */
    public function TagOrder_detail($tag,$content)
    {
        $condition = ' `id` = '.$tag['id'];
        $field = empty($tag['field']) ? '' : $tag['field'];
        $str = '<?php ';
        $str .= '$orderInfo = db("shop_order","",false)->where("'.$condition.'")->field("'.$field.'")->find();';
        $str .= '$'.$tag['name'].' = $orderInfo; ?>';
        return $str;
    }


    /**
     * 获取订单中的商品
     * @param $tag
     * @param $content
     * @return string
     */
    public function TagOrder_goods_list($tag,$content)
    {
        $condition = ' `order_id` = '.$tag['id'];
        $field = empty($tag['field']) ? '' : $tag['field'];
        $order = empty($tag['order']) ? 'id' : $tag['order'];
        $str = '<?php ';
        $str .= '$orderGoodsList = db("shop_order_goods","",false)->where("'.$condition.'")->field("'.$field.'")->order("'.$order.'")->select();';
        $key = empty($tag['key']) ? 'key' : $tag['key'];
        $str .= '$i=0;
               foreach($orderGoodsList as $'.$key.' => $item):';
        $str .= '$i++;
                $'.$tag['item'].' = $item; ?>';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;
    }


    /**
     * 商品推送
     * ['goods_rank' => ['attr'=>'cid,order,limit,key,item','close'=>1]],
     * 格式：{goods_rank cid="商品分类的ID，推送当前分类下的商品" order="商品排序方式" limit="获取的商品数量" key="循环使用的key值" item="循环使用的value值"}{/goods_rank}
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagGoods_rank($tag, $content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $cid = !empty($tag['cid']) ? $tag['cid'] : '';
        $order = !empty($tag['order']) ? $tag['order'] : '';
        $order = explode(' ',$order);
        $order[0] = empty($order[0]) ? 'id' : $order[0];
        $order[1] = empty($order[1]) ? 'asc' : $order[1];
        $limit = !empty($tag['limit']) ? $tag['limit'] : tb_config('list_row',1,1);
        $str = '';
        $str .= '<?php ';
        $str .= '$goods_rank = getTableRows("shop_goods",["is_audit"=>1,"status"=>1,"trash"=>0';

        if( $cid !== '' ){
            $str .= ',"cid"=>'.$cid.'';
        }
        $str .= '],["'.$order[0].'"=>"'.$order[1].'"],'.$limit.');'."\n";
        $str .= '$i = 0;
                foreach($goods_rank as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
                $'.$tag['item'].'["url"] = U("shop/Goods/goodsInfo",["id"=>$'.$tag['item'].'["id"]]);
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 评论分数获取
     * ['comment_fraction' => ['attr'=>'goods,id','close'=>0]],
     * 格式：{comment_fraction goods="当前商品的ID" id="用于获取的名称"}
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagComment_fraction($tag,$content)
    {
        $str = '';
        $str .= '<?php ';
        $str .= '$base_where = ["id"=>'.$tag['goods'].',"is_show"=>1];
                $'.$tag['id'].' = api("shop","Goods","commentFraction",'.$tag['goods'].');
	            ?>';
        return $str;
    }

    /**
     * 文章列表
     * ['article_list' => ['attr'=>'cid,limit,key,item','close'=>1]],
     * 格式：{article_list cid="文章的分类ID，取出当前分类下的文章" limit="设置需要取出的条数" key="循环使用的key值" item="循环使用的value值"}{/article_list}
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagArticle_list($tag,$content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $order = !empty($tag['order']) ? $tag['order'] : 'sort desc';
        $limit = !empty($tag['limit']) ? $tag['limit'] : 10;
        $str = '';
        $str .= '<?php ';
        $str .= '$article_list = api("web","Article","getArticleList",['.$tag['cid'].','.$limit.',"'.$order.'"]);
            $i = 0;
        ';

        $str .= 'foreach($article_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;
                $'.$tag['item'].'["url"] = U("Home/Article/detail",["id"=>$'.$tag['item'].'["id"]]);
        ?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }

    /**
     * 广告列表
     * ['ad_list' => ['attr'=>'cid,limit,key,item','close'=>1]],
     * 格式：{ad_list cid="广告分类的ID,取出分类下的所有广告"  limit="取出的条数" key="循环使用的key值" item="循环使用的value值"}{/ad_list}
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagAd_list($tag,$content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $limit = !empty($tag['limit']) ? $tag['limit'] : 10;
        $str = '';
        $str .= '<?php ';
        if( empty($limit) ){
            $str .= '$ad_list = getAdList('.$tag['cid'].');';
        }else{
            $str .= '$ad_list = getAdList('.$tag['cid'].','.$limit.');';
        }

        $str .= ' $i = 0;
                foreach($ad_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= ' $i++;
                $'.$tag['item'].' = $item;?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }

    /**
     * 楼层广告列表
     * ['floor_ad_list' => ['attr'=>'cid,pid,limit,key,item','close'=>1]],
     * 格式：{floor_ad_list cid="广告分类的ID" pid="广告父级ID，取出当前父级下的所有广告" limit="取出的条数" key="循环使用的key值" item="循环使用的value值"}{/floor_ad_list}
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagFloor_ad_list($tag,$content)
    {
        $tag['key'] = empty($tag['key']) ? 'key' : $tag['key'];
        $limit = !empty($tag['limit']) ? $tag['limit'] : tb_config('list_row',1,1);
        $str = '';
        $str .= '<?php ';
        $str .= '$ad_list = getFloorAd('.$tag['cid'].','.$tag['pid'].','.$limit.');';

        $str .= '$i = 0;
            foreach($ad_list as $'.$tag['key'].'=>$item):'."\n";
        $str .= '$i++;
            $'.$tag['item'].' = $item;?>';
        $str .= $content;
        $str .= '<?php endforeach;?>';

        return $str;
    }


    /**
     * 资源标签，获取css真实路径
     * 格式：
     * {tbcss rel="stylesheet" type="text/css" module="模块名称|默认为home" src="css路径" }
     */
    public function tagTbcss($tag,$content){
        $rel = !empty($tag['rel']) ? $tag['rel'] : 'stylesheet';
        $type = !empty($tag['type']) ? $tag['type'] : 'text/css';
        $module = !empty($tag['module']) ? $tag['module'] : 'home';
        $src = !empty($tag['src']) ? $tag['src'] : '';
        if( empty($src) ){
            return null;
        }
        $public_path = PUBLIC_PATH == '/' ? '' : PUBLIC_PATH;

        $css_path =  $public_path . "/static" . DS  .$module .DS ."css".DS;
        $css_link = "";
        $src_arr = explode(",",$src);
        foreach( $src_arr as $src ){
            $css_link .= "<link rel='".$rel."' type='".$type."' href='".$css_path.$src."'>\n";
        };
        return $css_link;
    }

    /**
     * 资源标签，获取css真实路径
     * 格式：
     * {tbjs module="模块名称|默认为home" src="css路径" }
     */
    public function tagTbjs($tag){
        $module = !empty($tag['module']) ? $tag['module'] : 'home';
        $src = !empty($tag['src']) ? $tag['src'] : '';
        if( empty($src) ){
            return null;
        }
        $public_path = PUBLIC_PATH == '/' ? '' : PUBLIC_PATH;

        $css_path =  $public_path . "/static" . DS  .$module .DS ."js".DS;;
        $css_link = "";
        $src_arr = explode(",",$src);
        foreach( $src_arr as $src ){
            $css_link .= "<script src='".$css_path.$src."'></script>\n";
        };
        return $css_link;
    }

    /**
     * 资源标签，获取img真实路径
     * 格式：
     * {tbimg  module="模块名称|默认为home" src="css路径" }
     */
    public function tagTbimg($tag){
        $module = !empty($tag['module']) ? $tag['module'] : 'home';
        $src = !empty($tag['src']) ? $tag['src'] : '';
        if( empty($src) ){
            return null;
        }
        $public_path = PUBLIC_PATH == '/' ? '' : PUBLIC_PATH;
        $img_path =  $public_path . "/static" . DS  .$module .DS ."images".DS;
        $img_link = $img_path.$src;
        return $img_link;
    }

    /**
     * 列表标签
     * 格式：
     * {list model='模型名称'||table='表名称' condition='查询条件' field='获取的字段（空为获取所有）' key="循环的key" item="循环的value"}{/list}
     */
    public function tagList($tag,$content){
        //需要查询的表名
        $model = !empty($tag['model']) ? $tag['model'] : $tag['table'];

        //查询的条件
        $where = !empty($tag['condition']) ? $tag['condition'] : '';
        //查询的字段
        $field = !empty($tag['field']) ? $tag['field'] : '';
        //查询的条数
        $limit = !empty($tag['limit']) ? $tag['limit'] : '';
        //排序
        $order = !empty($tag['order']) ? $tag['order'] : '';
        $key  =  !empty($tag['key']) ? $tag['key'] : 'key';// 返回的变量key
        $item  =  !empty($tag['item']) ? $tag['item'] : 'item';// 返回的变量item
        $str = '';
        $str .= '<?php ';
        //查询出list数据
        if( empty($tag['model'])){
            $str .= '$dataList = db("'.$model.'","",false)->where("'.$where.'")->field("'.$field.'")->limit("'.$limit.'")->order("'.$order.'")->select();';
        }else{
            $module = request()->module();
            $str .= '$model_info = db("'.$module.'_model","",false) ->where("name",'.$model.') ->find();';
            $str .= '$model_table = str_replace(config("database.prefix"), "", $model_info["table"]);';
            $str .= 'if ($model_info["type"]==2) {
                $dataList = db($model_table)->where("'.$where.'")->field("'.$field.'")->limit("'.$limit.'")->order("'.$order.'")->select();
            }else{
                $base_table = "'.$module.'_content";
                $dataList = db($base_table) ->alias("a") ->where("'.$where.'") ->join("$model_table b","b.aid=a.id") ->where(["trash"=>0])->limit("'.$limit.'")->order("'.$order.'")->select();
            }';

        }
        $str .= '
                if($dataList == ""){
                    return "";
                }
                $rows = count($dataList);
                foreach($dataList as $'.$key.'=>$'.$item.'): ?>
        ';
        $str .= $content;
        $str .= '<?php endforeach; ?>';
        return $str;
    }


    /**
     * 详情标签
     * 格式：
     * {detail model='模型名称'||table='表名称' condition='查询条件' field='获取的字段（空为获取所有）' key="循环的key" item="循环的value"}
     */
    public function tagDetail($tag,$content){
        //需要查询的表名
        $model = !empty($tag['model']) ? $tag['model'] : $tag['table'];
        //查询的条件
        $where = !empty($tag['condition']) ? $tag['condition'] : '';
        //查询的字段
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $id  =  !empty($tag['id']) ? $tag['id'] : 'vo';// 返回的变量key

        $str = '';
        $str .= '<?php ';
        //查询出list数据
        if( empty($tag['model']) ){
            $str .= '$data = db("'.$model.'")->where("'.$where.'")->field("'.$field.'")->find();';
        }else{
            $module = request()->module();
            $str .= '$model_info = db("'.$module.'_model") ->where("name",'.$model.') ->find();';
            $str .= '$model_table = str_replace(config("database.prefix"), "", $model_info["table"]);';
            $str .= 'if ($model_info["type"]==2) {
                $data = db($model_table)->where("'.$where.'")->field("'.$field.'")->find();
            }else{
                $base_table = "'.$module.'_content";
                $data = db($base_table) ->alias("a") ->where("'.$where.'") ->join("$model_table b","b.aid=a.id")->find();
            }';
        }
        $str .= '
                $'.$id.' = $data;
        ';
        $str .= $content;
        $str .= ' ?>';
        return $str;
    }


    public function tagTbiz($tag, $content)
    {

        $sql = $tag['sql']; // sql 语句
        //  file_put_contents('a.html', $sql.PHP_EOL, FILE_APPEND);
        $sql = str_replace(' eq ', ' = ', $sql); // 等于
        $sql = str_replace(' neq  ', ' != ', $sql); // 不等于
        $sql = str_replace(' gt ', ' > ', $sql);// 大于
        $sql = str_replace(' egt ', ' >= ', $sql);// 大于等于
        $sql = str_replace(' lt ', ' < ', $sql);// 小于
        $sql = str_replace(' elt ', ' <= ', $sql);// 小于等于
        //$sql = str_replace(' heq ', ' == ', $sql);// 恒等于
        //$sql = str_replace(' nheq ', ' !== ', $sql);// 不恒等于

        //$sql = str_replace('__PREFIX__', C('database.prefix'), $sql); // 替换前缀

        $key  =  !empty($tag['key']) ? $tag['key'] : 'key';// 返回的变量key
        $item  =  !empty($tag['item']) ? $tag['item'] : 'item';// 返回的变量item
        $result_name  =  !empty($tag['result_name']) ? $tag['result_name'] : 'result_name';// 返回的变量key
        $t  =  !empty($tag['t']) ? $tag['t'] : TPSHOP_CACHE_TIME;// 缓存时间
        //$Model = new \Think\Model();
        //$name = 'sql_result_'.$item.rand(10000000,99999999); // 数据库结果集返回命名
        $name = 'sql_result_'.$item;//.rand(10000000,99999999); // 数据库结果集返回命名
        //$this->tpl->tVar[$name] = $Model->query($sql); // 变量存储到模板里面去
        $parseStr   =   '<?php
                                   
                                $md5_key = md5("'.$sql.'");
                                $'.$result_name.' = $'.$name.' = S("sql_".$md5_key);
                                if(empty($'.$name.'))
                                {                            
                                    $'.$result_name.' = $'.$name.' = \think\Db::query("'.$sql.'"); 
                                    S("sql_".$md5_key,$'.$name.','.$t.');
                                }    
                             ';
        $parseStr  .=   ' foreach($'.$name.' as $'.$key.'=>$'.$item.'): ?>';
        //$parseStr  .=   $this->tpl->parse($content).$tag['level'];
        $parseStr  .=   $content;
        $parseStr  .=   '<?php endforeach; ?>';

        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }

    public function tagAdv($tag, $content)
    {
        $order = !empty($tag['order']) ? $tag['order'] : 'id DESC';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '1';
        // $where = $tag['where']; //查询条件
        $item  = !empty($tag['item']) ? $tag['item'] : 'item';// 返回的变量item
        $key  =  !empty($tag['key']) ? $tag['key'] : 'key';// 返回的变量key
        $pid  =  !empty($tag['pid']) ? $tag['pid'] : '0';// 返回的变量key

        $str = '<?php ';
        $str .= '$pid ='.$pid.';';
        $str .= '$ad_position = db("admin_ad_position")->column("position_id,position_name,ad_width,ad_height","position_id");';
        $str .= '$result = db("admin_ad")->where("pid=$pid  and enabled = 1 and start_time < '.strtotime(date('Y-m-d H:00:00')).' and end_time > '.strtotime(date('Y-m-d H:00:00')).' ")->order("id desc")->limit("'.$limit.'")->select();';
        $str .= '
$c = '.$limit.'- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $'.$key.'=>$'.$item.'):       
    
    $'.$item.'[position] = $ad_position[$'.$item.'[pid]]; 
    if(I("get.edit_ad") && $'.$item.'[not_adv] == 0 )
    {
        $'.$item.'[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $'.$item.'[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$'.$item.'[ad_id]";        
        $'.$item.'[title] = $ad_position[$'.$item.'[pid]][position_name]."===".$'.$item.'[ad_name];
        $'.$item.'[target] = 0;
    }
    ?>';
        $str .=  $content;
        $str .= '<?php endforeach; ?>';
        if(!empty($str)) {
            return $str;
        }
        return ;
    }
}
