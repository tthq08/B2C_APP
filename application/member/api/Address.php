<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/30
 * Time: 上午9:24
 */

namespace app\member\api;


use think\Db;

class Address
{

    /**
     * 后台查询所有用户地址
     * @param string $sort
     * @param string $condition
     * @return mixed
     */
    public function allAddress($sort = '',$condition = '')
    {
        $list = Db::name('user_address')->where($condition)->order($sort)->paginate(10);
        $data = $list->items();
        foreach ($data as $key=>$address){
            $user = api('member','User','userInfo',[$address['user_id'],'id,email,nickname']);
            $data[$key]['user'] = $user;
        }
        return ['page'=>$list->render(),'data'=>$data,'total'=>$list->total()];
    }


    /**
     * 获取指定条件所有地址
     * @param $condition
     * @return mixed
     */
    public function addressList($condition = '')
    {
        $data = Db::name('user_address')->where($condition)->select();
        foreach ($data as $key=>$address){
            $user = api('member','User','userInfo',[$address['user_id'],'id,email,nickname']);
            $data[$key]['user'] = $user['nickname'].'('.$user['email'].')';
        }
        return $data;
    }


    /**
     * 地址删除
     * @param $id
     * @return mixed
     */
    public function deleteAddress($id)
    {
        try{
            Db::name('user_address')->where('id','in',$id)->delete();
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }

}