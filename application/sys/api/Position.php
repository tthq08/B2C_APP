<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/13
 * Time: 上午11:17
 */

namespace app\sys\api;

use app\sys\model\Position as PositionModel;

class Position
{


    /**
     * 搜索推荐位
     * @param $search
     * @return mixed
     */
    public function searchPosition($search)
    {


    }


    /**
     * 获取类型名称
     * @param $type
     * @return mixed
     */
    public function getTypeName($type)
    {
        switch ($type){
            case 1:
                return '商品';
                break;
            case 2:
                return '门户内容';
                break;
        }
    }







}