<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/8/3
 * Time: 下午3:59
 */

namespace app\sys\api;


use think\Db;

class Domain
{

    /**
     * 查看域名库中是否存在该域名
     * @param $domain
     * @return mixed
     */
    public function getDomainInfo($domain)
    {
        $info = Db::name('domain_bind')->where('domain',$domain)->where('status',1)->find();

        return $info;
    }



    /**
     * 通过类型和id查看域名库中是否存在该域名
     * @param $domain
     * @return mixed
     */
    public function getDomainInfoFromType($type,$id)
    {
        $info = Db::name('domain_bind')->where('bind_type',$type)->where('bind_id',$id)->find();

        return $info;
    }


    /**
     * 插入到域名库中
     * @param $data
     * @return mixed
     */
    public function insert($data)
    {
        $insert = Db::name('domain_bind')->insertGetId($data);
        return $insert;
    }


    /**
     * 插入到dirurl中
     * @param $data
     */
    public function diyUrlInsert($url_sign, $real_url, $start_time = '',$end_time = '')
    {
        $diyUrlData = [
            'url_sign' => $url_sign,
            'real_url' => $real_url,
        ];
        $realUrl = array_filter(explode('/',$diyUrlData['real_url']));
        if( preg_match('/[a-zA-Z0-9_]*\.php/',$realUrl[1]) )
        {
            $realUrl[1] = '';
        }
        $diyUrlData['real_url'] = implode('/',$realUrl);

        $diyUrlData['start_time'] = strtotime($start_time);
        $diyUrlData['end_time'] = strtotime($end_time);

        $insert = Db::name('admin_diy_url')->insert($diyUrlData);
    }


    /**
     * 修改到域名库中
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data)
    {
        $update = Db::name('domain_bind')->where('id',$id)->update($data);
    }


    /**
     * 插入到dirurl中
     * @param $bind_id
     * @param $data
     */
    public function diyUrlUpdate($url_sign, $real_url, $start_time = '',$end_time = '')
    {
        $diyUrlData = [
            'url_sign' => $url_sign,
        ];

        $diyUrlData['start_time'] = strtotime($start_time);
        $diyUrlData['end_time'] = strtotime($end_time);
        // 查看是否存在,不存在就增加
        $diyurl = Db::name('admin_diy_url')->where('real_url',$real_url)->find();
        if( empty($diyurl) )
        {
            $this->diyUrlInsert($url_sign, $real_url, $start_time, $end_time);
        }

        Db::name('admin_diy_url')->where('real_url',$real_url)->update($diyUrlData);
    }


    /**
     * 通过类型和类型id查找绑定信息
     * @param int $type 类型
     * @param int $type_id 类型id
     * @return array
     */
    public function domainFromType($type,$type_id)
    {
        $data = Db::name('domain_bind')->where('bind_type',$type)->where('bind_id',$type_id)->where('status',1)->find();
        return $data;
    }

}