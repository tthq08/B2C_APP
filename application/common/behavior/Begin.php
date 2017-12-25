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


/**
 * 初始化配置信息行为
 * 将系统配置信息合并到本地配置
 * @package app\common\behavior
 */
class Begin
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

        // 查看是否开启定向IP调试模式
        $directional_debug = tb_config('directional_debug',1);
        if( !empty($directional_debug) && $directional_debug == 1 ){
            // 定向调试模式IP指向
            $debug_ip = tb_config('debug_ip',1);
            if( in_array(request()->ip(),$debug_ip)){
                // 开启debug调试模式
                config('app_debug',true);
            }
        }
    }
}
