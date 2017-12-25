<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/7
 * Time: 下午3:44
 */
namespace FileLibrary;

use FileLibrary\action\Database;
use FileLibrary\action\File;
use think\Db;
use think\Request;

class FileLibrary
{


    /**
     * 配置项
     * @var array
     */
    protected static $config = [

        // 基础目录
        'baseDirectory' => ROOT_PATH.'public/picture/',

        // 前段访问基础目录
        'showBaseDirectory' => '/picture/',

        // 开启多文件库
        'openSubLibrary' => true,

        // 图片子库(子库用于多文件库时)
        'subLibrary' => 'admin',

        // 数据表名称
        'dbName' => 'sys_file_library',

        // 是否删除文件不存在的数据库信息(指仅存在于数据库中,不存在于实际服务器目录中)(仅在检测文件|文件夹是否存在时有效)
        'deleteNonexistentFile' => false,
    ];


    /**
     * 设置基础数据
     * @param array|\Closure $configArray 配置项数组(支持闭包模式)
     * @return mixed
     */
    protected function config($configArray)
    {
        if( empty($configArray) )
            return false;
        $data = [];
        if( $configArray instanceof \Closure) {
            $data = call_user_func($configArray);
        }elseif ( is_array($configArray) ) {
            $data = $configArray;
        }
        if( !empty($data) ){
            foreach ($data as $key=>$value) {
                if( isset(self::$config[$key]) ){
                    self::$config[$key] = $value;
                }
            }
        }
    }


    /**
     * 设置当前路径
     * @param string $nowPath 当前路径
     * @return string
     */
    protected function nowPath($nowPath = '')
    {
        if( empty($nowPath) ){
            if( empty(cookie('file_library_now_path')) ){
                cookie('file_library_now_path','/');
            }
            $path = cookie('file_library_now_path');
            if( strpos($path,'/') !== 0 ){
                $path = '/'.$path;
            }
            return $path;
        }
        cookie('file_library_now_path',$nowPath);
        return $nowPath;
    }



    /**
     * 设置当前路径
     * @param string $nowPathId 当前路径数据库ID
     * @return string
     */
    protected function nowPathId($nowPathId = '')
    {
        if( '' === $nowPathId ){
            if( empty(cookie('file_library_now_path_id')) ){
                cookie('file_library_now_path_id',0);
            }
            $id = cookie('file_library_now_path_id');
            return $id;
        }
        cookie('file_library_now_path_id',$nowPathId);
        return $nowPathId;
    }


    /**
     * 获取当前服务器实际路径
     * @return mixed
     */
    protected function getNowActualPath()
    {
        $nowActualPath = self::$config['baseDirectory'];
        if( self::$config['openSubLibrary'] === true ){
            $nowActualPath .= self::$config['subLibrary'];
        }
        return $nowActualPath.$this->nowPath();
    }


    /**
     * 打开文件夹
     * @param string $folderPath 文件夹路径
     * @param string $fileType 获取的文件类型(如果该参数不为空,则值获取该类型的文件或文件夹)
     * @return array
     */
    protected function openFolder($folderPath,$fileType = '')
    {
        $where = [];
        if( !empty($fileType) ){
            $where = ['type' => $fileType];
        }
        if( self::$config['openSubLibrary'] === true ){
            $where['user'] = self::$config['subLibrary'];
        }
        $fileList = Database::openFolder($folderPath,$where);
        return $fileList;
    }


    /**
     * 获取当前文件夹的上级
     * @return mixed
     */
    protected function topFolder()
    {
        $top = Database::getTopFolder($this->nowPath());
        if( empty($top) ){
            $this->nowPath('/');
            $this->nowPathId(0);
            return $this->nowPath();
        }
        $this->nowPath($top['base_path']);
        return $top['base_path'];
    }



