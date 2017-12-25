<?php
/**
 * Created by PhpStorm.
 * User: TBMALL
 * Date: 2017/4/10
 * Time: 10:13
 */

namespace app\shop\validate;


use think\Validate;

class PanicBuying extends Validate
{
    protected $rule = [
        'title' => 'require',
        'goods' => 'require|number',
        'price' => 'require',
        'min_buy_num' => 'require|number',
        'max_buy_num' => 'require|number',
        'plus_buy_num' => 'require|number',
        'use_coupon' => 'number|between:0,1',
        'use_integral' => 'number|between:0,1',
        'use_shopping_coupon' => 'number|between:0,1',
        'add_shopping_car' => 'number|between:0,1',
        'add_user_integral' => 'number|between:0,1',
        'user_group' => 'require',
        'start_time' => 'require|dateFormat:Y-m-d H:i:s',
        'end_time' => 'require|dateFormat:Y-m-d H:i:s',
    ];

}