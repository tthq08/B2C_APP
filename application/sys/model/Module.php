<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 模块 - 模型
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\model;

use think\Model;

/**
 * 模块模型
 * @package app\Sys\model
 */
class Module extends Model
{
	 // 设置当前模型对应的完整数据表名称
    protected $table;

    public function __construct(){
        $this->table = config('database.prefix').'admin_module';    
    }

	public function getAllModule($keyword = '', $status = '')
	{
		$result = cache('module_all');
        // if (!$modules) {
        	//取application 目录下的所有目录
            $dirs = array_map('basename', glob(APP_PATH.'*', GLOB_ONLYDIR));
            if ($dirs === false || !file_exists(APP_PATH)) {
                $this->error = lang('module_path_error');
                return false;
            }

            // 读取模块列表时不予读取的目录
            $sys_model = ['Admin','User','common','extra','lang'];
            // 真正的模块，（已排除系统模块和系统功能目录）
            $dirs = array_diff($dirs, $sys_model);
            $dirs = array_map('strtolower', $dirs);

            // 读取数据库模块表
            $modules = $this->order('sort asc,id desc')->column(true, 'name');
            // 读取未安装的模块
            foreach ($dirs as $module) {
                if (!isset($modules[$module])) {
                    // 获取模块信息
                    $info = self::getInfoFromFile($module);
                    
                    if (!isset($info['is_manage']) || (isset($info['is_manage']) && $info['is_manage']!=0)){
                        $modules[$module]['name'] = $module;

                        // 模块模块信息缺失
                        if (empty($info)) {
                            $modules[$module]['status'] = '-2';
                            continue;
                        }

                        // 模块未安装
                        $modules[$module] = $info;
                        $modules[$module]['status'] = '-1'; // 模块未安装
                    }
                }
            }
            // dump($modules);
            $result = $modules;
            cache('module_all',$result);
        // }
        $lang = cookie('think_var');
        foreach ($result as $key => $modu) {
        	// dump($modu);
        	$title = json_decode($modu['title'],true);
        	$result[$key]['title'] = $title[$lang];
        }
        return $result;
	}

	/**
     * 从文件获取模块信息
     * @param string $name 模块名称
     * @return array|mixed
     */
    public static function getInfoFromFile($name = '')
    {
        $info = [];
        if ($name != '') {
            // 从配置文件获取
            if (is_file(APP_PATH. $name . '/info.php')) {
                $info = include APP_PATH. $name . '/info.php';
            }
        }
        return $info;
    }

    /**
     * 从文件获取模块菜单
     * @param string $name 模块名称
     * @return array|mixed
     */
    public static function getMenusFromFile($name = '')
    {
        $menus = [];
        if ($name != '' && is_file(APP_PATH. $name . '/menus.php')) {
            // 从菜单文件获取
            $menus = include APP_PATH. $name . '/menus.php';
        }
        return $menus;
    }
}