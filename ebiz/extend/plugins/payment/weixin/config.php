<?php
 return array (
  'code' => 'weixin',
  'name' => '微信支付',
  'version' => '1.0',
  'author' => 'jungo',
  'desc' => '微信支付',
  'icon' => 'logo.jpg',
  'scene' => 0,
  'status' => '1',
  'config' => 
  array (
    0 => 
    array (
      'name' => 'appid',
      'label' => '绑定支付的APPID',
      'type' => 'text',
      'value' => 'wx558c4fb3d755de0c',
    ),
    1 => 
    array (
      'name' => 'mchid',
      'label' => '商户号',
      'type' => 'text',
      'value' => '1229374402',
    ),
    2 => 
    array (
      'name' => 'key',
      'label' => '商户支付密钥',
      'type' => 'text',
      'value' => '0zZytuBPQI9dinvPpzg2VCpH7KzoClBJ',
    ),
    3 => 
    array (
      'name' => 'appsecret',
      'label' => '公众帐号secert（仅JSAPI支付的时候需要配置)',
      'type' => 'text',
      'value' => 'ac6f32a3af1411180896c89b5f18fabb',
    ),
  ),
);
?>