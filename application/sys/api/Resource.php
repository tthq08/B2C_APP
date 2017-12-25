<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 支付接口操作类
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\api;


use FileLibrary\FileLibrary;

class Resource extends FileLibrary
{


    /**
     * 用户id
     * @var int
     */
    public static $user;


    /**
     * 用户地址
     * @var string
     */
    public static $user_path;


    /**
     * 当前上传目录
     * @var string
     */
    public static $uploadPath;


    /**
     * Resource constructor.
     * @param string $user
     */
    public function __construct($user = '')
    {
        self::$user = $user;
        parent::config(function ()use($user){
            return [];
        });
    }




    /**
     * 查看文件目录
     * @param string $folder
     * @param boolean $mkdir 文件夹不存在是否创建文件夹
     * @return array|bool
     */
    public function files($folder = '',$mkdir = false)
    {
        if( $folder == '..' ){
            $this->topFolder();
        }elseif( $folder == '...' ){
            $this->nowPath('/');
        }else{
            $this->nowPath($folder);
        }
        $files = $this->openFolder($this->nowPath());

        return ['code'=>1,'data'=>json_encode($files)];
    }



    /**
     * 压缩文件
     * @param string $path 文件路径
     * @param string $zip 压缩的路径
     * @return boolean
     */
    public function zip($path, $zip)
    {

    }


    /**
     * 解压文件
     * @param string $zip 压缩文件路径
     * @param string $path 解压缩的地址
     * @return boolean
     */
    public function unzip($zip, $path)
    {

    }


    /**
     * 获取前端访问的基础目录
     * @return string
     */
    public static function getRootPath()
    {
        return '/picture/'.self::$user;
    }



    /**
     * 获取当前实际地址
     * @return mixed
     */
    public function getNowActualPath()
    {

        return parent::getNowActualPath();
    }


    /**
     * 获取当前显示地址
     * @return mixed
     */
    public function getNowPath()
    {
        return $this->nowPath();
    }


    /**
     * 当前上传目录
     * @return mixed
     */
    public static function getUploadPath()
    {
        if( empty(self::$uploadPath) ){
            $uploadPath = session('upload_path');
        }
        return self::$uploadPath;
    }


    /**
     * 新建文件夹
     * @return mixed
     */
    public function newFolder($name = '',$path = '',$chmod = 0777)
    {
        $path = $this->getBasePath().$this->nowPath().'/';
        $name = '新建文件夹';
        if( is_dir($path.$name) ){
            $i = 1;
            while (true){
                if( !is_dir($path.$name.$i) ){
                    $name .= $i;
                    $path = $path.$name;
                    break;
                }
                $i++;
            }
        }else{
            $path = $path.'新建文件夹';
        }
        return parent::newFolder($name, $path, $chmod); // TODO: Change the autogenerated stub
    }




    /**
     * 获取文件夹列表(上传用)
     * @return mixed
     */
    public function folderList()
    {
        $list = parent::folders(0,true);
        $data[0] = ['id'=>0,'pid'=>-1,'title'=>'选择文件夹'];
        $i = 1;
        foreach ($list as $key=>$item)
        {
            $data[$i] = [
                'id'=>$item['id'],
                'pid'=>$item['pid'],
                'title'=>$item['name'],
            ];
            $i++;
        }
        return $data;
    }


    /**
     * 文件上传
     * @param int $folder 文件夹ID
     * @return mixed
     */
    public function fileUpload($folder = 0)
    {
        $file = request()->file('file_data');
        $data = parent::uploadFile($file->getInfo(),$folder);
        return $data;
    }

}