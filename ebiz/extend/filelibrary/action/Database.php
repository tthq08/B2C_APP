<?php
namespace FileLibrary\action;
use think\Db;

/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/8
 * Time: 下午4:10
 */
class Database
{


    /**
     * 数据表名称
     * @var string
     */
    protected static $dbName = 'sys_file_library';


    /**
     * 数据表实例
     * @var Db
     */
    private static $db;


    /**
     * 读取文件夹
     * @param string $folderPath 文件夹地址
     * @param string|array $condition 额外条件
     * @return array
     */
    public static function openFolder($folderPath,$condition = '')
    {
        $fileList = self::db()->where($condition)->where('folder_path',$folderPath)->select();

        return $fileList;
    }




    /**
     * 获取上级文件夹
     * @param string $path 获取当前文件|文件夹的上级文件夹信息
     * @return array
     */
    public static function getTopFolder($path)
    {
        $nowFolder = self::getFolder($path);
        $condition = ['type'=>"folder",'base_path'=>$nowFolder['folder_path']];
        $top = self::db()->where($condition)->find();
        return $top;
    }




    /**
     * 文件夹信息
     * @param string $folderPath 当前文件夹的信息
     * @param string $field 获取的字段
     * @return array
     */
    public static function getFolder($folderPath,$field = '')
    {
        $fieldPos = 0;
        if( is_array($field) ){
            $fieldPos = count($field) > 1 ? 1 : false;
        }
        $condition = ['base_path'=>$folderPath,'type'=>'folder'];
        if( $fieldPos === 0 || $fieldPos > 0 ){
            $folder = self::db()->where($condition)->field($field)->find();
        }else{
            $folder = self::db()->where($condition)->value($field);
        }
        return $folder;
    }



    /**
     * 通过ID文件夹信息
     * @param string $id
     * @param string $field 获取的字段
     * @return array
     */
    public static function getFromId($id,$field = '')
    {

        $condition = ['id'=>$id];
        $data = self::db()->where($condition)->field($field)->find();

        return $data;
    }



    /**
     * 文件信息
     * @param string $folderPath 当前文件夹的信息
     * @param string $field 获取的字段
     * @return array
     */
    public static function getFile($folderPath,$field = '')
    {
        if( is_array($field) ){
            $fieldPos = count($field) > 1 ? 1 : false;
        }else{
            $fieldPos = strpos($field,',');
        }
        $condition = ['path'=>$folderPath,'type'=>['<>','folder']];
//        $condition['type'] = ['<>','folder'];
        if( $fieldPos === 0 || $fieldPos > 0 ){
            $folder = self::db()->where($condition)->field($field)->find();
        }else{
            $folder = self::db()->where($condition)->value($field);
        }
        return $folder;
    }




    /**
     * 删除文件
     * @param string $path 需要删除的文件|文件夹地址
     * @param string $condition 额外删除条件
     * @return mixed
     */
    public static function rmFile($path,$condition = '')
    {
        $where = is_numeric($path) ? ['id'=>$path] : ['path'=>$path];
        try{
            self::db()->where($where)->where($condition)->delete();
        }catch (\Exception $e){
            return ['code'=>0,'msg'=>$e->getMessage()];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }



    /**
     * 新建文件夹
     * @param $data
     * @return mixed
     */
    public static function newFolder($data)
    {
        self::db()->insert($data);
    }


    /**
     * 添加文件
     * @param $data
     * @return mixed
     */
    public static function newFile($data)
    {
        self::db()->insert($data);
    }



    /**
     * 数据表
     * @return Db
     */
    private static function db()
    {
        if( !empty(self::$db) ){
            return self::$db;
        }
        self::$db = db(self::$dbName,'',false);
        return self::$db;
    }


    /**
     * 获取文件夹下的文件夹列表
     * @param $folder
     * @param boolean $justCurrent 仅获取当前文件夹下的内容
     * @return array
     */
    public static function folders($folder,$justCurrent = true)
    {
        if( $justCurrent == true ){
            $where = 'FIND_IN_SET('.$folder.',`id_path`)';
            $condition['type'] = 'folder';
            $folders = self::db()->where($condition)->where($where)->select();
            return $folders;
        }
        $condition = ['pid'=>$folder,'type'=>'folder'];
        $folders = self::db()->where($condition)->select();
        return $folders;
    }
}