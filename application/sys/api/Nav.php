<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/22
 * Time: 下午4:17
 */

namespace app\sys\api;


use think\Db;
use think\Log;

class Nav
{
    /**
     * 导航位表名称
     * @var string
     */
    const POSITION_TABLE = 'sys_nav_position';

    /**
     * 导航表名称
     * @var string
     */
    const NAV_TABLE = 'sys_nav';


    /**
     * 当前导航位
     * @var int
     */
    protected $position_id = 0;


    /**
     * 使用html作为导航名称
     * @var boolean
     */
    protected $useHtmlName = false;


    /**
     * 获取条数
     * @var int
     */
    protected $limit = 10;


    /**
     * 开启分页
     * @var boolean
     */
    public $needPage = false;


    /**
     * Nav constructor.
     */
    public function __construct()
    {

    }


    /**
     * 设置positionId
     * @param $position
     * @return mixed
     */
    public function setPositionId($position)
    {
        $this->position_id = $position;
        return $this;
    }


    /**
     * 获取下级导航位
     * @param bool $status 是否限制状态
     * @return mixed
     */
    public function selectPositions($status = false,$getAll = false)
    {
        $where['trash'] = 0;
        if( !empty($this->position_id) ){
            $where['top_position']=$this->position_id;
        }else{
            $where['top_position']=0;
        }
        if( $status == true ){
            $where['status'] = 1;

        }
        $positions = Db::name(self::POSITION_TABLE)->where($where)->order('sort asc')->order('id desc');
        if( $getAll == true ){
            $positions = $positions->select();
        } elseif( $this->needPage == true ){
            $positions = $positions->paginate($this->limit);
        }else{
            $positions = $positions->select();
        }
        return $positions;
    }


    /**
     * 获取一级导航位
     * @param bool $status 是否限制状态
     * @return mixed
     */
    public function select1LevelPositions($status = false)
    {
        $where['trash'] = 0;
        if( empty($this->position_id) ){
            $where = ['level'=>1];
        }
        if( $status == true ){
            $where['status'] = 1;
        }
        $positions = Db::name(self::POSITION_TABLE)->where($where)->order('sort asc')->order('id desc');
        if( $this->needPage == true ){
            $positions = $positions->paginate($this->limit);
        }else{
            $positions = $positions->select();
        }
        return $positions;
    }



    /**
     * 获取上级导航位
     * @param bool $status 是否限制状态
     * @param bool $getAll 是否获取全部
     * @return mixed
     */
    public function selectTopPositions($status = false,$getAll = false)
    {
        if( $this->position_id == 0 ){
            return [];
        }
        $positionInfo = $this->getPosition($this->position_id);
        $topPositionInfo = $this->getPosition($positionInfo['top_position']);
        if( $status == true ){
            $where['status'] = 1;
            $where['trash'] = 0;
        }
        $where['top_position']=$topPositionInfo['top_position'];
        if( $getAll == true ){
            $positions = Db::name(self::POSITION_TABLE)->where($where)->order('sort asc')->order('id desc')->select();
        }elseif( $this->needPage == false ){
            $positions = Db::name(self::POSITION_TABLE)->where($where)->order('sort asc')->order('id desc')->paginate($this->limit);
        }else{
            $positions = Db::name(self::POSITION_TABLE)->where($where)->limit($this->limit)->order('sort asc')->order('id desc')->select();
        }
        return $positions;
    }


    /**
     * 获取导航位的详细信息
     * @return array
     */
    public function getPosition($id = '')
    {
        $id = empty($id) ? $this->position_id : $id;
        $where = ['id'=>$id,'trash'=>0];
        $position = Db::name(self::POSITION_TABLE)->where($where)->find();
        return $position;
    }


    /**
     * 获取导航详情
     * @param $id
     * @return mixed
     */
    public function getNav($id)
    {
        $where = ['id'=>$id,'trash'=>0];
        $nav = Db::name(self::NAV_TABLE)->where($where)->find();
        return $nav;
    }

