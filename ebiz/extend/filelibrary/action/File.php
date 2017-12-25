<?php
namespace FileLibrary\action;
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/8
 * Time: 下午4:09
 */
class File
{

    /**
     * 创建目录
     * @param $path
     * @param $chmod
     * @return mixed
     */
    public static function createDirectory($path,$chmod = '')
    {
        if( is_dir($path) ){
            return false;
        }
        mkdir($path);
        chmod($path,$chmod);
    }

}