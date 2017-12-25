<?php

// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 韩国SweetTracker物流查询接口
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace plugins\shipping\sweettracker;

use plugins\shipping\Abkd;

class Sweettracker extends Abkd
{

    public $EBusinessID;
    public $AppKey;

    public function __construct()
    {
        $config = include "config.php";
        $config_val = $config['config'];
        foreach ($config_val as $key => $config) {
            $config_value[$config['name']] = $config['value'];
        }
        $this->AppKey = $config_value['key'];
    }
    /**
     * 获取快递追踪轨迹
     * @param string $scode 快递编号
     * @param string $ssn 快递单号
     * @return array
     */
    public function getkdTrail($scode,$ssn)
    {
        // TODO: Implement getkdTrail() method.
        //接口地址
        $url = "http://tracking.sweettracker.net/tracking";
        $param = ['t_key'=>$this->AppKey,'t_code'=>$scode,'t_invoice'=>$ssn];
        $param = http_build_query($param);
        // dump($param);
        $result = httpRequest($url.'?'.$param,'GET');
        $result = json_decode($result,true);

        return $result;
    }

    // 获取快递公司列表
    public function getCompany($enabled=1)
    {
        $url = 'http://tracking.sweettracker.net/companylist';
        $param = ['t_key'=>$this->AppKey];
        $result = httpRequest($url,'POST',$param);
        $result = json_decode($result,true);
        $companys = $result['Company'];
        foreach ($companys as $key => $com) {
            $companys[$key]['name'] = $com['Name'];
            $companys[$key]['code'] = $com['Code'];
            unset($companys[$key]['Name'],$companys[$key]['Code']);
        }

        return $companys;
    }

    // 获取指定代码的公司信息
    public function getCompanyByCode($code='')
    {
        $url = 'http://tracking.sweettracker.net/companylist';
        $param = ['t_key'=>$this->AppKey];
        $result = httpRequest($url,'POST',$param);
        $result = json_decode($result,true);
        $companys = $result['Company'];
        foreach ($companys as $key => $com) {
            $companys[$com['Code']]['name'] = $com['Name'];
            $companys[$com['Code']]['code'] = $com['Code'];
            unset($companys[$key]['Name'],$companys[$key]['Code']);
        }

        return $companys[$code];
    }

    public function getCompanyBySn($sn)
    {
        $url = "http://tracking.sweettracker.net/recommend?t_key={$this->AppKey}&t_invoice={$sn}";

        $result = httpRequest($url,'GET');
        $result = json_decode($result,true);

        return $result['Recommend'];
    }


    public function switchComp($code='')
    {
        $msg = [
            'info' => '不支持切换公司启用状态',
            'enabled' => 1,
        ];
        return $msg;
    }

}