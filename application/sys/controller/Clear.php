<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 公共功能控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\sys\controller;

use think\Cache;
use think\Controller;

class Clear extends Controller
{


    /**
     * 缓存清理
     * @return mixed
     */
    public function index()
    {

        return $this->fetch();
    }


    /**
     * 清空模块缓存
     * @param $module
     * @return mixed
     */
    public function moduleCache($module)
    {


    }


    /**
     * 清除标签缓存
     * @param $tag
     * @return mixed
     */
    public function tagCache($tag)
    {
        if( strpos($tag,':') === 0){
            $method = substr($tag,1);
            return $this->$method();
        }
        $clear = Cache::clear($tag);
        if( $clear === true ){
            $this->success('清理成功!');
        }
        $this->error($clear);
    }


    /**
     * 清空系统缓存
     */
    public function clear()
    {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            Cache::clear();
            $this->clearTempCache();
            $this->success(lang('clear_success'));
        } else {
            $this->error(lang('clear_error'));
        }
    }


    /**
     * 清除商品图片缓存
     * @param string|int $goodsId 商品id
     * @return mixed
     */
    public function clearGoodsThumb($goodsId = '')
    {
        $goodsId = empty($goodsId) ? '' : $goodsId.'/';
        $dirName = str_replace('\\', '/', UPLOADS_PATH.'/goods/thumb/'.$goodsId);
        $op = dir($dirName);
        try{
            while (false != ($item = $op->read())) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                if (is_dir($op->path . '/' . $item)) {
                    $path = $op->path . '/' . $item;
                    $pathOP = dir($path);
                    while(false != ($item2 = $pathOP->read())){
                        if ($item2 == '.' || $item2 == '..') {
                            continue;
                        }
                        unlink($path . '/' . $item2);
                    }
                    rmdir($op->path . '/' . $item);
                } else {
                    dump($item);
                    unlink($op->path . '/' . $item);
                }

            }
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }
    }


    /**
     * 清空模板缓存文件
     * @return mixed
     */
    public function clearTempCache()
    {
        $dirName = TEMP_PATH;
        $op = dir($dirName);
        try{
            while (false != ($item = $op->read())) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                if (is_dir($op->path . '/' . $item)) {
                    $path = $op->path . '/' . $item;
                    $pathOP = dir($path);
                    while(false != ($item2 = $pathOP->read())){
                        if ($item2 == '.' || $item2 == '..') {
                            continue;
                        }
                        unlink($path . '/' . $item2);
                    }
                    rmdir($op->path . '/' . $item);
                } else {
                    unlink($op->path . '/' . $item);
                }

            }
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }
    }

}