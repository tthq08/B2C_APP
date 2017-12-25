<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 插件管理控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\common\JunCreater\JCreater;
use app\sys\model\Plugin as PluginModel;
use think\Controller;
use think\Config;
use think\Db;
use think\Cache;
use think\Session;
use util\Database;
use util\Sql;
use util\File;
use util\PHPZip;
use util\Tree;

class Plugins extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();
    }

  	// 插件列表
    public function index($type='payment')
    {
    	$this->assign('curr_type',$type);

    	$pluginModel = new PluginModel();
    	// 获取所有插件的类型
    	$types = $pluginModel->getPluginType();
    	$all_tps = tb_config('sys_plugin_type',1,$this->lang);
    	foreach ($types as $k => $v) {
    		$tp['name'] = $v;
    		$tp['title'] = empty($all_tps[$v]) ? '' : $all_tps[$v];
    		$tp['href'] = url('Plugins/Index',['type'=>$v]);
    		$tp['curr'] = $type==$v?true:false;
    		$plugin_types[] = $tp;
    	}
    	$this->assign('plug_typs',$plugin_types);
    	// 获取当前类型的所有插件
    	$result = $pluginModel->getAllplugin($type);
    	// dump($result);
    	$this->assign('plugins',$result);
    	return $this->fetch();
    }

    public function configs($code,$type)
    {
    	$pluginModel = new PluginModel();
    	$config = $pluginModel->getPluginConfig($code,$type);
    	$config_value = $config['config'];

    	$form['web_title'] = lang('plugin_config_form',[$config['name']]);   // 页面标题
		$form['action'] = url('config_save');		//表单提交的目的路径
		$form_fields=[['','hidden','type',$type],['','hidden','code',$code]];
		foreach ($config_value as $key => $val) {
			$option = isset($val['option'])?$val['option']:[];
			$field = [$val['label'],$val['type'],$val['name'],$val['value'],'','',$option];
			$form_fields[] = $field;
		}

		$JCreater = new JCreater();
		$form['form_Html'] = $JCreater->form_build($form_fields);
		$this->assign($form);
		return $this->fetch('sys@Base/form'); 
    }

    public function config_save()
    {
    	$data = $this->post_data;

    	$pluginModel = new PluginModel();
    	$config = $pluginModel->getPluginConfig($data['code'],$data['type']);
    	$config_value = $config['config'];

    	foreach ($config_value as $key => $val) {
    		$config_value[$key]['value'] = $data[$val['name']];
    	}

    	$config['config'] = $config_value;
    	$pluginModel = new PluginModel();
    	$res = $pluginModel->savePlugConfig($config,$data['code'],$data['type']);
    	if ($res['status']==1) {
    		$this->success($res['msg']);
    	} else {
    		$this->error($res['msg']);
    	}    	
    }

    public function config_status($code,$type,$status)
    {
    	$pluginModel = new PluginModel();
    	$config = $pluginModel->getPluginConfig($code,$type);
    	$config['status'] = $status;
    	$pluginModel = new PluginModel();
    	$res = $pluginModel->savePlugConfig($config,$code,$type);
    	if ($res['status']==1) {
    		$this->success($res['msg']);
    	} else {
    		$this->error($res['msg']);
    	}
    }
}