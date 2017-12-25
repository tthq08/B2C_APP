<?php
/**
 * Created by PhpStorm.
 * User: TBMALL
 * Date: 2017/4/10
 * Time: 10:13
 */

namespace app\shop\validate;


use think\Validate;

class OrderProm extends Validate
{
    protected $rule = [
        'title' => 'require',
        'money' => 'require',
        'discount_type' => 'require',
        'expression' => 'require|number',
        'use_coupon' => 'number|between:0,1',
        'use_integral' => 'number|between:0,1',
        'use_shopping_coupon' => 'number|between:0,1',
        'user_group' => 'require',
        'start_time' => 'require|dateFormat:Y-m-d H:i:s',
        'end_time' => 'require|dateFormat:Y-m-d H:i:s',
    ];

}