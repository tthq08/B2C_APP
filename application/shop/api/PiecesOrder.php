<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/3
 * Time: 下午3:23
 */

namespace app\shop\api;


use app\member\api\User;
use think\Db;
use think\Log;

class PiecesOrder
{

    /**
     * 获取拼团订单详情
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        $info = Db::name('shop_pieces_order')->where('id',$id)->find();
        return $info;
    }


    /**
     * 订单退还
     * @param $id
     * @return mixed
     */
    public function returnOrder($id)
    {
        $order = $this->get($id);
        if( empty($order) || !empty($order['order_id']) || $order['is_return'] == 1 ){
            return false;
        }
        // 退还金额
        $price = $order['payable_price'] + $order['postage'] - $order['change_mny'];
        // 退回用户余额
        Db::startTrans();
        try{
            Db::name('users')->where('id',$order['user_id'])->setInc('user_money',$price);
            User::account_log($order['user_id'],$price,0,0,'拼团订单退还金额!',$order['pieces_sn']);
            Db::name('shop_pieces_order')->where('id',$order['id'])->update(['is_return'=>1,'status'=>2]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            Log::error($e);
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 拼团订单删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        try{
            Db::name('shop_pieces_order')->delete($id);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }



    /**
     * 获取后台订单列表
     * @param $condition
     * @param $sort
     * @return mixed
     */
    public function orderAdminList($condition = '',$sort = '')
    {
        $list = Db::name('shop_pieces_order')->where($condition)->order($sort)->order('id asc')->paginate();
        return $list;
    }


    /**
     * 获取小团成员
     * @param $pieces
     * @param $head
     * @return array
     */
    public function smallPiecesList($pieces,$head)
    {
        $list = Db::name('shop_pieces_order')->where('`pieces_id` = '.$pieces)->where('`head_id`='.$head)->order('head_id asc')->select();
        return $list;
    }

}