<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 物流查询接口
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace plugins\shipping;

class Shipping
{

    protected $shipping_code;
    protected $kdC;

    public function __construct($shipping_code = 'kdniao')
    {
        //查看
        $this->shipping_code = $shipping_code;

        //实例化快递接口类
        $kdPsr = "plugins\\shipping\\".$this->shipping_code.'\\'.ucfirst($this->shipping_code);
        $this->kdC = new $kdPsr();
        if( class_exists($kdPsr) ){

        }else{
            return '接口不存在';
        }
    }

    public function getkdTrail($scode,$ssn)
    {
        //获取快递追踪轨迹
        $re = $this->kdC->getkdTrail($scode,$ssn);
        return $re;
    }

    public function getCompany($enabled=1)
    {
        //获取快递公司列表
        $re = $this->kdC->getCompany($enabled);
        return $re;
    }

    public function getCompanyByCode($code)
    {
        //获取指定编号的快递公司信息
        $re = $this->kdC->getCompanyByCode($code);
        return $re;
    }

    public function getCompanyBySn($SN)
    {
        //根据快递单号获取快递公司信息
        $re = $this->kdC->getCompanyBySn($SN);
        return $re;
    }

    // 更换快递公司启用状态
    public function switchComp($code='')
    {
        $re = $this->kdC->switchComp($code);
        return $re;
    }
}