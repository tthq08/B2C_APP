<?php
 return array (
  'code' => 'paypal',
  'name' => 'PayPal支付',
  'version' => '1.0',
  'author' => 'Yujh',
  'desc' => 'PayPal支付 ',
  'scene' => 0,
  'icon' => 'logo.png',
  'status' => '1',
  'config' => 
  array (
    0 => 
    array (
      'name' => 'account',
      'label' => '商户账号',
      'type' => 'text',
      'value' => 'shop@jungo.com',
    ),
    1 => 
    array (
      'name' => 'client_id',
      'label' => 'Client ID',
      'type' => 'text',
      'value' => '',
    ),
    2 => 
    array (
      'name' => 'secret',
      'label' => 'Secret',
      'type' => 'text',
      'value' => '',
    ),
    3 => 
    array (
      'name' => 'is_sandbox',
      'label' => '开启沙箱环境',
      'type' => 'radio',
      'option' => 
      array (
        0 => 'NO',
        1 => 'YES',
      ),
      'value' => '1',
    ),
  ),
);
?>