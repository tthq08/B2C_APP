<?php
/**
 * 缓存数据说明
 * 一级:模块
 * 二级:标签
 * 三级:缓存名
 * User: iconblog
 * Date: 2017/12/20
 * Time: 下午2:04
 */
return [
    // 系统模块
    'system' => [
        'config'=>[ // 配置标签
            'config'
        ],
        'nav' => [ // 导航
            'navList'
        ]
    ],

    // 商城模块
    'shop' => [
        // 商品评价部分
        'goodsComment' => [
            'goodsCommentNum:',// + goodsID商品评价数量
            'goodsCommentFraction:',// +goodsID,商品评分汇总
        ],
        // 获取平台推荐商品
        'recommend' => [
            'recommend',// 获取推荐商品
        ],
        // 商品分类部分
        'category' => [
            'category:',// + 商品分类ID,商品分类信息
            'cate_child_list:',// +商品分类ID,下级商品分类列表
            'categoryTopTree:',// +商品分类ID,分类上层树
        ],
        // 优惠券
        'coupon' => [
            'coupon:' ,// + 优惠券ID,优惠券信息
            'coupon_discount_type' ,// 优惠方式列表
            'coupon_discount_type:' ,// + 优惠方式ID,具体的优惠方式信息
            'coupon_send_type'  ,// 发放方式列表
            'coupon_send_type:'  ,// + 发放方式ID,具体的发放方式信息
            'coupon_level' ,// 优惠券等级列表
            'coupon_level:' ,// + 优惠券等级ID,获取优惠券等级信息
        ],
        // 促销
        'promotion' => [
            'promotion:'.':',// + 促销类型 + 促销类型ID,获取促销详细信息
            'promotion_panic_list:'.'page:',// + 日期 + 分页,抢购商品列表页
        ],
        'goods' => [
            'goodsInfo:' ,// +商品ID,商品信息
        ],
        'brand' => [
            'brand:',// +品牌ID,品牌详细信息
        ],
        'attribute' => [
            'attribute:',// +商品属性ID,属性详细信息
            'attributeList:',// +商品分类ID,商品分类下的属性列表
        ],
        'spec' => [
            'spec:',// +规格ID,规格信息
            'spec_item:' ,// +规格项ID,规格项信息
        ],
        'goodsImagesList' => [
            'goods_images_list:',// +商品ID,商品图片列表
        ],

    ],
    'web' => [
        'column' => [
            'columnList:',// +栏目ID,获取子栏目列表
            'columnInfo:',// +栏目ID,获取栏目信息
        ],
        'content' => [
            'contentInfo:',// +内容ID,获取内容详情
        ]
    ],


];