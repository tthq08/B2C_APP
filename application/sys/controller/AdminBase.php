<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 后台公用基础控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------
namespace app\sys\controller;

use org\Auth;
use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\Sys\controller
 */
class AdminBase extends Controller
{
    public $lang;
    public $lang_id;
    public $module_list;
    public $post_data;

    protected function _initialize()
    {
        parent::_initialize();

        $this->getLang();
        // 如果是POST 提交，先验证表单令牌
        if ($this->request->isPost()) {
            $data = request()->post();
            // 如果POST数据中有__token__并且表单令牌验证开启了，则验证表单令牌
            if (isset($data['__token__']) && tb_config('Sys_form_switch', 1, $this->lang_id) == 1) {
                $toke_valid = $this->validate($data, ['__token__' => 'require|token']);
                if ($toke_valid !== true) {
                    $this->error($toke_valid);
                }
            }
            unset($data['__token__']);
            unset($data['fileList']);
            $this->post_data = $data;
        }
        $this->checkAuth();
        $this->getModule();
        $this->getMenu($this->request->module());
        $this->getAdmin();
        $this->getSkin();
        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $this->assign('controller', Loader::parseName($this->request->controller()));


    }

    /**
     * 获取当前的语言，输出语言ID
     * @return bool
     */
    protected function getLang()
    {
        $this->lang = cookie('think_var');
        $this->lang_id = Db::name('lang')->where('lang', $this->lang)->value('id');
        $this->assign('lang', $this->lang);
        $this->assign('lang_id', $this->lang_id);
    }

    protected function getSkin()
    {
        $skin = cookie('sys_skin');
        $skin = empty($skin) ? 'bg-1' : $skin;
        $this->assign('sys_skin', $skin);
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {

        if (!Session::has('admin_id')) {
            $this->redirect('sys/login/index');
        }

        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        $url_param = ['module' => $module, 'controller' => $controller, 'action' => $action];
        $this->assign('url_param', $url_param);
        // 排除权限
        $not_check = ['sys/Index/index', 'sys/Index/main', 'sys/AuthGroup/getjson', 'sys/system/clear', 'sys/Login/weibo_callback', 'sys/Login/weibo_cancel_callback'];

        //对所有ajax行为放行
        if (!in_array($module . '/' . $controller . '/' . $action, $not_check) && strpos($action, 'ajax') === false) {
            $auth = new Auth();
            $admin_id = Session::get('admin_id');
            if (!$auth->check($module . '/' . $controller . '/' . $action, $admin_id) && $admin_id != 1) {
                $this->error(lang('comm_auth_error'));
            }
        }
    }

    // 取出所有状态正常的模块并输出
    protected function getModule()
    {
        $module_list = Db::name('admin_module')->where(['status' => 1])->select();
        $admin_id = session('admin_id');
        if ($admin_id != 1) {
            $role_id = getUserRole($admin_id)['id'];
            // 得到当前用户有权限的所有模块，
            $roles = explode(',', $role_id);
            $module_allow = Db::name('auth_group')->where('id', $role_id)->value('modules');
            $module_list = Db::name('admin_module')->where('id IN (' . $module_allow . ') AND status=1')->select();
            // 如果用户没有系统模块(id固定为1)权限，则自动跳转至用户拥有权限的第一个模块
            // 该判断仅在系统admin模块下有效
            $module_now = $this->request->module();
            if (!in_array(1, $roles) && $module_now == 'admin') {
                $this->redirect(url($module_list[0]['name'] . '/index/index'));
                exit;
            }
        }
        //取出所有模块在当前语言版本下的显示标题
        foreach ($module_list as $key => $module) {
            $module_title = json_decode($module['title'], true);
            $module_list[$key]['title'] = $module_title[$this->lang];
        }
        $this->module_list = $module_list;
        $this->assign('module_list', $module_list);
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu($module)
    {
        $menu = $this->getSubMenu(0, $module);
        $this->assign('menu', $menu);
    }

    private function getSubMenu($id, $module)
    {
        $menu = [];
        $admin_id = session('admin_id');
        $auth = new Auth();
        if ($admin_id != 1) {
            // dump($admin_id);
            $role_id = getUserRole($admin_id)['id'];
            $rules_allow = Db::name('auth_group')->where('id', $role_id)->value('rules');
            $auth_rule_list = Db::name('auth_rule')->where(['module' => $module, 'status' => 1, 'pid' => $id])->where('id IN (' . $rules_allow . ')')->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
            foreach ($auth_rule_list as $key => $value) {
                //取出所有菜单在当前语言版本下的显示标题
                $menu_title = json_decode($value['title'], true);
                $value['title'] = $menu_title[$this->lang];
                // dump($value['title']);
                // 只显示当前用户有权限使用的菜单
                if ($auth->check($value['name'], $admin_id) || $admin_id == 1) {
                    $value['children'] = $this->getSubMenu($value['id'], $module);
                    $menu[$value['id']] = $value;
                }
            }
        } else {
            $menu = Db::name('auth_rule')->where(['module' => $module, 'status' => 1, 'pid' => $id])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();

            foreach ($menu as $key => $value) {
                //取出所有菜单在当前语言版本下的显示标题
                $menu_title = json_decode($value['title'], true);
                $menu[$key]['title'] = $menu_title[$this->lang];
                $menu[$key]['children'] = $this->getSubMenu($value['id'], $module);
            }
        }
        return $menu;

    }

    protected function getAdmin()
    {
        $admin_id = session('admin_id');
        $admin = Db::name('admin_user')->find($admin_id);
        $admin['show'] = empty($admin['nickname']) ? $admin['username'] : $admin['nickname'];
        $this->assign('admin', $admin);
    }

}