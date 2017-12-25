<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： EBIZ 钩子
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------

namespace hook;


class Hook
{

    /**
     * 当前钩子
     * @var array
     */
    protected static $hooks;


    /**
     * 执行钩子
     * @param string $position 执行当前位置下的hook
     * @param string $hookAction 在$action不为空的情况下,仅会执行当前位置下的$action操作
     * @param array|string $args 可以在hook中填写参数,由数组组成
     * @return mixed
     */
    public static function exec($position, $hookAction = '',$args = '')
    {
        if( empty(self::$hooks[$position]) ){
            return null;
        }
        $action = empty($hookAction) ? self::$hooks[$position] : [$hookAction];

        foreach ($action as $item=>$value){
            if( self::hasHook($position,$item) ){
                self::execHook($position,$item,$args);
            }
        }
    }


    /**
     * 执行单个hook
     * @param string $position
     * @param string $hookAction
     * @param array|string $args
     * @return mixed
     */
    protected static function execHook($position, $hookAction,$args = '')
    {
        $actionCode = self::$hooks[$position][$hookAction];
        if( empty($actionCode) ){
            if( function_exists($hookAction) ){
                return call_user_func($hookAction,$args);
            }
        }else{
            eval($actionCode);
        }

    }


    /**
     * 添加钩子进钩子队列
     * @param string $position 选择需要添加进的hook位置,将通过指定的hook位置触发hook操作
     * @param string $hookAction hook的实际操作,由函数构成,直接填写函数名称即可
     *               注:不支持参数传入。
     * @param string $actionCode 需要执行的PHP代码; 填入该参数,会将执行函数变为执行该PHP代码。
     * @return mixed
     */
    public static function addHook($position, $hookAction, $actionCode = '')
    {
        if( empty(self::$hooks[$position]) ){
            self::$hooks[$position] = [];
        }
        if( !self::hasHook($position,$hookAction) ){
            if( empty($actionCode) ){
                self::$hooks[$position][$hookAction] = '';
            }else{
                self::$hooks[$position][$hookAction] = $actionCode;
            }
        }
    }


    /**
     * 查看hook是否存在
     * @param string $position
     * @param string $hookAction
     * @return mixed
     */
    protected static function hasHook($position, $hookAction)
    {
        if( empty(self::$hooks[$position][$hookAction]) ){
            return false;
        }
        return true;
    }


    /**
     * 删除钩子
     * @param string $position
     * @param string $hookAction 需要删除的钩子
     * @return mixed
     */
    public static function removeHook($position, $hookAction)
    {
        if( self::hasHook($position,$hookAction) ){
            unset(self::$hooks[$position][$hookAction]);
        }
    }


}