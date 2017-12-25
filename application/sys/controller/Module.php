<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 模块管理控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\sys\model\Module as ModuleModel;
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

/**
 * 后台菜单
 * Class Menu
 * @package app\Sys\controller
 */
class Module extends AdminBase
{
	protected function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
    	$ModuleModel = new ModuleModel();
    	$result = $ModuleModel->getAllModule();
    	$this->assign('modules',$result);
    	return $this->fetch();
    }

    // 启用模块
    public function enable($id)
    {
        $res = Db::name('admin_module') ->where('id',$id) ->setField('status',1);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }        
    }

    // 禁用模块
    public function disable($id)
    {
        $res = Db::name('admin_module') ->where('id',$id) ->setField('status',0);
        if ($res!==false) {
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_update_success'));
        } else {
            sys_log(lang('comm_update_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_update_error'));
        }  
    }

    // 导出模块
    public function export($name)
    {
        if ($name == '') return $this->error('缺少模块名');

        // 模块导出目录
        $module_dir = ROOT_PATH. 'public/data/export/module/'. $name;
        // 删除旧的导出数据
        if (is_dir($module_dir)) {
            File::del_dir($module_dir);
        }

        // 复制模块目录到导出目录
        File::copy_dir(APP_PATH. $name, $module_dir);

        // 模块本地配置信息
        $module_info = ModuleModel::getInfoFromFile($name);
        // 检查是否有模块设置信息
        if (isset($module_info['config'])) {
            $db_config = ModuleModel::where('name', $name)->value('config');
            $db_config = json_decode($db_config, true);
            // 获取最新的模块设置信息
            $module_info['config'] = set_config_value($module_info['config'], $db_config);
        }

        // 表前缀
        $module_info['database_prefix'] = config('database.prefix');

        // 导出数据库表
        $install_sql = $module_dir. '/sql/install.sql';
        if( !is_dir($module_dir. '/sql') ){
            mkdir($module_dir. '/sql');
            chmod($module_dir. '/sql',0777);
        }
        $unstall_sql = $module_dir. '/sql/uninstall.sql';
        if (isset($module_info['tables']) && !empty($module_info['tables'])) {
            if (!Database::export($module_info['tables'], $install_sql, config('database.prefix'), 1)) {
                return $this->error('数据库文件创建失败，请重新导出');
            }
            if (!Database::exportUninstall($module_info['tables'],$unstall_sql, config('database.prefix'))) {
                return $this->error('数据库文件创建失败，请重新导出');
            }
        }

        // 获取模型菜单并导出
        $fields = 'id,module,name,attach,title,level,type,status,pid,icon,sort,contet_sign';
        $menus = Db::name('auth_rule') ->where('module',$name) ->column($fields,'id');

        if (false === $this->buildMenuFile($menus, $name)) {
            return $this->error('模型菜单文件创建失败，请重新导出');
        }

        //插入菜单数据
        // $menu_SQL = Database::getSqlWhere(config('database.prefix').'auth_rule',"module='{$name}'");
        // File::write_file($install_sql,File::read_file($install_sql).$menu_SQL);
        // $sql  = "-- -----------------------------\n";
        // $sql .= "-- 删除 `{$name}` 模块相关菜单\n";
        // $sql .= "-- -----------------------------\n";
        // $sql .= "DELETE FROM ".config('database.prefix')."auth_rule WHERE module='{$name}'";
        // File::write_file($unstall_sql,File::read_file($unstall_sql).$sql);
        // 记录行为
        sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
        // 打包下载
        $archive = new PHPZip;
        return $archive->ZipAndDownload($module_dir, $name);
    }

        /**
     * 创建模块菜单文件
     * @param array $menus 菜单
     * @param string $name 模块名
     * @return int
     */
    private function buildMenuFile($menus = [], $name = '')
    {
        $menus = Tree::toLayer($menus);

        // 美化数组格式
        $menus = var_export($menus, true);
        $menus = preg_replace("/(\d+|'id'|'pid') =>(.*)/", '', $menus);
        $menus = preg_replace("/'child' => (.*)(\r\n|\r|\n)\s*array/", "'child' => $1array", $menus);
        $menus = str_replace(['array (', ')'], ['[', ']'], $menus);
        $menus = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $menus);

        $content = <<<INFO
<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 门户模块内容控制器 
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

/**
 * 菜单信息
 */
return {$menus};

