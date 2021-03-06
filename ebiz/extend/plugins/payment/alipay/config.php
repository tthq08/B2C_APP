<?php
 return array (
  'code' => 'alipay',
  'name' => 'PC端支付宝',
  'version' => '1.0',
  'author' => 'jungo',
  'desc' => 'PC端支付宝插件 ',
  'scene' => 2,
  'icon' => 'logo.jpg',
  'status' => '1',
  'config' => 
  array (
    0 => 
    array (
      'name' => 'alipay_account',
      'label' => '支付宝帐户',
      'type' => 'text',
      'value' => 'xnbangbang@163.com',
    ),
    1 => 
    array (
      'name' => 'alipay_key',
      'label' => '交易安全校验码',
      'type' => 'text',
      'value' => 'pzxeozl0p94izwso8fabkwx9qkffh75w',
    ),
    2 => 
    array (
      'name' => 'alipay_partner',
      'label' => '合作者身份ID',
      'type' => 'text',
      'value' => '2088521421873197',
    ),
    3 => 
    array (
      'name' => 'alipay_private_key',
      'label' => '秘钥',
      'type' => 'textarea',
      'value' => '',
    ),
    4 => 
    array (
      'name' => 'alipay_pay_method',
      'label' => ' 选择接口类型',
      'type' => 'select',
      'value' => '2',
      'option' => 
      array (
        1 => '使用担保交易接口',
        2 => '使用即时到帐交易接口',
      ),
    ),
  ),
);
?>