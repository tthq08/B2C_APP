<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/9
 * Time: 上午11:55
 */
namespace app\web\api;

use think\Db;

class Common
{

    /**
     * 获取栏目链接
     * @param int $id 栏目id
     * @return string
     */
    public function columnUrl($id)
    {
        $url = getTableValue('web_cate','id='.$id,'cat_url');
        if( empty($url) ){
            $url = '';
        }
        return $url;
    }


    /**
     * 获取内容链接
     * @param $id
     * @return string
     */
    public function contentUrl($id)
    {
        $url = getTableValue('web_content','id='.$id,'cat_url');
        if( empty($url) ){
            $url = '';
        }
        return $url;
    }


    /**
     * 获取子栏目
     * @param $id
     * @param $reset
     * @return mixed
     */
    public function columnList($id,$reset = false)
    {
        if( empty(cache('columnList:'.$id)) || $reset == true ){
            $where = '`pid` = '.$id;
            $list = Db::name('web_cate')->where($where)->order('sort asc')->select();
            cache('columnList:'.$id,$list,'','web-column');
            return $list;
        }else{
            return cache('columnList:'.$id);
        }
    }


    /**
     * 通过参数搜索栏目列表
     * @param $search
     * @return mixed
     */
    public function searchColumn($search)
    {
        $where = '`name` like "%'.$search.'%" or `en_name` like "%'.$search.'%" or `id` = '.$search;

        $data = Db::name('web_cate')->where($where)->order('sort asc')->select();
        return $data;
    }


    /**
     * 通过参数搜索内容列表
     * @param $search
     * @return mixed
     */
    public function searchContent($search)
    {
        $where = '`title` like "%'.$search.'%" or `shorttitle` like "%'.$search.'%" or `id` = '.$search.'';

        $data = Db::name('web_content')->where($where)->select();
        foreach ($data as $key =>$content){
            $data[$key]['name'] = $content['title'];
        }
        return $data;
    }


    /**
     * 获取栏目详情
     * @param $id
     * @param $constraint
     * @return mixed
     */
    public function columnInfo($id,$constraint = false)
    {
        if( empty(cache('columnInfo:'.$id)) || $constraint == true ) {
            $data = Db::name('web_cate')->find($id);
            cache('columnInfo:'.$id,$data,'','web-column');
        }else{
            $data = cache('columnInfo:'.$id);
        }
        return $data;
    }


    /**
     * 获取内容详情
     * @param $id
     * @param $constraint
     * @return mixed
     */
    public function contentInfo($id,$constraint = false)
    {
        if( empty(cache('contentInfo:'.$id,'','','web')) || $constraint == true ) {
            $data = Db::name('web_content')->find($id);
            cache('contentInfo:'.$id,$data,'','web-content');
        }else{
            $data = cache('contentInfo:'.$id,'','','web');
        }
        return $data;
    }


    /**
     * 获取前端子栏目列表
     * @param $pid
     * @return mixed
     */
    public function cate_list($pid,$limit = 10,$condition = '',$order = '')
    {

        $where = '`pid`='.$pid.' and `status` = 1';
        $list = Db::name('web_cate')->where($condition)->where($where)->order($order)->limit($limit)->cache()->select();

        return $list;
    }


    /**
     * 获取前端文章列表
     * @param $cid
     * @return array
     */
    public function content_list($cid,$limit = 10,$condition = '',$order = '')
    {
        // dump($order);
        $where = '`cid`='.$cid.' and `status` = 1 and `trash` = 0';
        $list = Db::name('web_content')->where($condition)->where($where)->order($order)->limit($limit)->cache()->select();

        return $list;
    }

}