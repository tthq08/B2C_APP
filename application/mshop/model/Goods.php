<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 后台商品模型
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\mshop\model;


use think\Model;
use think\Db;

class Goods extends Model
{
	/**
	 * 获取已有商品的品牌数据
	 * @param string  $goods_ids     商品ID列表
	 * @param array $filter_param       过滤条件
	 * @param array $order       操作路径
	 * @return string
	 */
	public function getFilterBrand($goods_ids,$filter_param,$url)
	{
		if (!empty($filter_param['brand_id'])) {
			return [];
		}
		// 取出当前商品列表中所有的商品品牌ID
		$brand_ids = Db::name('shop_goods') ->field('GROUP_CONCAT(brand_id) as ids') ->where('id','IN',$goods_ids) ->find();
		// $brand_ids = Db::name('shop_goods') ->field('GROUP_CONCAT(brand_id) as ids') ->find();
		$brand_ids = $brand_ids['ids'];
		$brand_arr = explode(',', $brand_ids);
		// 去除品牌ID的重复ID
		$brand_arr = array_unique($brand_arr);

		$brand = Db::name('shop_brand') ->where('id','IN',$brand_arr) ->column('id,name,logo','id');
		foreach ($brand as $key => $value) {
			$filter_param['brand_id'] = $value['id'];
			$param_url['id'] = implode('--', $filter_param);
			$brand[$key]['href'] = url($url,$param_url);
		}
		return $brand;
	}

	/**
	 * 获取已有商品的属性数据
	 * @param string  $goods_ids     商品ID列表
	 * @param array $filter_param       过滤条件
	 * @param array $order       操作路径
	 * @return string
	 */
	public function getFilterAttr($goods_ids,$filter_param,$url)
	{
		$attr_arr = Db::name('shop_goods_attr') ->where('goods_id','IN',$goods_ids) ->select();
		if (!empty($attr_arr)) {
			$attr_id = [];
			foreach ($attr_arr as $key => $arr) {
				$is_slect = Db::name('shop_attribute') ->where('id',$arr['attr_id']) ->value('attr_index');
				if ($is_slect==1) {
					$attr_id[] = $arr['attr_id'];
					$attr_val[] = $arr['attr_value'];
				}
			}
			$vals = [];
			// 拼接属性ID与商品的属性值
			// dump($attr_val);
			// dump($attr_id);die;
			if (!empty($attr_val)) {
				foreach ($attr_val as $key => $val) {
					if (!isset($vals[$attr_id[$key]])) {
						$vals[$attr_id[$key]][] = $val;
					}else{
						if(!in_array($val, $vals[$attr_id[$key]])){
							$vals[$attr_id[$key]][] = $val;
						}
					}
				}
			}
			// dump($vals);
			// 过滤重复的属性ID
			$attr_id = array_unique($attr_id);

			// 已经存在的属性参数
			$attr_exist = empty($filter_param['attr'])?'':$filter_param['attr'];
			if (empty($attr_exist)) {
				$attr_id_exist = [];
			}else{
				$exists = explode('-', $attr_exist);
				// 经过处理,获取已存在的属性ID 
				foreach ($exists as $k => $attr) {
					$attr_array = explode('_', $attr);
					$attr_id_exist[] = $attr_array[0];
				}			
			}
			$attribute_arr = [];
			foreach ($attr_id as $k => $id) {
				// 已存在的属性不再显示
				if (!in_array($id, $attr_id_exist)) {
					$value = [];
					if (!empty($vals[$id])) {
						foreach ($vals[$id] as $i => $val) {
							$filter_param['attr'] = ltrim($attr_exist.'-'.$id.'_'.$val,'-');
							$param_url['id'] = implode('--', $filter_param);
							$value[] =  [
								'key' => $id,
								'val' => $val,
								'href' => url($url,$param_url)
							];
						}
						$attribute_arr[] = [
							'id' => $id,
							'attr_name' => getTableValue('shop_attribute',['id'=>$id],'attr_name'),
							'attr_value' => $value,
						];
					}
				}
			}
			return $attribute_arr;
		}else{
			return [];
		}
	}

