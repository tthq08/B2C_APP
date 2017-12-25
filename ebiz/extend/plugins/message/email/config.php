<?php
 return array (
  'code' => 'email',
  'name' => 'Email',
  'version' => '1.0',
  'author' => 'Jungo',
  'desc' => 'E-mail邮件',
  'icon' => 'logo.png',
  'scene' => 0,
  'status' => 1,
  'config' => 
  array (
    0 => 
    array (
      'name' => 'mail_smtp_serv',
      'label' => 'SMTP服务器',
      'type' => 'text',
      'value' => 'smtp.163.com',
    ),
    1 => 
    array (
      'name' => 'mail_smtp_port',
      'label' => 'SMTP端口',
      'type' => 'text',
      'value' => '25',
    ),
    2 => 
    array (
      'name' => 'mail_account',
      'label' => '发送账号',
      'type' => 'text',
      'value' => 'phpupil@163.com',
    ),
    3 => 
    array (
      'name' => 'mail_password',
      'label' => '验证密码',
      'type' => 'text',
      'value' => 'phpupil1008',
    ),
  ),
);
?>