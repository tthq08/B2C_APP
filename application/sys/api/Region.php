<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： region操作类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\api;

use think\Db;

class Region
{
    protected $_DB;

    public function __construct()
    {
        $this->_DB = Db::name('region');
    }

    /**
     * 获取城市列表
     * @param $pid
     * @return array
     */
    public function getRegionList($pid = 0)
    {
        if( empty(cache('regionList:'.$pid)) ){
            $cityList = $this->_DB->where('parent_id',$pid)->select();
            cache('regionList:'.$pid,$cityList,'','system-region');
        }else{
            $cityList = cache('regionList:'.$pid);
        }
        return $cityList;
    }

}