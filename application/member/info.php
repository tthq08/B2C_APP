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
    'name'        => 'member',
    // 模块标题[必填]
    'title'       => '{"zh-cn":"会员","en-us":"Members"}',
    // 模块唯一标识[必填]，格式：模块名.开发者标识.module
    'identifier'  => 'member.jungo.module',
    // 模块图标[选填]
    'icon'        => 'fa fa-fw fa-users',
    // 模块描述[选填]
    'description' => '会员模块',
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
        'user_level',
        'user_vm',
        'users',
        'user_field',
        'user_model',
        'users_tree',
        'user_recharge',
        'user_remittance',
        'user_withdrawals',
        'user_account_log',
        'user_sign',
    ],
    // 原始数据库表前缀
    // 用于在导入模块sql时，将原有的表前缀转换成系统的表前缀
    // 一般模块自带sql文件时才需要配置
    'database_prefix' => 'tb_',
];
