<?php
/**
 * Created by PhpStorm.
 * User: jungo
 * Date: 2017/5/19
 * Time: 15:48
 */

namespace app\shop\admin;

use app\sys\controller\AdminBase;
use think\Db;

class Shipping extends AdminBase
{
    /**
     * 物流公司列表
     *
     */
    public function index(){

        //读取列表
        $shippingCompanyList1 = Db::name('shop_shipping')->where('enabled',1)->select();
        $shippingCompanyList0 = Db::name('shop_shipping')->where('enabled',0)->select();
        $this->assign('shippingCompany1',$shippingCompanyList1);
        $this->assign('shippingCompany0',$shippingCompanyList0);
        return $this->fetch();
    }



    /**
     * 删除物流公司
     * @param int $id
     * @return mixed
     */
    public function del($id)
    {

    }

    /**
     * 修改物流公司状态
     * @param int $id 表ID
     * @return mixed
     */
    public function updateEnabled($id)
    {
        $id = intval($id);
        if( empty($id) ){
            return $this->error('ID错误');
        }
        //修改状态
        //获取当钱状态
        $enabled = getTableValue('shop_shipping','id='.$id,'enabled');
        $uEnabled = $enabled == 1 ? 0 : 1;
        $update = Db::name('shop_shipping')->where('id',$id)->update(['enabled'=>$uEnabled]);
        if( $update === false ){
            return $this->error('修改失败，请重试');
        }
        $msg = [
            'info' => '修改成功',
            'enabled' => $uEnabled,
        ];
        return $this->success($msg);
    }

}