	/**
	 * 获取已有商品的属性数据
	 * @param string  $goods_ids     商品ID列表
	 * @param array $filter_param       过滤条件
	 * @param array $order       操作路径
	 * @return string
	 */
	public function getFilterPrice($goods_ids,$filter_param,$url,$c=5)
	{
		if (!empty($filter_param['price'])) {
			return [];
		}
		$price_list = Db::name('shop_goods') ->where('id','IN',$goods_ids) ->column('shop_price');
		if ($price_list) {
			rsort($price_list);
			$max_price = (int)$price_list[0];
		}else{
			$price_list=[];
			$max_price= 0;
		}
		$psize = ceil($max_price/$c);
		$parr = [];
		if (count($price_list)<=$c) {
			foreach ($price_list as $key => $price) {
				$filter_param['price'] = $price;
				$param_url['id'] = implode('--', $filter_param);
				$parr[] = ['value'=>"$price",'href'=>url($url,$param_url)];
			}
		}else{			
			for ($i=0; $i < $c; $i++) { 
				$start = $i * $psize;
				$end = $start + $psize;

				// 如果该价格段没有商品,则不显示
				$in = false;
				foreach ($price_list as $k => $v) {
					if ($v>$start && $v<$end) {
						$in = true;
					}
				}
				if ($in) {
					continue;
				}

				$filter_param['price'] = "{$start}-{$end}";
				$param_url['id'] = implode('--', $filter_param);
				if ($i==0) {
					$parr[] = ['value'=>"{$end}以下",'href'=>url($url,$param_url)];
				}elseif ($i == ($c-1)) {
					$parr[] = ['value'=>"{$end}以上",'href'=>url($url,$param_url)];
				}else{
					$parr[] = ['value'=>"{$start}-$end",'href'=>url($url,$param_url)];
				}
			}
		}
		return $parr;
	}

	/**
	 * 输出已选中项目
	 * @param string  $goods_ids     商品ID列表
	 * @param array $filter_param       过滤条件
	 * @param array $order       操作路径
	 * @return string
	 */
	public function getFilterMenu($filter_param,$url)
	{
		$menu = [];
		if (!empty($filter_param['brand_id'])) {
			$brand_param = $filter_param;
			$brand_id = $brand_param['brand_id'];
			$brand_param['brand_id']='';
			$param_url['id'] = implode('--', $brand_param);
			$menu[] = [
				'text' => '品牌:'.getTableValue('shop_brand',['id'=>$brand_id],'name'),
				'href' => url($url,$param_url),
			];
		}
		if (!empty($filter_param['price'])) {
			$price_param = $filter_param;
			$price = $price_param['price'];
			$price_param['price']='';
			$param_url['id'] = implode('--', $price_param);
			$menu[] = [
				'text' => '价格:'.$price,
				'href' => url($url,$param_url),
			];
		}
		if (!empty($filter_param['attr'])) {
			$attr = $filter_param['attr'];
			$attr_arr = explode('-', $filter_param['attr']);
			foreach ($attr_arr as $key => $value) {
				$attr_param  = $attr_arr;
				$attr_single = explode('_', $value);
				unset($attr_param[$key]);
				$filter_param['attr'] = implode('-', $attr_param);
				$param_url['id'] = implode('--', $filter_param);
				$menu[] = [
					'text' => getTableValue('shop_attribute',['id'=>$attr_single[0]],'attr_name').":".$attr_single[1],
					'href' => url($url,$param_url)
				];
			}
		}
		return $menu;
	}

	/**
	 * 根据选择的品牌筛选商品
	 * @param string $brand_id       品牌ID
	 * @param string  $goods_ids     商品ID列表
	 * @return string
	 */
	public function getGoodsIDByBrand($brand_id,$goods_id)
	{
		$goods = Db::name('shop_goods') ->where('id','IN',$goods_id) ->where('brand_id',$brand_id) ->column('id');
		$goods_id = implode(',', $goods);
		return $goods_id;
	}

	/**
	 * 根据选择的价格筛选商品
	 * @param string $price       价格
	 * @param string  $goods_ids     商品ID列表
	 * @return string
	 */
	public function getGoodsIDByPrice($price,$goods_id)
	{
		$price_arr = explode('-', $price);

		if (count($price_arr)<=1) {
			$goods = Db::name('shop_goods') ->where('id','IN',$goods_id) ->where('shop_price',$price_arr[0]) ->column('id');
		} else {
			$goods = Db::name('shop_goods') ->where('id','IN',$goods_id) ->where('shop_price','BETWEEN',$price_arr) ->column('id');
		}
		
		$goods_id = implode(',', $goods);
		return $goods_id;
	}

	/**
	 * 根据选择的属性筛选商品
	 * @param string $attr       选中属性
	 * @param string  $goods_ids     商品ID列表
	 * @return string
	 */
	public function getGoodsIDByAttr($attr,$goods_id)
	{
		$attr_array = explode('-', $attr);
		foreach ($attr_array as $key => $att) {
			$attri = explode('_', $att);
			$attr_id[] = $attri[0];
			$attr_value[] = $attri[1];
		}
		if (count($attr_id)>1) {
			$goods = Db::name('shop_goods_attr') ->where('spec_key',0) ->where('attr_id','IN',$attr_id) ->where('attr_value','IN',$attr_value) ->group('goods_id') ->having('count(goods_id)>1') ->column('goods_id');
		} else {
			$goods = Db::name('shop_goods_attr') ->where('spec_key',0) ->where('attr_id','IN',$attr_id) ->where('attr_value','IN',$attr_value) ->column('goods_id');
		}
		$goods = array_unique($goods);
		$goods_id = implode(',', $goods);
		return $goods_id;
	}
}