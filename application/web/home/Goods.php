<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 官网模块前台主控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\web\home;

use app\web\home\HomeBase;
use think\Db;
class Goods extends HomeBase
{
	public function lists($id)
	{
		$this->assign('active','Product');
		$cate = Db::name('web_cate') ->find(4);
		$this->assign('cate',$cate);
		if (empty($id)) {  	// 如果没有分类参数，则取第一个分类
			$id = Db::name('goods_category') ->where(['pid'=>0,'is_show'=>1]) ->order('id asc')->value('id');
			$top_id = $id;
		}
		$cate_ids = Db::name('goods_category') ->where('id',$id) ->value('parent_id_path');
		$cate_ids = ltrim($cate_ids,'0,');
		$cate_ids = explode(',', $cate_ids);
		$top_id = isset($top_id)?$top_id:$cate_ids[0];
		$this->assign('top_cate',$top_id);
		$goods_cate = Db::name('goods_category') ->where(['pid'=>0,'is_show'=>1]) ->order('id asc') ->select();
		foreach ($goods_cate as $key => $cat) {
			$goods_cate[$key]['children'] = $this->getSubCate($cat['id']);
		}
		$this->assign('goods_cate',$goods_cate);
		$goods = Db::name('shop_goods') ->where('FIND_IN_SET('.$id.',cat_tree)') ->paginate(6);
		$page = $goods -> render();
        $this->assign('page',$page);
        $this->assign('content_list',$goods);
		return $this->fetch();
	}

	public function show($id)
	{
		if(empty($id)){
			$this->error(lang('param_is_failed'));
		}
		$param = explode('--', $id);
		$goods_id = $param[0];
		$this->assign('active','Product');

		// 增加商品的点击量
		Db::name('shop_goods') ->where('id',$goods_id) ->setInc('view');

		// 获取商品详情数据
		$goodsInfo = Db::name('shop_goods') ->find($goods_id);
		$goodsInfo['comment_count'] = Db::name('shop_goods_comment') ->where('goods_id',$goods_id) ->count('id');
		$goodsInfo['brand_name'] = getTableValue('shop_brand',['id'=>$goodsInfo['brand_id']],'name');
		$this->assign('goods',$goodsInfo);

		// 取商品所有的规格数据
		$spec_list = Db::name('shop_spec_price') ->where('goods_id',$goods_id) ->order(['price'=>'ASC']) ->column('key_sign');
		$filter_spec = [];
		if ($spec_list) {
			// 如果参数中没有规格,则将最低价格的规格作为当前规格
			if (!isset($param[1])) {
				$spec_curr = $spec_list[0];
				$param[1] = $spec_list[0];
			}else{
				$spec_curr = $param[1];
			}
			$lowPriceKey = explode('_', $spec_curr);
			$this->assign('lowPriceKey',$lowPriceKey);
			foreach ($lowPriceKey as $k => $v) {
				$spec_id = getTableValue('shop_spec_item',['id'=>$v],'spec_id');
				$curr[$spec_id] = $v;
			}
			// dump($curr);

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
					$item[$k]['href'] = url('shop/Goods/goodsInfo',$url);
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

		// 取出当前商品的分类详细信息
    	$goodsCate = Db::name('goods_category') ->find($goodsInfo['cat_id']);
    	// 显示当前分类的层级数据
    	$cateLv = explode(',', $goodsCate['parent_id_path']);
    	$lv_curr = explode(',', ltrim($goodsCate['parent_id_path'],'0,'));
    	unset($cateLv[count($cateLv)-1]);
    	foreach ($cateLv as $key => $lv) {
    		$cate['id'] = $lv_curr[$key];
    		$cate['title'] = Db::name('goods_category') ->cache(true) ->where('id',$lv_curr[$key]) ->value('name');
    		$cate['brothers'] = Db::name('goods_category') ->cache(true) ->where(['pid'=>$lv,'is_show'=>1]) ->column('id,name,pid,level','id'); 
    		 $goods_category[] = $cate;  		
    	}
    	$this->assign('goods_category',$goods_category);


    	return $this->fetch();
	}

	public function ajaxComment()
	{
		$goods_id = input("goods_id",'0'); 
		$where = ['goods_id'=>$goods_id];
        $comment = Db::name('shop_goods_comment')->where($where)->order(['is_essence'=>'DESC','add_time'=>'DESC'])->paginate(10,'',['query'=>$where]);
        $list = $comment ->all();
        $show = $comment->render();

        foreach($list as $k => $v){
            $v['img'] = unserialize($v['img']); // 晒单图片
            if ($v['is_essence']==1) {
            	$commList['essence'][] = $v;
            }else{
            	$commList['comment'][] = $v;
            }     
        }
        // dump($commList);
        $this->assign('commentlist',$commList);// 商品评论
        // $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出        
        return $this->fetch();
	}

	private function getSubCate($id)
	{
		$sub = Db::name('goods_category') ->where(['pid'=>$id,'is_show'=>1]) ->order('id asc') ->select();
		if ($sub) {
			foreach ($sub as $key => $val) {
				$sub[$key]['children'] = $this->getSubCate($val['id']);				
			}
		}
		return $sub;
	}
}