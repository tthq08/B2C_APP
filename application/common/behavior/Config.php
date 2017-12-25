<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 系统行为扩展
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\common\behavior;
use think\Db;


/**
 * 初始化配置信息行为
 * 将系统配置信息合并到本地配置
 * @package app\common\behavior
 */
class Config
{
    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @param mixed $params  行为参数
     * @return void
     */
    public function run(&$params)
    {
        $default_module = tb_config('default_module',1);
        if (!empty($default_module)) {
            config('default_module',$default_module);
        }
        // 如果是安装操作，直接返回
        if(defined('BIND_MODULE') && BIND_MODULE === 'install') return;

        // 获取当前模块名称
        $module = '';
        $dispatch = request()->dispatch();
        // $dispatch = array_map('strtolower', $dispatch);
        if (isset($dispatch['module'])) {            
            $module = strtolower($dispatch['module'][0]);
            // dump($dispatch);
        }
        // // 获取入口目录
        $base_file = request()->baseFile();
        $base_dir = $base_file;

        // 如果定义了入口为admin，则修改默认的访问控制器层
        if(defined('ENTRANCE') && ENTRANCE == 'admin') {
            if ($dispatch['type'] == 'module' && $module == '') {
                header("Location: ".$base_dir.'/sys/login/index', true, 302);exit();
            }
            config('paginate.type','page\Page');
            $sys_module = ['sys','user','statistics','common','index','extra'];
            // dump($module);
            if ($module != '' && !in_array($module, $sys_module)) {
                // 修改默认访问控制器层
                config('url_controller_layer', 'admin');
                // 修改视图模板路径
                config('template.view_path', APP_PATH. $module. '/view/');
                config('template.mobile_path', APP_PATH. $module. '/view/');
            }
            // 插件静态资源目录
            // config('view_replace_str.__PLUGINS__', '/plugins');
        } else {
            if ($dispatch['type'] == 'module' && $module == 'admin') {
                header("Location: ".$base_dir.'/sys', true, 302);exit();
            }
            // 修改默认访问控制器层
            if ($module != 'index') {
                if ($module == '') {
                    $module = config('default_module');
                }
                $moduleConfig = Db::name('admin_module_config')->where('module',$module)->find();

                if( isMobile() ){
                    config('url_controller_layer', 'mobile');
                    if( empty($moduleConfig['mobile_template']) ){
                        $moduleConfig['mobile_template'] = $module;
                    }
                    config('template.view_path', config('template.home_view_path').$moduleConfig['mobile_template'].'/');
                }else{
                    if( empty($moduleConfig['home_template']) ){
                        $moduleConfig['home_template'] = $module;
                    }
                    config('url_controller_layer', 'home');
                    config('template.view_path', config('template.home_view_path').$moduleConfig['home_template'].'/');
                }

            }
        }
    }
}
