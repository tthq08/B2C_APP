<?php 
	return [
		'content_list' => 'web/Index/cate',		//内容列表页前台访问路径
		'content_show' => 'web/Index/cate_show',	//内容详情页前台访问路径
		 // 默认跳转页面对应的模板文件
	    'dispatch_success_tmpl'  => ROOT_PATH . 'themes/' . tb_config('web_template',1) . '/web/public/dispatch_jump.html',
	    'dispatch_error_tmpl'    => ROOT_PATH . 'themes/' . tb_config('web_template',1) . '/web/public/dispatch_jump.html',
	];
 ?>