INFO;
        // 写入到文件
        $filePath = ROOT_PATH. 'public/data/export/module/'. $name. '/menus.php';

        return file_put_contents($filePath, $content);

    }

    // 安装模块
    public function install($name)
    {
        if ($name == '') return $this->error('模块不存在！');
        if ($name == 'sys' || $name == 'user' ) return $this->error('禁止操作系统模块！');

        // 模块配置信息
        $module_info = ModuleModel::getInfoFromFile($name);

        // 执行安装模块sql文件
        $sql_file = realpath(APP_PATH.$name.'/sql/install.sql');
        if (file_exists($sql_file)) {
            if (isset($module_info['database_prefix']) && !empty($module_info['database_prefix'])) {
                $sql_statement = Sql::getSqlFromFile($sql_file, false, [$module_info['database_prefix'] => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file);
            }
            if (!empty($sql_statement)) {
                foreach ($sql_statement as $value) {
                    try{
                        Db::execute($value);
                    }catch(\Exception $e){
                        $this->error('导入SQL失败，请检查install.sql的语句是否正确');
                    }
                }
            }
        }

        // 添加菜单
        $menus = ModuleModel::getMenusFromFile($name);
        if (is_array($menus) && !empty($menus)) {
            if (false === $this->addMenus($menus, $name)) {
                return $this->error('菜单添加失败，请重新安装');
            }
        }


        // 将模块信息写入数据库
        unset($module_info['tables'],$module_info['database_prefix']);
        $res = Db::name('admin_module') ->insert($module_info);

        if ($res) {
            cache('modules', null);
            cache('module_all', null);
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            return $this->success('模块安装成功', 'index');
        } else {
            MenuModel::where('module', $name)->delete();
            return $this->error('模块安装失败');
        }
    }

     /**
     * 添加模型菜单
     * @param array $menus 菜单
     * @param string $module 模型名称
     * @param int $pid 父级ID
     * @return bool
     */
    private function addMenus($menus = [], $module = '', $pid = 0)
    {
        foreach ($menus as $menu) {
            $data = [
                'pid'         => $pid,
                'module'      => $module,
                'title'       => $menu['title'],
                'name'        => $menu['name'],
                'attach'      => $menu['attach'],
                'level'       => $menu['level'],
                'icon'        => isset($menu['icon']) ? $menu['icon'] : 'fa fa-fw fa-puzzle-piece',
                'type'        => isset($menu['type']) ? $menu['type'] : '1',
                'status'      => $menu['status'],
                'sort'        => $menu['sort'],
                'contet_sign' => $menu['contet_sign'],
            ];

            $result = Db::name('auth_rule') ->insertGetId($data);
            if (!$result) return false;

            if (isset($menu['child'])) {
                $this->addMenus($menu['child'], $module, $result);
            }
        }

        return true;
    }

    // 卸载模块
    public function unstall($id)
    {
        if ($id == '') return $this->error('模块不存在！');
        $module = Db::name('admin_module') ->find($id);
        if ($module['system_module'] == 1 ) return $this->error('禁止操作系统模块！');

        $name = $module['name'];
        // 模块配置信息
        $module_info = ModuleModel::getInfoFromFile($name);

        // 执行卸载模块sql文件

        $sql_file = realpath(APP_PATH.$name.'/sql/uninstall.sql');
        if (file_exists($sql_file)) {
            if (isset($module_info['database_prefix']) && !empty($module_info['database_prefix'])) {
                $sql_statement = Sql::getSqlFromFile($sql_file, false, [$module_info['database_prefix'] => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file);
            }

            if (!empty($sql_statement)) {
                foreach ($sql_statement as $sql) {
                    try{
                        Db::execute($sql);
                    }catch(\Exception $e){
                        $this->error('卸载失败，请检查uninstall.sql的语句是否正确');
                    }
                }
            }
        }

        // 删除菜单
        if (false === Db::name('auth_rule')->where('module', $name)->delete()) {
            return $this->error('菜单删除失败，请重新卸载');
        }

        // 删除模块信息
        if (ModuleModel::where('name', $name)->delete()) {
            cache('modules', null);
            cache('module_all', null);
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            return $this->success('模块卸载成功', 'index');
        } else {
            return $this->error('模块卸载失败');
        }
    }


    /**
     * 配置管理
     * @param $module
     * @return mixed
     */
    public function config($module)
    {
        $module = Db::name('admin_module')->where('id',$module)->find();
        $config = Db::name('admin_module_config')->where('module',$module['name'])->find();
        // 获取模板地址
        $tmpList = $this->getTemplateList();

        $this->assign(['config'=>$config,'tmpList'=>$tmpList,'module'=>$module]);
        return $this->fetch();
    }


    /**
     * 配置保存
     * @return mixed
     */
    public function configUpdate()
    {
        $data = request()->post();
        if( empty($data['home_template']) ){
            $this->error('请选择PC模板');
        }
        elseif( empty($data['mobile_template']) ){
            $this->error('请选择手机模板');
        }
        // 检查PC模板是否重复
        $data['id'] = empty($data['id']) ? 0:$data['id'];
        $isPCExist = Db::name('admin_module_config')->where('id <> '.$data['id'])->where('home_template',$data['home_template'])->find();
        if( !empty($isPCExist) ){
            $this->error('当前手机模板已存在于其他模块');
        }
        // 检查手机模板是否重复
        $isMobileExist = Db::name('admin_module_config')->where('id <> '.$data['id'])->where('mobile_template',$data['mobile_template'])->find();
        if( !empty($isMobileExist) ){
            $this->error('当前手机模板已存在于其他模块');
        }
        if( !empty($data['id']) ){
            $save = Db::name('admin_module_config')->where('id',$data['id'])->update($data);
        }else{
            $save = Db::name('admin_module_config')->insert($data);
        }
        if( $save !== false ){
            $this->success('保存成功!');
        }else{
            $this->error('保存失败!');
        }
    }


    /**
     * 获取模板列表
     * @return mixed
     */
    public function getTemplateList()
    {
        $path = config('template.home_view_path');
        $dirList = getDir($path);
        return $dirList;
    }

}