    /**
     * 获取当前导航位的上级树
     * @return array
     */
    public function topTree()
    {
        if( empty($this->position_id) ){
            return [];
        }
        $position = $this->getPosition();
        $positionTree = $position['position_tree'];
        $positionTree = explode(',',$positionTree);
        $positionTreeArray = [];
        foreach ($positionTree as $key=>$positionId) {
            $positionTreeArray[$key] = $this->getPosition($positionId);
        }
        return $positionTreeArray;
    }


    /**
     * 获取当前导航位下的导航
     * @return mixed
     */
    public function selectNavs($limit = '')
    {
        $where = ['position'=>$this->position_id,'trash'=>0];
        if( empty($limit) ){
            $lists = Db::name(self::NAV_TABLE)->where($where)->order('sort asc')->order('id desc')->paginate(10);
        }else{
            $lists = Db::name(self::NAV_TABLE)->where($where)->limit($limit)->order('sort asc')->order('id desc')->select();
        }
        return $lists;
    }


    /**
     * 获取当前导航位下的导航
     * @return mixed
     */
    public function selectAllNavs($limit = '')
    {
        $where = ['trash'=>0];
        if( empty($limit) ){
            $lists = Db::name(self::NAV_TABLE)->where($where)->order('sort asc')->order('id desc')->paginate(10);
        }else{
            $lists = Db::name(self::NAV_TABLE)->where($where)->order('sort asc')->order('id desc')->limit($limit)->select();
        }
        return $lists;
    }


    /**
     * 获取当前导航位下的导航
     * @return mixed
     */
    public function selectNav($pid = 0)
    {
        $where = ['pid'=>$pid,'trash'=>0];
        $lists = Db::name(self::NAV_TABLE)->where($where)->order('sort asc')->order('id desc')->paginate(10);

        return $lists;
    }

