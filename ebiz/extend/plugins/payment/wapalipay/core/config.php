<?php
return array (
		// 应用ID,您的APPID。
		'app_id' => "2088521421873197",

		// 商户私钥，您的原始格式RSA私钥
		'merchant_private_key' => "pzxeozl0p94izwso8fabkwx9qkffh75w",
		
		// 异步通知地址
		'notify_url' => "http://b2cd.git.com/alipay.trade.wap.pay-PHP-UTF-8/notify_url.php",
		
		// 同步跳转
		'return_url' => "http://b2cd.git.com/alipay.trade.wap.pay-PHP-UTF-8/return_url.php",

		// 编码格式
		'charset' => "UTF-8",

		// 签名方式
		'sign_type'=>"RSA2",

		// 支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		// 支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "",
		
	
);