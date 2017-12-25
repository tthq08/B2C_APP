<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/7
 * Time: 上午9:45
 */

namespace app\shop\api;


use think\Db;
use think\Log;

class FeatureGroup
{

    /**
     * 获取当前分组的信息
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        $info = Db::name('shop_feature_group')->where('trash',0)->where('id',$id)->find();
        return $info;
    }


    /**
     * 获取当前特征分类下的分组列表
     * @param $type
     * @param int $pageRow
     * @return mixed
     */
    public function pageGroupList($type,$pageRow = 10)
    {
        if( empty($type) )
        {
            return null;
        }
        $condition = [
            'type' => $type,
            'trash' => 0,
        ];
        if ( empty($pageRow) )
        {
            $pageRow = tb_config('list_rows',1);
        }
        $list = Db::name('shop_feature_group')->where($condition)->order('id desc')->paginate($pageRow);
        return $list;
    }


    /**
     * 获取当前特征分类下的所有分组(用于商品分类进行选择)
     * @param $type
     * @return mixed
     */
    public function featureSelectList($type)
    {
        if( empty($type) )
        {
            return null;
        }
        $condition = [
            'type' => $type,
            'trash' => 0,
        ];
        if ( empty($pageRow) )
        {
            $pageRow = tb_config('list_rows',1);
        }
        $list = Db::name('shop_feature_group')->where($condition)->order('sort desc')->order('id desc')->field('id,name')->select();
        $dataList = [];
        $dataList[0] = '取消';
        foreach ($list as $k=>$value){
            $dataList[$value['id']] = $value['name'];
        }
        return $dataList;
    }


    /**
     * 获取所有项
     * @return mixed
     */
    public function itemListAll($type)
    {
        $funtionName = 'type'.$type.'ListAll';

        return $this->$funtionName();
    }


    /**
     * 获取所有属性
     * @return mixed
     */
    public function type1ListAll()
    {
        $condition = [
            'trash' => 0,
        ];
        $list = Db::name('shop_attribute')->where($condition)->select();
        foreach ($list as $key=>$item)
        {
            $list[$key]['name'] = $item['attr_name'];
        }
        return $list;
    }


    /**
     * 通过名称和分类获取分组信息
     * @param $name
     * @param $type
     * @return mixed
     */
    public function getFromName($name, $type)
    {
        $condition = [
            'name' => $name,
            'type' => $type,
            'trash' => 0
        ];
        $featureGroup = Db::name('shop_feature_group')->where($condition)->find();
        return $featureGroup;
    }


    /**
     * 插入分组
     * @param $data
     * @return array
     */
    public function insert($data)
    {
        // 检测该分组是否存在
        $isExist = $this->getFromName($data['name'],$data['type']);
        if ( empty($isExist) )
        {
            if( is_array($data['item']) )
            {
                $data['item'] = implode(',',$data['item']);
            }
            $data['sort'] = empty($data['sort']) ? '100' : $data['sort'];
            $data['status'] = empty($data['status']) ? 0 : $data['status'];
            $data['add_time'] = NOW_TIME;
            try{
                Db::name('shop_feature_group')->insert($data);
            }catch (\Exception $e)
            {
                Log::error(json_encode($e));
                return msg(0,$e->getMessage(),$e);
            }
            return msg(1,'添加成功!');
        }else{
            // 存在
            return msg(0,'该分组名称已存在!');
        }
    }


    /**
     * 更新分组
     * @param $id
     * @param $data
     * @return array
     */
    public function update($id,$data)
    {
        // 检测该分组是否存在
        $isExist = $this->get($id);
        if ( !empty($isExist) )
        {
            if( is_array($data['item']) )
            {
                $data['item'] = implode(',',$data['item']);
            }
            $data['sort'] = empty($data['sort']) ? '100' : $data['sort'];
            $data['status'] = empty($data['status']) ? 0 : $data['status'];
            $data['update_time'] = NOW_TIME;
            try{
                Db::name('shop_feature_group')->where('id',$id)->update($data);
            }catch (\Exception $e)
            {
                Log::error(json_encode($e));
                return msg(0,$e->getMessage(),$e);
            }
            return msg(1,'更新成功!');
        }else{
            // 存在
            return msg(0,'该分组不存在,请检查后重新输入!');
        }
    }


    /**
     * 获取分组内的成员
     * @param int $id 分组id
     * @param bool $alldata 是否取出所有数据
     * @return mixed
     */
    public function itemList($id,$alldata = false)
    {
        $group = $this->get($id);
        $list = explode(',',$group['item']);
        $itemList = [];
        foreach ($list as $key=>$item)
        {
            if( $alldata == false ){
                $itemList[$item] = getFeatureGroupName($item);
            }else{
                $itemList[$item] = $this->getItemData($group['type'],$item);
            }
        }
        $itemList = array_filter($itemList);
        return $itemList;
    }


    /**
     * 获取成员数据
     */
    public function getItemData($type,$item)
    {
        if( $type == 1 )
        {
            return api('shop','Goods','getAttr',[$item]);
        }elseif ( $type == 2 )
        {
            return api('shop','Goods','getSpec',[$item]);
        }elseif( $type == 3 )
        {
            return api('shop','Goods','getBrand',[$item]);
        }
    }



}