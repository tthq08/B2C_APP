<?php

// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 快递鸟物流查询接口
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace plugins\shipping\kdniao;

use plugins\shipping\Abkd;

class Kdniao extends Abkd
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
        $this->EBusinessID = $config_value['EBusinessID'];
        $this->AppKey = $config_value['AppKey'];
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
        $ReqURL = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';
        $requestData= "{'OrderCode':'','ShipperCode':'{$scode}','LogisticCode':'{$ssn}'}";

        $datas = array(
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->AppKey);
        $result=$this->sendPost($ReqURL, $datas);
        $info = json_decode($result,1);
        return $info;
    }

    // 获取快递公司列表
    public function getCompany($enabled=1) {
        $companys = db('shop_shipping') ->field('code,name') ->where('enabled',$enabled) ->select();
        return $companys;
    }

    public function getCompanyByCode($code='')
    {
        $companys = db('shop_shipping') ->where('enabled',1) ->column('id,code,name','code');
        return $companys[$code];
    }

    public function switchComp($code='')
    {
        $enabled = getTableValue('shop_shipping',['code'=>$code],'enabled');
        $uEnabled = $enabled == 1 ? 0 : 1;
        $update = Db::name('shop_shipping')->where('code',$code)->update(['enabled'=>$uEnabled]);
        if( $update === false ){
            return $this->error('修改失败，请重试');
        }
        $msg = [
            'info' => '修改成功',
            'enabled' => $uEnabled,
        ];
        return $msg;
    }

    // 识别单号
    public function getCompanyBySn($sn='')
    {
        $ReqURL = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';
        $requestData= "{'LogisticCode':'{$sn}'}";

        $datas = array(
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => '2002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->AppKey);
        $result=$this->sendPost($ReqURL, $datas);
        $info = json_decode($result,1);
        return $info;
    }

    /**
    *  post提交数据
    * @param  string $url 请求Url
    * @param  array $datas 提交的数据
    * @return url响应返回的html
    */
    protected function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    protected function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }

}