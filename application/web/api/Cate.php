<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/8/22
 * Time: 上午10:23
 */

namespace app\web\api;


use think\Db;

class Cate
{

    /**
     * 获取子栏目列表
     * @param int $pid
     * @param int $limit
     * @return mixed
     */
    public function getChCateList($pid, $limit = 0)
    {
        $limit = empty($limit) ? '' : $limit;

        $list = Db::name('web_cate')->where('pid',$pid)->order(['sort'=>'ASC','id'=>'DESC'])->limit($limit)->select();
        return $list;
    }


    /**
     * 获取栏目信息
     * @param int $id
     * @return array
     */
    public function get($id)
    {
        $info = Db::name('web_cate')->where('id',$id)->find();
        return $info;
    }

}