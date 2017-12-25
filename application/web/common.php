<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 门户模块通用函数库
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
use think\Db;
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
 * 获取订单内的商品
 * @param int $id 订单ID
 * @return array
 */
function getOrderGoods($id){
    //获取
    $orderGoods = Db::name('shop_order_goods')->alias('og')->join(config('prefix')."shop_goods g",'og.goods_id = g.id')->field('g.id,g.title as goods_name,g.shop_price,og.pay_price,og.payable_price,g.thumb')->where('order_id',$id)->select();

    return $orderGoods;
}

function getBrotherCate($cid)
{
	$pid = Db::name('web_cate') ->where('id',$cid) ->value('pid');
	$brotherCateTree = getSubCate($pid);
	return $brotherCateTree;
}

function getSubCate($cid)
{
	$cates = Db::name('web_cate') ->where('pid',$cid) ->order(['sort'=>'ASC','id'=>'DESC']) ->select();
	if ($cates) {
		foreach ($cates as $key => $val) {
			$cates[$key]['children'] = getSubCate($val['id']);
		}
	}else{
		$cates = null;
	}
	
	return $cates;
}

/**
 * 取当前栏目的顶级栏目信息
 * @param int $cid 栏目ID
 * @return array
 */
function getTopCateInfo($cid)
{
	$cate = Db::name('web_cate') ->where('id',$cid) ->value('pid_path');
	$pid_path = ltrim($cate,'0,');
	$pid_arr = explode(',', $pid_path);
	$top_id = $pid_arr[0];
	$top_id = empty($top_id)?$cid:$top_id;
	// dump($pid_arr);
	$topCate = Db::name('web_cate') ->find($top_id);
	return $topCate;
}

/**
 * 获取内容栏目信息
 * @param int $cid 栏目ID
 * @param string $field 字段名，为空时输出栏目所有信息，否则输出指定字段的信息
 * @return array | string
 */
function getCateInfo($cid,$field='')
{
	$cate = Db::name('cms_cate') ->where('id',$cid) ->find();
	if (!empty($field)) {
		$result = $cate[$field];
	} else {
		$result = $cate;
	}
	return $result;
}

/**
 * 检查数据表是否存在
 * @param string $table_name 附加表名
 * @return string
 */
// function table_exist($table_name = '')
// {
//     return true == Db::query("SHOW TABLES LIKE '{$table_name}'");
// }

?>