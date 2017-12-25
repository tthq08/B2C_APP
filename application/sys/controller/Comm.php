<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 公共功能控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------


namespace app\sys\controller;

use think\Config;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use geetest\GeetestLib;

class Comm extends Controller
{
    // 切换语言
    public function changeLang($lang = '')
    {
        cookie('think_var', $lang);
    }

    // ajax方式获取正常状态的栏目ID及名称
    public function ajaxGetLang()
    {
        // $cates = Db::name('cms_cate')->field('id,name') ->where('status',1) ->select();
        $cates = Db::name('lang')->where('status', 1)->column('title', 'id');
        if ($cates) {
            return ['code' => 1, 'msg' => 'success', 'data' => $cates];
        } else {
            return ['code' => 0, 'msg' => 'Data null', 'data' => []];
        }
    }

    // ajax方式获取正常状态下的非系统模块
    public function ajaxGetModule()
    {
        $lang = cookie('think_var');
        $modules = Db::name('admin_module')->where(['system_module' => 0, 'status' => 1])->select();
        foreach ($modules as $key => $mod) {
            $mod_name = json_decode($mod['title'], true);
            $mod_name = $mod_name[$lang];
            $module[$mod['name']] = $mod_name;
        }
        return ['code' => 1, 'msg' => 'success', 'data' => $module];
    }

