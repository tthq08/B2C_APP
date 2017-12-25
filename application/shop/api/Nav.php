<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/14
 * Time: 上午10:47
 */

namespace app\shop\api;


use think\Db;
use think\Log;

class Nav
{
    /**
     * 获取广告位信息
     * @param $id
     * @return array
     */
    public function getPosition($id)
    {
        if( empty(cache('nav_position:'.$id)) ){
            $info = Db::name('shop_nav_position')->where('id',$id)->find();
            if( empty($info) ){
                return null;
            }
            cache('nav_position:'.$id,$info,'','system-nav');
        }else{
            $info = cache('nav_position:'.$id);
        }
        return $info;
    }

    /**
     * 获取广告位信息
     * @param $position
     * @return array
     */
    public function getFromPosition($position)
    {
        if( empty(cache('nav_position:'.$position)) ){
            $info = Db::name('shop_nav_position')->where('position',$position)->find();
            if( empty($info) ){
                return null;
            }
            cache('nav_position:'.$position,$info,'','system-nav');
        }else{
            $info = cache('nav_position:'.$position);
        }
        return $info;
    }


    /**
     * 保存导航位
     * @param $data
     * @return mixed
     */
    public function savePosition($data)
    {
        Db::startTrans();
        try{
            $insert = Db::name('shop_nav_position')->insert($data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            Log::error(json_encode($e));
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 更新广告位
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updatePosition($id,$data)
    {
        Db::startTrans();
        try{
            $update = Db::name('shop_nav_position')->where('id',$id)->update($data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            Log::error(json_encode($e));
            return $e->getMessage();
        }
        if( !empty(cache('nav_position:'.$id)) ){
            $cacheData = cache('nav_position:'.$id);
            $data = array_merge($cacheData,$data);
            cache('nav_position:'.$id,$data,'','system-nav');
        }
        return true;
    }


    /**
     * 删除导航位
     * @param $id
     * @return mixed
     */
    public function delPosition($id)
    {
        try{
            $del = Db::name('shop_nav_position')->where('id','IN',$id)->delete();

            cache('nav_position:'.$id,null,'','system-nav');
        }catch (\Exception $e){
            Log::error(json_encode($e));
            return false;
        }
        return true;
    }

}