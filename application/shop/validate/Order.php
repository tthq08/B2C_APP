<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/4/10
 * Time: 10:13
 */

namespace app\shop\validate;


use think\Validate;

class Order extends Validate
{
    protected $rule = [
        'consignee' => 'require',
        'phone' => 'require|number|length:11',
        'province' => 'require|number',
        'city' => 'require|number',
        'district' => 'number|number',
        'address' => 'require',
    ];

}