<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 拼团管理模块
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\home;

use app\shop\home\HomeBase;
use app\sys\controller\Api;
use think\Db;
use app\shop\api\Pieces as PiecesApi;

class Pieces extends HomeBase
{
    /**
     * 立即拼团 开团
     */
    public function index()
    {
        //查看type是否存在
        $type = request()->param('type');
        $now_date = date('Y-m-d H:i:s');
        if( $type == 'hot_list' ){
            $group_goods = PiecesApi::availableList();
            $this->assign('group_goods_list',$group_goods);
            return $this->fetch('groups_list');
        }else{
            //获取今日热拼榜
            $group_goods = PiecesApi::availableList(5);
            //获取最新的一个
            $new_group_goods = PiecesApi::newPieces();
            $this->assign('group_goods_list',$group_goods);
            $this->assign('new_group_goods',$new_group_goods);
            return $this->fetch();
        }
    }

    /**
     * 拼团详情
     */
    public function pieces_group($id)
    {
        //查看
        if(empty($id)){
            $this->error('参数错误');
        }
        $param = explode('--', $id);
        $goods_id = $param[0];

        //获取团购活动数据
        $pieces_id = PiecesApi::getPiecesId($id);
        $group_info = api('shop','Pieces','::get',$pieces_id);
        //查看商品是否参团
        if( empty($group_info) ){
            return $this->error('该商品未参团');
        }
        //参加团购的规格
        $goodsspec = json_decode(htmlspecialchars_decode($group_info['goodsspec']),true);
        $spec = implode(',',array_keys($goodsspec));

        //获取团购的价格
        // 增加商品的点击量
        Db::name('shop_goods') ->where('id',$goods_id) ->setInc('view');

        // 获取商品详情数据
        $goodsInfo = Db::name('shop_goods') ->find($goods_id);
        $goodsInfo['comment_count'] = Db::name('shop_goods_comment') ->where('goods_id',$goods_id) ->count('id');
        $goodsInfo['brand_name'] = getTableValue('shop_brand',['id'=>$goodsInfo['brand_id']],'name');
        $this->assign('goods',$goodsInfo);

        // 取商品所有的规格数据
        $spec_list = Db::name('shop_spec_price') ->where('id in('.$spec.')') ->where('goods_id',$goods_id) ->order(['price'=>'ASC']) ->column('key_sign');
        $filter_spec = [];
        if ($spec_list) {
            // 如果参数中没有规格,则将最低价格的规格作为当前规格
            if (!isset($param[1])) {
                $spec_curr = $spec_list[0];
                $param[1] = $spec_list[0];
            }else{
                $spec_curr = $param[1];
            }
            //获取spec的ID
            $sed_spec = Db::name('shop_spec_price')->where('key_sign',$param['1'])->field('id,price')->find();
            if(!empty($goodsspec[$sed_spec['id']])){
                $group_info['price'] = $goodsspec[$sed_spec['id']];
            }else{
                $group_info['price'] = $sed_spec['price'];
            }
            $this->assign('group_info',$group_info);

            $this->assign('spec_curr',$spec_curr);
            $lowPriceKey = explode('_', $spec_curr);
            $this->assign('lowPriceKey',$lowPriceKey);
            //查找已选字符
            $selectedSpec = '';
            foreach ($lowPriceKey as $k => $v) {
                $spec_id = getTableValue('shop_spec_item',['id'=>$v],'spec_id');
                $selectedSpec .= getTableValue('shop_spec_item',['id'=>$v],'item').',';
                $curr[$spec_id] = $v;
            }
            //默认的规格金额
            $selectedSpecPrice = getTableValue('shop_spec_price','key_sign=\''.$spec_curr.'\'','price');
            $this->assign('selectedSpec',rtrim($selectedSpec,','));
            $this->assign('selectedSpecPrice',$selectedSpecPrice);

            $spec_str  = implode('_', $spec_list);
            $spec_arr = explode('_', $spec_str);
            $spec_arr = array_unique($spec_arr);
            sort($spec_arr);
            $spec_id = Db::name('shop_spec_item') ->where('id','IN',$spec_arr) ->column('spec_id');
            $spec_id = array_unique($spec_id);
            foreach ($spec_id as $key => $spec) {
                $filter_spec[$spec] = Db::name('shop_spec') ->find($spec);
                $item = Db::name('shop_spec_item') ->where('spec_id',$spec) ->where('id','IN',$spec_arr) ->cache(true) ->select();
                foreach ($item as $k => $v) {
                    $spec_ed = $curr;
                    $spec_ed[$spec] = $v['id'];
                    $key = implode('_', $spec_ed);
                    $url_param = $param;
                    $url_param[1] = $key;
                    $url['id'] = implode('--', $url_param);
                    $item[$k]['href'] = url('mshop/Goods/groups_msg',$url);
                }
                $filter_spec[$spec]['item'] = $item;
            }
        }
        $this->assign('filter_spec',$filter_spec);

        // 取商品属性数据
        $spec_attr = '0';
        if (isset($param[1])) {
            $spec_attr = $param[1];
        }
        // 输出当前商品的属性值
        $attr_list = Db::name('shop_goods_attr') ->where(['goods_id'=>$goods_id,'spec_key'=>$spec_attr]) ->column('attr_value','attr_id');
        $this->assign('attr_list',$attr_list);
        // 输出当前商品的所有属性项目
        $attribute = Db::name('shop_attribute') ->where('cate_id',$goodsInfo['type_id']) ->column('attr_name','id');
        $this->assign('attribute',$attribute);

        // 如果商品带有规格数据,则取对应规格的相册,如相册为空则取商品默认相册
        if (isset($param[1])) {
            // 取对应规格的商品相册
            $goods_images_list = Db::name('shop_spec_image') ->where(['goods_id'=>$goods_id,'spec_key'=>$param[1]]) ->select();
            if (empty($goods_images_list)) {
                // 对应规格相册不存在,则取商品默认相册
                $goods_images_list = Db::name('shop_goods_images') ->where('goods_id',$goods_id) ->select();
            }

            // 取对应规格的商品详情
            $goods_content = Db::name('shop_spec_content') ->where(['goods_id'=>$goods_id,'spec_key'=>$param[1]]) ->value('content');
            $spec_goods = Db::name('shop_spec_price') ->where(['goods_id'=>$goods_id,'key_sign'=>$param[1]]) ->field('price,store_count') ->find();
            $goods_content = empty($goods_content)?$goodsInfo['content']:$goods_content;
            $goods_price = empty($spec_goods)?$goodsInfo['shop_price']:$spec_goods['price'];
            $goods_stock = empty($spec_goods)?$goodsInfo['stock']:$spec_goods['store_count'];
        }else{
            // 商品没有规格参数,取商品的默认相册数据,默认的商品详情
            $goods_images_list = Db::name('shop_goods_images') ->where('goods_id',$goods_id) ->select();
            $goods_content = $goodsInfo['content'];
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }
        $this->assign('goods_images_list',$goods_images_list);
        $this->assign('goods_content',$goods_content);
        $this->assign('goods_price',$goods_price);
        $this->assign('goods_stock',$goods_stock);

        //获取商品最新评论
        $new_comment = Db::name('shop_goods_comment')->where('goods_id',$goods_id)->where('content is not null')->find();
        if( $new_comment != '' ){
//用户名称
            $new_comment['user'] = Db::name('users')->where('id',$new_comment['user_id'])->field('head_pic,nickname')->find();
            //评论图片
            $new_comment['img'] = json_decode($new_comment['img']);
            $new_comment['img_count'] = count($new_comment['img']);
            $this->assign('comment',$new_comment);
        }
        //获取第一个参加当前团购的人
        $this->assign('comment',$new_comment);
        if( request()->isAjax() ){
            $this->success($this->fetch('ajax_groups_msg'));
        }
        return $this->fetch();
    }

}