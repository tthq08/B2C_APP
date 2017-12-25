<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/18
 * Time: 上午9:42
 */

namespace app\sys\model;


use think\Model;
use think\Db;
use think\Exception;

class FriendlyLink extends Model
{
    protected $table = 'sys_friendly_link';

    public function __construct()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->table = config('database.prefix').'sys_friendly_link';
    }


    /**
     * 获取列表
     * @param $limit
     * @return array
     */
    public function lists($limit)
    {
        $where = ['trash'=>0,'status'=>1];
        $list = $this->where($where)->order('sort asc,id desc')->limit($limit)->select();
        return $list;
    }


    /**
     * 获取后台显示列表
     * @return array
     */
    public function adminList()
    {
        $where = '`trash`=0';
        $listRow = tb_config('list_row',1);
        $list = $this->where($where)->order('sort asc,id desc')->paginate($listRow);
        return $list;
    }


    /**
     * 保存友情链接
     * @param $data
     * @return array
     * @throws Exception
     */
    public function saveLinks($data)
    {
        $saveData = [
            'name' => $data['name'],
            'description' => empty($data['description']) ? '' : $data['description'],
            'url' => empty($data['url']) ? '' : $data['url'],
            'img' => empty($data['img']) ? '0' : $data['img'],
            'sort' => empty($data['sort']) ? '0' : $data['sort'],
            'status' => empty($data['status']) ? 1 : $data['status'],
        ];
        try{
            if( empty($data['id']) ){
                $this->save($saveData);
            }else{
                $position = self::get($data['id']);
                if( empty($position) ){
                    throw new Exception('当前友情链接不存在!');
                }
                Db::name('sys_friendly_link')->where('id',$data['id'])->update($saveData);
            }
        }catch (\Exception $e){
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }



    /**
     * 删除友情链接
     * @param $id
     * @return mixed
     */
    public function deleteLinks($id)
    {
        try{
            // 删除推荐位
            $this->where('id',$id)->delete();
        }catch (\Exception $e){
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }


    /**
     * 批量删除推荐位
     * @param $ids
     * @return mixed
     */
    public function deletesLinks($ids)
    {
        try{
            $this->where('id','in',$ids)->delete();
        }catch (\Exception $e){
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }

}