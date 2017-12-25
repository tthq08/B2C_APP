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
class Plugin extends Model
{
	 // 设置当前模型对应的完整数据表名称
    protected $table;

    public function __construct(){
        // $this->table = config('database.prefix').'admin_plugin';    
    }

    public function getPluginType()
    {
        $plugins_dir = EXTEND_PATH.'plugins';
        $dirs = array_map('basename', glob($plugins_dir.'/*', GLOB_ONLYDIR));
        return $dirs;
    }

	public function getAllPlugin($type = '')
	{
		$result = cache('plugin_all');
        // if (!$result) {
            $plugins_dir = EXTEND_PATH.'plugins/'.$type;
        	//取plugins 目录下的所有目录
            $dirs = array_map('basename', glob($plugins_dir.'/*', GLOB_ONLYDIR));
            // dump($dirs);
            if ($dirs === false || !file_exists($plugins_dir)) {
                $this->error = lang('plugin_path_error');
                return false;
            }

            foreach ($dirs as $key => $plug) {
                $plug_dir = $plugins_dir.'/'.$plug;
                $conf_dir = $plug_dir.'/config.php';
                // dump($conf_dir);
                $config = include $conf_dir;
                $config['icon'] = base64EncodeImage($plugins_dir.'/'.$config['code'].'/'.$config['icon']);
                $result[] = $config;
            }
        // }

        // 根据插件状态进行排序,排序先后:已启用,已禁用,未安装
        $sort = array(
            'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
            'field'     => 'status',       //排序字段
        );
        $arrSort = array();
        foreach($result AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $result);
        }
        return $result;
	}

	public function getPluginConfig($plugin,$type)
    {
        $plugins_dir = EXTEND_PATH.'plugins/'.$type.'/'.$plugin;
        $config = include $plugins_dir.'/config.php';
        return $config;
    }


    public function savePlugConfig($array,$plugin,$type)
    {
        $plugins_dir = EXTEND_PATH.'plugins/'.$type.'/'.$plugin;
        $file=$plugins_dir.'/config.php'; 
        $s="<?php\n return ".var_export($array,true).";\n?>";
        // dump($s);
        if(file_exists($file)){@unlink($file);}
        if(!$fp=@fopen($file,'w')){
            return ['status'=>0,'msg'=>lang('plugin_config_open_error')];
        }
        flock($fp,LOCK_EX);
        if(!fwrite($fp,$s)){
            return ['status'=>0,'msg'=>lang('plugin_config_write_error')];
        }
        flock($fp,LOCK_UN);
        unset($fp);

        return ['status'=>1,'msg'=>lang('plugin_config_write_success')];
    }
}