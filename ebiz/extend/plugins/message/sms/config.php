<?php
 return array (
  'code' => 'sms',
  'name' => '手机短信',
  'version' => '1.0',
  'author' => 'Jungo',
  'desc' => 'SMS手机短信',
  'icon' => 'logo.png',
  'scene' => 0,
  'status' => 1,
  'config' => 
  array (
    0 => 
    array (
      'name' => 'sms_api',
      'label' => '接口地址',
      'type' => 'text',
      'value' => '',
    ),
    1 => 
    array (
      'name' => 'sms_key',
      'label' => '用户标志',
      'type' => 'text',
      'value' => '',
    ),
    2 => 
    array (
      'name' => 'sms_secret',
      'label' => '用户密钥',
      'type' => 'text',
      'value' => '',
    ),
    3 => 
    array (
      'name' => 'sms_sign_key',
      'label' => '短信签名串',
      'type' => 'text',
      'value' => '',
    ),
    4 => 
    array (
      'name' => 'sms_from_num',
      'label' => '发送号码',
      'type' => 'text',
      'value' => '',
    ),
  ),
);
?>