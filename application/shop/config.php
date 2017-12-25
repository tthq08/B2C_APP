<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 商城模块配置文件
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------
	'order_status' => [
	    '-3' => '已作废',
        '-2' => '无效订单',
        '-1' =>	'已删除',
		'0'	=>	'已取消',
        '1'	=>	'未支付',
		'2' => 	'已支付，待确认',
		'3' => 	'已确认，待发货',
		'4' => 	'已发货，待收货',
		'5' => 	'已收货，待评价',
 		'6' => 	'已完成',
	],
	'shipping_status' => [
		'0' =>	'未发货',
		'1' =>	'已发货',
		'2' =>	'部分发货',
	],
	'pay_status' => [
		'0'	=>	'未支付',
		'1'	=>	'已支付',
	],
    //优惠方式
    'discount_type' => [
        '1' => '折扣券',
        '2' => '代金券',
        '3' => '积分券',
        '4' => '优惠券',
        '5' => '免邮券',
    ],
    //发放类型
    'payment_type' => [
        '1' => '线下发放',
        '2' => '指定用户发放',
        '3' => '用户领取',
        '4' => '注册发放',
        '5' => '邀请发放',
    ],
    //退货
    'return_goods' => [
        '-2' => '退回买家',//未达到退货要求，退回买家
        '-1' => '卖家拒绝',//卖家拒绝退货
        '0' => '用户取消',
        '1' => '待卖家处理',
        '2' => '待买家退货',
        '3' => '待卖家收货',
        '4' => '待卖家退款',
        '5' => '退货完成',
    ],
];