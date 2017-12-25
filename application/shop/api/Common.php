<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/1
 * Time: 上午10:40
 */

namespace app\shop\api;


use think\Db;

class Common
{

    /**
     * 获取自定义导航列表
     * @return array
     */
    public function navList()
    {
        // 获取自定义导航列表
        if( empty(cache('navList')) )
        {
            // 获取
            $navList = Db::name('shop_nav')->where('is_show',1)->order('sort')->select();
            cache('navList',$navList);
        }else{
            $navList = cache('navList');
        }
        return $navList;
    }


}