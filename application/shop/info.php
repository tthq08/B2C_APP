<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 门户模块内容控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 深圳市俊网网络有限公司
// +----------------------------------------------------------------------

/**
 * 模块信息
 */
return [
    // 模块名[必填]
    'name'        => 'shop',
    // 模块标题[必填]
    'title'       => '{"zh-cn":"商城","en-us":"SHOP"}',
    // 模块唯一标识[必填]，格式：模块名.开发者标识.module
    'identifier'  => 'shop.jungo.module',
    // 模块图标[选填]
    'icon'        => 'fa fa-fw fa-shopping-cart',
    // 模块描述[选填]
    'description' => '商城模块',
    // 开发者[必填]
    'author'      => 'Jungo',
    // 开发者网址[选填]
    'author_url'  => 'http://www.junnet.net',
    // 版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
    'version'     => '1.0.0',
	// 是否为系统模块[必填]，默认为否
    'system_module'     => '0',
	 // 数据表[有数据库表时必填]
    'tables' => [
        'goods_category',
        'goods_categoryimg',
        'shop_attribute',
        'shop_brand',
        'shop_field',
        'shop_goods',
        'shop_goods_attr',
        'shop_goods_images',
        'shop_goods_type',
        'shop_model',
        'shop_spec',
        'shop_spec_image',
        'shop_spec_item',
        'shop_spec_price',
        'shop_spec_content',
        'shop_promotion',
        'shop_promotion_panic',
        'shop_promotion_group',
        'shop_promotion_type',
        'shop_promotion_order',
        'shop_promotion_discount',
        'virtual_money',
        'user_vm',
        'user_level',
        'shop_promotion_goods',
        'shop_nav',
        'shop_coupon',
        'shop_coupon_type',
        'user_coupon',
        'region',
        'shop_discount_type',
        'shop_order',
        'shop_order_action',
        'shop_order_goods',
        'shop_order_pay',
        'shop_points_receive',
        'shop_return_goods',
        'shop_shipping',
        'shop_delivery_doc',
    ],
    // 原始数据库表前缀
    // 用于在导入模块sql时，将原有的表前缀转换成系统的表前缀
    // 一般模块自带sql文件时才需要配置
    'database_prefix' => 'tb_',
];
