<?php
/**
 * Created by PhpStorm.
 * User: 吴跃忠
 * Date: 2017/4/22
 * Time: 9:54
 */

namespace app\sys\controller;

use think\Db;
class Api
{

    /**
     * 获取子类地址
     * @param $pid 父级ID 默认为0
     * @return array
     */
    public static function getChildAddress($pid = 0,$level=1){
        $addressList = Db::name('region')->where('parent_id',intval($pid))->select();
        if( $addressList !== '' ){
            return $addressList;
        }
       return false;
    }

}