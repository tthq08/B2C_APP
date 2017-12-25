<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 后台文件上传控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017     
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\home;

use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use OSS\Core\OssException;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * 通用上传接口
 * Class Upload
 * @package app\api\controller
 */
class Upload extends Controller
{
    public $lang_id;
    protected function _initialize()
    {
        parent::_initialize();
        // if (!Session::has('admin_id')) {
        //     $result = [
        //         'error'   => 1,
        //         'message' => '未登录'
        //     ];

        //     return $result;
        // }
        $lang = cookie('think_var');
        $this->lang_id = Db::name('lang') ->where('lang',$lang) ->value('id');
    }

    /**
     * 云存储下的所有图片
     * @return mixed
     */
    public function qiniu_picList()
    {
        require_once APP_PATH . '/../vendor/vendor/autoload.php';

        $accessKey = tb_config('QINIU_ACCESSKEY',1,$this->lang_id);
        $secretKey = tb_config('QINIU_SECRETKEY',1,$this->lang_id);

        $auth = new Auth($accessKey, $secretKey);
        $bucketMgr = new BucketManager($auth);

        // 要列取的空间名称
        $bucket = tb_config('QINIU_BUCKET',1,$this->lang_id);
        // 要列取文件的公共前缀
        $prefix = '';
        // 上次列举返回的位置标记，作为本次列举的起点信息。
        $marker = '';
        // 本次列举的条目数
        //$limit = 3;

        // 列举文件
        $list = $bucketMgr->listFiles($bucket, $prefix, $marker);
        $list = array_filter($list);

        $this->assign([
            'list' => $list
        ]);

        return $this->fetch();
    }