    /**
     * 新建文件夹
     * @param string $name 文件名称
     * @param string $path 新建的路径
     * @param int $chmod 文件夹权限: 0755
     * @return mixed
     */
    protected function newFolder($name ,$path ,$chmod = 0777)
    {
        if( is_dir($this->getBasePath().$path) ){
            return ['code'=>0,'error'=>'当前文件夹已存在'];
        }
        $topFolder = Database::getFolder($this->nowPath());
        if( empty($topFolder) ){
            $fileData = [
                'pid' => 0,
                'id_path' => 0,
                'user' => 0,
                'name' => $name,
                'path' => self::config('showBaseDirectory').self::config('subLibrary').'/'.$name,
                'base_path' => '/'.$name,
                'type' => 'folder',
                'upload_time' => date('Y-m-d H:i:s'),
                'modification_time' => date('Y-m-d H:i:s'),
                'folder' => '',
                'folder_path' => '/',
            ];
        }else{
            $fileData = [
                'pid' => $topFolder['id'],
                'id_path' => $topFolder['id_path'].','.$topFolder['id'],
                'user' => 0,
                'name' => $name,
                'path' => $topFolder['path'].'/'.$name,
                'base_path' => $topFolder['base_path'].'/'.$name,
                'type' => 'folder',
                'upload_time' => date('Y-m-d H:i:s'),
                'modification_time' => date('Y-m-d H:i:s'),
                'folder' => $topFolder['name'],
                'folder_path' => $topFolder['folder_path'].'/'.$topFolder['name'],
            ];
        }

        Db::startTrans();
        try{
            Database::newFolder($fileData);
            mkdir($path);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        chmod($path,$chmod);
        return ['code'=>1,'file'=>$fileData];
    }



    /**
     * 查看文件是否存在
     * @param $filePath
     * @return mixed
     */
    public function fileExist($filePath)
    {
        $isExist = Database::getFile($filePath,'id');
        if( empty($isExist) )
            return false;
        if( !is_file(PUBLIC_BASE_PATH.$filePath) ){
            if( self::$config['deleteNonexistentFile'] == true )
                // 删除数据库信息
                Database::rmFile(PUBLIC_BASE_PATH.$filePath,['type'=>['<>','folder']]);
            return false;
        }
        return true;
    }


    /**
     * 查看文件夹是否存在
     * @param $folderPath
     * @return mixed
     */
    public function folderExist($folderPath)
    {
        $isExist = Database::getFolder($folderPath,'id');
        if( empty($isExist) )
            return false;
        if( !is_dir($folderPath) ){
            if( self::$config['deleteNonexistentFile'] == true )
                // 删除数据库信息
                Database::rmFile($folderPath,['type'=>'folder']);
            return false;
        }
        return true;
    }


    /**
     * 获取当前base路径
     * @return mixed
     */
    public function getBasePath()
    {
        $basePath = self::$config['baseDirectory'];
        if( self::$config['openSubLibrary'] === true ){
            $basePath .= self::$config['subLibrary'];
        }
        return $basePath;
    }


    /**
     * 获取当前base路径
     * @return mixed
     */
    public function getShowBasePath()
    {
        $basePath = self::$config['showBaseDirectory'];
        if( self::$config['openSubLibrary'] === true ){
            $basePath .= self::$config['subLibrary'];
        }
        return $basePath;
    }



    /**
     * 获取文件夹列表
     * @param int $folder 获取当前文件夹下的列表 文件夹ID
     * @param bool $all 获取全部 | false为只获取当前文件夹
     * @return mixed
     */
    public function folders($folder = 0,$all = false)
    {
        $folder = Database::folders($folder,$all);
        return $folder;
    }




    /**
     * 文件上传
     * @param array $uploadFile 上传的文件数据
     * @param int $folder 文件夹ID
     * @return mixed
     */
    public function uploadFile($uploadFile,$folder)
    {
        $folder = Database::getFromId($folder);
        if( empty($folder) ){
            $folder = [
                'id' => 0,
                'id_path' => '',
                'path' => $this->getShowBasePath(),
                'base_path' => '',
                'name' => '',
                'folder_path' => '',
            ];
        }
        // 文件夹路径
        $folderPath = $this->getBasePath().$folder['base_path'];
        // 移动的图片路径
        $filePath = $folderPath.'/'.$uploadFile['name'];
        $suffix = explode('.',$uploadFile['name']);
        $suffix = end($suffix);
        // 添加进数据库
        try{
            $move = move_uploaded_file($uploadFile['tmp_name'],$filePath);
            chmod($filePath,0777);
            // 添加进数据库
            $file = [
                'pid' => $folder['id'],
                'id_path' => empty($folder['id_path']) ? 0 : $folder['id_path'].','.$folder['id'],
                'user' => 0,
                'name' => $uploadFile['name'],
                'path' => $folder['path'].'/'.$uploadFile['name'],
                'base_path' => $folder['base_path'].'/'.$uploadFile['name'],
                'size' => $uploadFile['size'],
                'type' => $uploadFile['type'],
                'suffix' => '.'.$suffix,
                'upload_time' => datetime_format(),
                'modification_time' => datetime_format(),
                'is_audit' => 1,
                'folder' => $folder['name'],
                'folder_path' => $folder['folder_path'] == '/' ? '/'.$folder['name'] : $folder['folder_path'].'/'.$folder['name'],
            ];
            Database::newFile($file);
        }catch (\Exception $e){
            return ['code'=>0,'error'=>$e->getMessage()];
        }
        return ['code'=>1];
    }
}