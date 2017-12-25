<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/10
 * Time: 下午4:24
 */

namespace app\sys\api;

use app\sys\model\FriendlyLink;

class Comm
{

    /**
     * 友情链接列表
     * @param $limit
     * @return array
     */
    public function friendlyLink($limit)
    {
        $position = new FriendlyLink();
        return $position->lists($limit);
    }

}