    /**
     * 保存导航
     * @param $data
     * @return mixed
     */
    public function saveNav($data)
    {
        try{
            $install = Db::name(self::NAV_TABLE)->insertGetId($data);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 保存导航
     * @param $data
     * @return mixed
     */
    public function saveNavPosition($data)
    {
        $top_position = $this->getPosition($data['top_position']);
        $data['level'] = $top_position['level']+1;
        try{
            $install = Db::name(self::POSITION_TABLE)->insertGetId($data);
            if( empty($top_position['position_tree']) ){
                $top_position['position_tree'] = 0;
            }
            $position_tree = $top_position['position_tree'].','.$install;
            $update = Db::name(self::POSITION_TABLE)->where('id',$install)->update(['position_tree'=>$position_tree]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 更新导航信息
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateNav($id, $data)
    {
        try{
            $install = Db::name(self::NAV_TABLE)->where('id',$id)->update($data);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 更新导航信息
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateNavPosition($id, $data)
    {
        $top_position = $this->getPosition($data['top_position']);
        $data['position_tree'] = $top_position['position_tree'].','.$id;
        $data['level'] = $top_position['level']+1;
        try{
            $update = Db::name(self::POSITION_TABLE)->where('id',$id)->update($data);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 删除导航
     * @param $id
     * @return mixed
     */
    public function deleteNav($id)
    {
        try{
            $del = Db::name(self::NAV_TABLE)->where('id','in',$id)->update(['trash'=>1]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 删除导航位
     * @param $id
     * @return mixed
     */
    public function deleteNavPosition($id)
    {
        try{
            $del = Db::name(self::POSITION_TABLE)->where('id','in',$id)->update(['trash'=>1]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 设置导航字段值
     * @param $id
     * @param $field
     * @param $value
     * @return mixed
     */
    public function setNavField($id,$field,$value)
    {
        try{
            $update = Db::name(self::NAV_TABLE)->where('id',$id)->update([$field=>$value]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 设置导航字段值
     * @param $id
     * @param $field
     * @param $value
     * @return mixed
     */
    public function setNavPositionField($id,$field,$value)
    {
        try{
            $update = Db::name(self::POSITION_TABLE)->where('id',$id)->update([$field=>$value]);
        }catch (\Exception $e){
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 获取链接
     * @param $type
     * @param $param
     * @return mixed
     */
    public function getUrl($type, $param)
    {
        switch ($type){
            case 1:
                // 商品,从商品中取出链接
                $url = api('shop','Goods','goodsUrl',[$param]);
                break;
            case 2:
                // 商品分类,从商品分类中取出
                $url = api('shop','Goods','categoryUrl',[$param]);
                break;
            case 3:
                // 栏目,从栏目中取出
                $url = api('web','Common','columnUrl',[$param]);
                break;
            case 4:
                $url = api('web','Common','contentUrl',[$param]);
                break;
        }
        $url = empty($url) ? '' : $url;
        return $url;
    }



    /**
     * 通过模板调用标签获取导航位
     * @param $position
     * @return array
     */
    public function getPositionFromLogo($position)
    {
        $where = ['position'=>$position,'trash'=>0];
        $data = Db::name(self::POSITION_TABLE)->where($where)->find();
        return $data;
    }



    /**
     * 获取导航
     * @param $pid
     * @param $position
     * @param $sort
     * @param $condition
     * @param $limit
     * @return mixed
     */
    public function getNavList($pid = '',$position = '',$sort = '',$condition = '',$limit = 10)
    {
        $where = [];
        $positionWhere = '';
        if( !empty($position) ){
            if( !is_numeric($position) ){
                $positionInfo = Db::name(self::POSITION_TABLE)->where('position',$position)->find();
                $position = $positionInfo['id'];
            }
            $positionWhere = 'FIND_IN_SET('.$position.',position)';
        }
        if( !empty($pid) && is_numeric($pid) ){
            $where['pid'] = $pid;
        }else{
            if( $pid === 0 ){
                $where['pid'] = 0;
            }
        }
        $list = Db::name(self::NAV_TABLE) ->where('trash',0)->where($positionWhere)->where($where)->where($condition)->order($sort)->order('sort asc')->limit($limit)->select();
        return $list;
    }


    /**
     * 获取导航位
     * @param $position
     * @param $sort
     * @param $condition
     * @param $limit
     * @return mixed
     */
    public function getNavs($position,$sort = '',$condition = '',$limit = 10)
    {
        $where = '`trash`=0 ';
        if( is_numeric($position) ){
            $where .= ' and FIND_IN_SET('.$position.',position)';
        }else{
            $positionInfo = $this->getPositionFromLogo($position);
            if( empty($positionInfo) ){
                return [];
            }
            $where .= 'and FIND_IN_SET('.$positionInfo['id'].',position)';
        }
        $list = Db::name(self::NAV_TABLE)->where($where)->where($condition)->order($sort)->order('sort asc')->limit($limit)->select();
        return $list;
    }


    public function updateLinks()
    {

        Db::startTrans();
        try{
            // 导航位
            $NavPositionList = Db::name('sys_nav_position')->where('trash',0)->select();
            foreach ($NavPositionList as $key=>$position){
                if( $position['link_type'] != 5){
                    $url = $this->getUrl($position['link_type'],$position['link_param']).$position['link_extra_param'];
                    Db::name('sys_nav_position')->where('id',$position['id'])->update(['link'=>$url]);
                }
            }
            // 导航
            $NavList = Db::name('sys_nav')->where('trash',0)->select();
            foreach ($NavList as $key=>$position){
                if( $position['link_type'] != 5){
                    $url = $this->getUrl($position['link_type'],$position['link_param']).$position['link_extra_param'];
                    Db::name('sys_nav')->where('id',$position['id'])->update(['link'=>$url]);
                }
            }
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            Log::error($e);
            return $e->getMessage();
        }
        return true;
    }

}