    /**
     * 通用图片上传接口
     * @return \think\response\Json
     */
    public function upload($img_tp='image')
    {
        $file_size = tb_config('upload_image_size',1,$this->lang_id)*1024;
        $config = [
            'size' => $file_size,
            'ext'  => tb_config('upload_image_ext',1,$this->lang_id)
        ];

        if ($file_size==0) {
            unset($config['size']);
        }

        // dump($config);die;

        $file = $this->request->file('fileList');
        if (empty($file)) {
            $file = $this->request->file('file');
        }
        $type = '';
        // UEditor控件上传
        if (empty($file)) {
            $type = 'UE';
            $file = $this->request->file('upfile');
        }
        // dump($file);

        $upload_path = str_replace('\\', '/', ROOT_PATH.PUBLIC_PATH . 'uploads/'.$img_tp);
        $save_path   = PUBLIC_PATH."uploads/{$img_tp}/";

        $save_server = tb_config('upload_file_postion',1,$this->lang_id);
        $upload_serv = [];
        // Html5 Base64编码方式上传图片
        if (empty($file)) {
            $img = $_POST['img'];
            // echo UPLOAD_DIR;
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace('data:image/jpeg;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            $file_default = md5(time().uniqid()) . '.png';
            $fileStr =  $save_path.''.date('Ymd').'/'.$file_default;
            $path = $upload_path.'/'.date('Ymd').'/';
            if(!is_dir($path)){
                mkdir($path);
                chmod($path,0777);
            }
            $file = $path.$file_default;
            $success = file_put_contents($file, $data);
            $file = ltrim($file,'.');
            if ($save_server=='local') {
                if ($success) {
                    return ['state'=>'SUCCESS','msg'=>'upload access','url'=>$fileStr];
                }else{
                    return ['state'=>'ERROR','msg'=>'upload error'];
                }
            }
            $upload_serv = [
                'saveName' =>   date('Ymd').'/'.$file_default,
                'pathName' =>   $upload_path.'/'.date('Ymd').'/'.$file_default
            ];
           
        }else{
            $info        = $file->validate($config)->move($upload_path);
            // dump($upload_serv);die;
            if (!$info) {
                $result = [
                    'error'   => 1,
                    'message' => $file->getError()
                ];
                return $result;
            }
            $upload_serv = [
                'saveName' =>   $info->getSaveName(),
                'pathName' =>   $info->getPathname()
            ];
        }
        switch ($save_server) {
            case 'aliyun':  //上传至阿里云
                vendor('aliyun.autoload');
                $accessKeyId = tb_config('aliyun_keyid',1,$this->lang_id);//去阿里云后台获取秘钥
                $accessKeySecret = tb_config('aliyun_secret',1,$this->lang_id);//去阿里云后台获取秘钥
                $endpoint = tb_config('aliyun_endpoing',1,$this->lang_id);//你的阿里云OSS地址
                $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);

                $bucket= tb_config('aliyun_bucket',1,$this->lang_id);//oss中的文件上传空间
                $object = str_replace('\\', '/', $upload_serv['saveName']);//想要保存文件的名称
                $file = str_replace('\\', '/', $upload_serv['pathName']);//文件路径，必须是本地的。
                try{
                    $res = $ossClient->uploadFile($bucket,$object,$file);
                    if (tb_config('aliyun_cdn_domain',1,$this->lang_id)) {
                        $url = 'http://'.tb_config('aliyun_cdn_domain',1,$this->lang_id).'/'.$object;
                    } else {
                        $url = 'http://'.$bucket.'.'.$endpoint.'/'.$object;
                    }
                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $object,
                            'original' => $object
                        ];
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }
                    
                    @unlink($file);
                    return $result;
                    //上传成功，自己编码
                    //这里可以删除上传到本地的文件。
                } catch(OssException $e) {
                    //上传失败，自己编码
                    printf($e->getMessage() . "\n");
                    return;
                }
                break;
            case 'qiniu':       //上传至七牛云
                require_once APP_PATH . '/../vendor/vendor/autoload.php';

                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = tb_config('QINIU_ACCESSKEY',1,$this->lang_id);
                $secretKey = tb_config('QINIU_SECRETKEY',1,$this->lang_id);
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);

                // 要上传的空间
                $bucket = tb_config('QINIU_BUCKET',1,$this->lang_id);

                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                // 要上传文件的本地路径
                $filePath = str_replace('\\', '/', $upload_serv['pathName']);//文件路径，必须是本地的。
                // dump($filePath);die;
                $ext = pathinfo($upload_serv['pathName'], PATHINFO_EXTENSION);  //后缀
             
                // 上传到七牛后保存的文件名
                $key = substr(md5($upload_serv['pathName']) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;

                // 初始化 UploadManager 对象并进行文件的上传
                $uploadMgr = new UploadManager();

                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                if ($err !== null) {
                    $result = ['error'=>1,'message'=>'上传失败'];
                } else {
                    $url = 'http://'.tb_config('QINIU_CDN',1,$this->lang_id).'/'.$key;
                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $key,
                            'original' => $key
                        ];
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }

                    @unlink($file);
                }
                return $result;
                break;

            case 'local':        //上传至本地
                if ($info) {
                    $url = str_replace('\\', '/', $save_path . $upload_serv['saveName']);

                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $upload_serv['saveName'],
                            'original' => $upload_serv['saveName']
                        ];
                        return json_encode($result);
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }
                } else {
                    $result = [
                        'error'   => 1,
                        'message' => $file->getError()
                    ];
                }
                return $result;
                break;
        }
    }

    /**
     * 通用文件上传接口
     * @return \think\response\Json
     */
    public function upload_file()
    {
        $file_size = tb_config('upload_file_size',1,$this->lang_id)*1024;
        $config = [
            'size' => $file_size,
            'ext'  => tb_config('upload_file_ext',1,$this->lang_id)
        ];
        if ($file_size==0) {
            unset($config['size']);
        }
        $file = $this->request->file('fileList');
        if (empty($file)) {
            $file = $this->request->file('file');
        }
        // UEditor控件上传
        if (empty($file)) {
            $type = 'UE';
            $file = $this->request->file('upfile');
        }
        // dump($file);

        $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads/files');
        $save_path   = '/uploads/files/';
        $info        = $file->validate($config)->move($upload_path);
        if (!$info) {
            $result = [
                'error'   => 1,
                'message' => $file->getError()
            ];
            return $result;
        }
        switch (tb_config('upload_file_postion',1,$this->lang_id)) {
            case 'aliyun':  //上传至阿里云
                vendor('aliyun.autoload');
                $accessKeyId = tb_config('aliyun_keyid',1,$this->lang_id);//去阿里云后台获取秘钥
                $accessKeySecret = tb_config('aliyun_secret',1,$this->lang_id);//去阿里云后台获取秘钥
                $endpoint = tb_config('aliyun_endpoing',1,$this->lang_id);//你的阿里云OSS地址
                $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);

                $bucket= tb_config('aliyun_bucket',1,$this->lang_id);//oss中的文件上传空间
                $object = str_replace('\\', '/', $info->getSaveName());//想要保存文件的名称
                $file = str_replace('\\', '/', $info->getPathname());//文件路径，必须是本地的。
                try{
                    $res = $ossClient->uploadFile($bucket,$object,$file);
                    if (tb_config('aliyun_cdn_domain',1,$this->lang_id)) {
                        $url = 'http://'.tb_config('aliyun_cdn_domain',1,$this->lang_id).'/'.$object;
                    } else {
                        $url = 'http://'.$bucket.'.'.$endpoint.'/'.$object;
                    }
                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $object,
                            'original' => $object
                        ];
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }
                    
                    @unlink($file);
                    return $result;
                    //上传成功，自己编码
                    //这里可以删除上传到本地的文件。
                } catch(OssException $e) {
                    //上传失败，自己编码
                    printf($e->getMessage() . "\n");
                    return;
                }
                break;
            case 'qiniu':       //上传至七牛云
                require_once APP_PATH . '/../vendor/vendor/autoload.php';

                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = tb_config('QINIU_ACCESSKEY',1,$this->lang_id);
                $secretKey = tb_config('QINIU_SECRETKEY',1,$this->lang_id);
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);

                // 要上传的空间
                $bucket = tb_config('QINIU_BUCKET',1,$this->lang_id);

                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                // 要上传文件的本地路径
                $filePath = str_replace('\\', '/', $info->getPathname());//文件路径，必须是本地的。
                // dump($filePath);die;
                $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
             
                // 上传到七牛后保存的文件名
                $key = substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;

                // 初始化 UploadManager 对象并进行文件的上传
                $uploadMgr = new UploadManager();

                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                if ($err !== null) {
                    $result = ['error'=>1,'message'=>'上传失败'];
                } else {
                    $url = 'http://'.tb_config('QINIU_CDN',1,$this->lang_id).'/'.$key;
                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $key,
                            'original' => $key
                        ];
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }

                    @unlink($file);
                }
                return $result;
                break;

            case 'local':        //上传至本地
                if ($info) {
                    $url = str_replace('\\', '/', $save_path . $info->getSaveName());
                    if ($type=='UE') {
                        $result = [
                            'state' => 'SUCCESS',
                            'url' => $url,
                            'title' => $info->getSaveName(),
                            'original' => $info->getSaveName()
                        ];
                    } else {
                        $result = [
                            'error' => 0,
                            'url'   => $url
                        ];
                    }
                } else {
                    $result = [
                        'error'   => 1,
                        'message' => $file->getError()
                    ];
                }
                return $result;
                break;
        }
    }

/**
 * 验证是否为真实图片
 * @param file $file 文件
 * @param define $exif 需要验证的常量;
 * @return bool
 * exif_imagetype函数返回常量:
 * 1 IMAGETYPE_GIF
 * 2 IMAGETYPE_JPEG
 * 3 IMAGETYPE_PNG
 * 4 IMAGETYPE_SWF
 * 5 IMAGETYPE_PSD
 * 6 IMAGETYPE_BMP
 * 7 IMAGETYPE_TIFF_II（Intel 字节顺序）
 * 8 IMAGETYPE_TIFF_MM（Motorola 字节顺序）
 * 9 IMAGETYPE_JPC
 * 10 IMAGETYPE_JP2
 * 11 IMAGETYPE_JPX
 * 12 IMAGETYPE_JB2
 * 13 IMAGETYPE_SWC
 * 14 IMAGETYPE_IFF
 * 15 IMAGETYPE_WBMP
 * 16 IMAGETYPE_XBM
 **/
 function checkFile($file,$exif){
    $mimeType = exif_imagetype($file);
    if( is_array($exif) ){
        if( in_array($exif,$mimeType) ){
            return true;
        }
    }else{
        if( $mimeType == $exif ){
            return true;
        }
    }
    return false;
 }
	

}