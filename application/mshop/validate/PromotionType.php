<?php
namespace app\shop\validate;
use think\Validate;

class PromotionType extends Validate
{
    protected $rule = [
        'title' => 'require',
        'controller' => 'require',
        'table' => 'require',
    ];



}