    /**
     * 清空系统缓存
     */
    public function clear()
    {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            Cache::clear();
            // 去除商品图片缓存
//            $this->clearGoodsThumb();
            $this->success(lang('clear_success'));
        } else {
            $this->error(lang('clear_error'));
        }
    }


    /**
     * 清楚商品图片缓存
     * @return mixed
     */
    public function clearGoodsThumb()
    {
        $dirName = str_replace('\\', '/', ROOT_PATH.PUBLIC_PATH . '/uploads/goods/thumb/');
        $op = dir($dirName);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . '/' . $item)) {
                $path = $op->path . '/' . $item;
                $pathOP = dir($path);
                while(false != ($item2 = $pathOP->read())){
                    if ($item2 == '.' || $item2 == '..') {
                        continue;
                    }
                    unlink($path . '/' . $item2);
                }
                rmdir($op->path . '/' . $item);
            } else {
                unlink($op->path . '/' . $item);
            }

        }
    }


    /**
     * 获取指定模块下的目录列表
     */
    public function getMenu($module)
    {
        $menu = [];
        $admin_id = Session::get('admin_id');
        $lang = cookie('think_var');
        $auth_rule_list = Db::name('auth_rule')->where(['module' => $module])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
        foreach ($auth_rule_list as $value) {
            //取出所有菜单在当前语言版本下的显示标题
            $menu_title = json_decode($value['title'], true);
            $value['title'] = $menu_title[$lang];
            $menu[$value['id']] = $value;
        }
        // $menu = !empty($menu) ? array2tree($menu) : [];
        if (!empty($menu)) {
            $menu = array2tree($menu);
            $menu = $this->getMenuTree($menu);
        } else {
            $menu = [];
        }
        $Controller = $this->getController($module);
        return ['code' => 1, 'msg' => lang('comm_getdata_success'), 'controller' => $Controller, 'menus' => $menu];
    }

    // 拼接出树形结构的字符菜单组
    public function getMenuTree($menus, $str = '', $field = 'title')
    {
        $tree = [];
        foreach ($menus as $key => $menu) {
            $tree[] = ['id' => $menu['id'], 'title' => $str . $menu[$field]];
            if (!empty($menu['children'])) {
                $strs = $str . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                $subs = $this->getMenuTree($menu['children'], $strs);
                $tree = array_merge($tree, $subs);
            }
        }
        return $tree;
    }

    // 获取指定模块下的控制器列表
    public function getController($module)
    {
        $systemModule = ['sys', 'user'];
        $systemController = ['Comm', 'AdminBase'];

        if (in_array($module, $systemModule)) {
            $controller_dir = APP_PATH . $module . '/controller';
        } else {
            $controller_dir = APP_PATH . $module . '/admin';
        }
        $allCon = getFile($controller_dir, false);
        // dump($controller_dir);
        $conn = [];
        foreach ($allCon as $key => $con) {
            if (!in_array($con, $systemController)) {
                $conn[] = $con;
            }
        }
        return $conn;

    }

    // 获取指定控制器中的方法列表
    public function getAction($module, $controller)
    {
        $systemModule = ['sys', 'user'];
        if (in_array($module, $systemModule)) {
            $controller_dir = APP_PATH . $module . '/controller';
        } else {
            $controller_dir = APP_PATH . $module . '/admin';
        }
        $controller_path = $controller_dir . '/' . $controller . '.php';
        $content = file_get_contents($controller_path);
        preg_match_all("/.*?public.*?function(.*?)\(.*?\)/i", $content, $matches);
        $functions = $matches[1];
        foreach ($functions as $fun) {
            $func[] = ltrim($fun);
        }
        return ['code' => 1, 'msg' => lang('comm_getdata_success'), 'action' => $func];

    }

    //获取所有的模板目录
    public function getAllTpl()
    {
        $base_dir = config('tpl_base');
        $tpl_list = getDir($base_dir);
        foreach ($tpl_list as $key => $tpl) {
            $tpls[$tpl] = $tpl;
        }
        return ['code' => 1, 'msg' => 'success', 'data' => $tpls];
    }

    // 验证极验验证是否通过
    public function VerifyLoginGeetest()
    {
        $gee_token = "think_biz_" . NOW_TIME;
        $GtSdk = new GeetestLib(tb_config('gee_appid', 1, 1), tb_config('gee_key', 1, 1));
        $status = $GtSdk->pre_process($gee_token);
        session('gtserver', $status);
        session('gee_token', $gee_token);
        echo $GtSdk->get_response_str();
    }

    public function getMap($position)
    {
        if (empty($position) || $position == ',') {
            $position_str = '';
        } else {
            $position_str = '//设置中心点
                    map.setZoomAndCenter(14, [' . $position . ']);
                   //标注中心点
                    var marker = new AMap.Marker({
                        map: map,
                        position: [' . $position . ']
                    });';
        }

        echo '<!doctype html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width">
                    <title>鼠标拾取地图坐标</title>
                    <link rel="stylesheet" href="http://cache.amap.com/lbs/static/main1119.css"/>
                    <script type="text/javascript"
                            src="http://webapi.amap.com/maps?v=1.3&key=b68654fea291cff97020f5342b000f57&plugin=AMap.Autocomplete"></script>
                    <script type="text/javascript" src="http://cache.amap.com/lbs/static/addToolbar.js"></script>
                </head>
                <body>
                <div id="container"></div>
                <div id="myPageTop">
                    <table>
                        <tr>
                            <td>
                                <label>按关键字搜索：</label>
                            </td>
                            <td class="column2">
                                <label>左击获取经纬度：</label>
                            </td>
                            <td rowspan="2">
                                <button class="layui-btn" style="background: #009688;color: #fff;border: 1px solid;padding: 5px 12px;" onclick="confirm();">确认坐标</button>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <input type="text" placeholder="请输入关键字进行搜索" id="tipinput">
                            </td>
                            <td class="column2">
                                <input type="text" readonly="true" id="lnglat">
                            </td>
                        </tr>
                    </table>
                </div>
                <script type="text/javascript">
                    var map = new AMap.Map("container", {
                        resizeEnable: true,
                        zoom:14,
                    });

                    ' . $position_str . '
                   
                    //为地图注册click事件获取鼠标点击出的经纬度坐标
                    var clickEventListener = map.on("click", function(e) {
                        document.getElementById("lnglat").value = e.lnglat.getLng() + "," + e.lnglat.getLat()
                    });
                    var auto = new AMap.Autocomplete({
                        input: "tipinput"
                    });
                    AMap.event.addListener(auto, "select", select);//注册监听，当选中某条记录时会触发
                    function select(e) {
                        if (e.poi && e.poi.location) {
                            map.setZoom(15);
                            map.setCenter(e.poi.location);
                        }
                    }

                    function confirm()
                    {
                        var position = document.getElementById("lnglat").value;
                        parent.map_call_back(position);
                    }
                </script>
                </body>
                </html>';
    }

}