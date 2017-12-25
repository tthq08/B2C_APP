<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 数据库管理控制器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\sys\controller;

use app\common\JunCreater\JCreater;
use think\Db;
use util\Database as DatabaseModel;

/**
 * 数据库管理
 * @package app\Sys\controller
 */
class Database extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 数据库管理
     * @param string $group 分组
     * @return mixed
     */
    public function index($group = 'export')
    {
        // 配置分组信息
        $list_group = ['export' =>lang('database_tab_title_0'), 'import' => lang('database_tab_title_1') , 'dictionary'=>lang('database_tab_title_2')];
        $tab_list = [];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['sign'] = $key;
            $tab_list[$key]['url']   = url('index', ['group' => $key]);
        }
        $tab = JCreater::setTabNav($tab_list,$group);
        $this->assign($tab);

        switch ($group) {
            case 'export':
                // 是否显示表格的选择列？
                $this->assign('show_check',1);

                $data_list = Db::query("SHOW TABLE STATUS");
                $data_list = array_map('array_change_key_case', $data_list);
                foreach ($data_list as $key => $data) {
                    $data_list[$key]['id'] = $data['name'];
                    $data_list[$key]['data_length'] = get_real_size($data['data_length']);
                }
                // dump($data_list);

                /*设置表格各行的操作按钮，
                * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
                * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
                */
                $btn = [
                    [lang('database_list_btn_optimize'),'confirm',lang('comm_do_confirm_msg'),'fa fa-fw fa-cogs','','Database/optimize','id'],
                    [lang('database_list_btn_repair'),'confirm',lang('comm_do_confirm_msg'),'fa fa-fw fa-wrench','','Database/repair','id'],
                ];
                $table['btn_lst'] = JCreater::table_btn($btn);

                // 设置列表页顶部按钮组
                $top_btn = [
                    [lang('database_list_top_btn_backup'),'confirm_form',lang('database_backup_confirm_msg'),'fa fa-fw fa-copy','','Database/export'],
                    [lang('database_list_top_btn_optimize'),'confirm_form',lang('comm_do_confirm_msg'),'fa fa-fw fa-cogs','','Database/optimize'],
                    [lang('database_list_top_btn_repair'),'confirm_form',lang('comm_do_confirm_msg'),'fa fa-fw fa-wrench','','Database/repair'],
                ];
                $table['top_btn'] = JCreater::table_btn($top_btn);
                /*设置表格的表头，表格将按顺序显示设置的表头
                * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
                * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
                */
                $table_head = [
                    ['name',lang('database_list_title_0'),'text'],
                    ['rows',lang('database_list_title_1'),'text'],
                    ['data_length',lang('database_list_title_2'),'text'],
                    ['data_free',lang('database_list_title_3'),'text'],
                    ['comment',lang('database_list_title_4'),'text'],
                    ['btn',lang('database_list_title_5'),'btn'],
                ];
                $table['tb_title'] = JCreater::table_header($table_head);
                $this->assign($table);

                $this->assign('data',$data_list);
                return $this->fetch('Base/table');
                break;
            case 'import':
                // 是否显示表格的选择列？
                $this->assign('show_check',0);
                // 列出备份文件列表
                $path = tb_config('data_backup_path',1,$this->lang_id);
                if(!is_dir($path)){
                    mkdir($path);
                    chmod($path, 0777);
                }
                $path = realpath($path);
                $flag = \FilesystemIterator::KEY_AS_FILENAME;
                $glob = new \FilesystemIterator($path,  $flag);

                $data_list = [];
                $nums = 1;
                foreach ($glob as $name => $file) {
                    if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)){
                        $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                        $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                        $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                        $part = $name[6];
                        $back_name = "{$name[0]}{$name[1]}{$name[2]}-{$name[3]}{$name[4]}{$name[5]}";

                        if(isset($data_list["{$date} {$time}"])){
                            $info = $data_list["{$date} {$time}"];
                            $info['part'] = max($info['part'], $part);
                            $info['size'] = $info['size'] + $file->getSize();
                        } else {
                            $info['part'] = $part;
                            $info['size'] = get_real_size($file->getSize());
                        }
                        // dump($info);
                        $extension        = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                        $info['compress'] = ($extension === 'SQL') ? '-' : $extension;
                        $info['time']     = "{$date} {$time}";
                        $info['name']     = $back_name;
                        $info['id']       = strtotime($info['time']);
                        $info['number']     = $nums;

                        $data_list["{$date} {$time}"] = $info;
                    }
                    $nums ++;
                }

                $data_list = !empty($data_list) ? array_values($data_list) : $data_list;


                /*设置表格各行的操作按钮，
                * 设置说明： [标题,类型,提示信息,图标,样式Class,执行URL,提交参数]
                * 元素说明：标题 - 按钮的标题；类型 - 按钮触发后的执行类型，有frame(窗口弹层)，confirm(确认执行弹层)，有confirm_form(确认后提交表单)；样式Class - 执行URL - 按钮触发执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
                */
                $btn = [
                    [lang('backup_list_btn_restore'),'confirm',lang('comm_do_confirm_msg'),'fa fa-fw fa-reply','','Database/import','time'],
                    [lang('backup_list_btn_del'),'confirm',lang('comm_do_confirm_msg'),'fa fa-fw fa-trash-o','layui-btn-danger','Database/delete','time'],
                ];
                $table['btn_lst'] = JCreater::table_btn($btn);
                

                /*设置表格的表头，表格将按顺序显示设置的表头
                * 设置说明： [字段,标题,类型,(绑定路径),(提交参数)]
                * 元素说明：字段 - 绑定的数据表的字段；标题 - 表格各列的标题；类型 - 表格各列内容的显示类型，支持text、input、switch；绑定路径 - text类型不支持，input及switch类型执行动作时绑定的URL路径；提交参数 - 向绑定的URL提交的参数名
                */
                $table_head = [
                    ['number','','text'],
                    ['name',lang('backup_list_title_0'),'text'],
                    ['part',lang('backup_list_title_1'),'text'],
                    ['compress',lang('backup_list_title_2'),'text'],
                    ['size',lang('backup_list_title_3'),'text'],
                    ['time',lang('backup_list_title_4'),'text'],
                    ['btn',lang('backup_list_title_5'),'btn'],
                ];
                $table['tb_title'] = JCreater::table_header($table_head);
                $this->assign($table);

                $this->assign('data',$data_list);
                return $this->fetch('Base/table');
                break;

            case 'dictionary':
                $data_list = Db::query("SHOW TABLE STATUS");
                $data_list = array_map('array_change_key_case', $data_list);
                foreach ($data_list as $k => $v) {
                    $sql  = 'SELECT * FROM ';
                    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
                    $sql .= 'WHERE ';
                    $sql .= "table_name = '{$v['name']}' AND table_schema = '".config('database.database')."'";
                    $table_result = Db::query($sql);
                    $data_list[$k]['TABLE_COMMENT'] = $table_result;
                }
                $this->assign('dabase_list',$data_list);
                return $this->fetch('dictionary');
                break;
        }
    }

    /**
     * 备份数据库(参考onthink 麦当苗儿 <zuojiazi@vip.qq.com>)
     * @param null|array $ids 表名
     * @param integer $start 起始行数
     */
    public function export($id = null, $start = 0)
    {
        $tables = $id;
        if ($this->request->isPost() && !empty($tables) && is_array($tables)) {
            // 初始化
            $path = tb_config('data_backup_path',1,$this->lang_id);
            if(!is_dir($path)){
                mkdir($path, 0755, true);
            }
            // 读取备份配置
            $config = array(
                'path'     => realpath($path) . DIRECTORY_SEPARATOR,
                'part'     => tb_config('data_backup_part_size',1,$this->lang_id),
                'compress' => tb_config('data_backup_compress',1,$this->lang_id),
                'level'    => tb_config('data_backup_compress_level',1,$this->lang_id),
            );

            // 检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if(is_file($lock)){
                $this->error(lang('database_export_doing'));
            } else {
                // 创建锁文件
                file_put_contents($lock, $this->request-> NOW_TIME);
            }
            // 检查备份目录是否可写
            is_writeable($config['path']) || $this->error(lang('database_path_error'));

            // 生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', $this->request->time()),
                'part' => 1,
            );

            // 创建备份文件
            $Database = new DatabaseModel($file, $config);

            if(false !== $Database->create()){
                // 备份指定表
                foreach ($tables as $table) {
                    $start = $Database->backup($table, $start);
                    while (0 !== $start) {
                        if (false === $start) { // 出错
                            $this->error(lang('database_export_error'));
                        }
                        $start = $Database->backup($table, $start[0]);
                    }
                }

                // 备份完成，删除锁定文件
                unlink($lock);
                // 记录行为
                // action_log('database_export', 'database', 0, UID, implode(',', $tables));
                sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                $this->success(lang('database_export_success'));
            } else {
                $this->error(lang('database_start_error'));
            }
        } else {
            sys_log(lang('comm_request_error'),0);  //操作结果写入系统日志
            $this->error(lang('comm_request_error'));
        }
    }

    /**
     * 还原数据库(参考onthink 麦当苗儿 <zuojiazi@vip.qq.com>)
     * @param int $time 文件时间戳
     */
    public function import($time = 0)
    {
        if ($time === 0) $this->error(lang('import_code_eror'));

        // 初始化
        $name  = date('Ymd-His', $time) . '-*.sql*';
        $path  = realpath(tb_config('data_backup_path',1,$this->lang_id)) . DIRECTORY_SEPARATOR . $name;
        $files = glob($path);
        $list  = array();
        foreach($files as $name){
            $basename = basename($name);
            $match    = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
            $gz       = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
            $list[$match[6]] = array($match[6], $name, $gz);
        }
        ksort($list);

        // 检测文件正确性
        $last = end($list);
        if(count($list) === $last[0]){
            foreach ($list as $item) {
                $config = [
                    'path'     => realpath(tb_config('data_backup_path',1,$this->lang_id)) . DIRECTORY_SEPARATOR,
                    'compress' => $item[2]
                ];
                $Database = new DatabaseModel($item, $config);
                $start = $Database->import(0);

                // 循环导入数据
                while (0 !== $start) {
                    if (false === $start) { // 出错
                        $this->error(lang('import_eror'));
                    }
                    $start = $Database->import($start[0]);
                }
            }
            // 记录行为
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('import_success'));
        } else {
            $this->error(lang('import_file_error'));
        }
    }

    /**
     * 优化表
     * @param null|string|array $ids 表名
     */
    public function optimize($id = null)
    {
        $tables = $id;

        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list   = Db::query("OPTIMIZE TABLE `{$tables}`");

                if($list){
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('database_optimize_success'));
                } else {
                    $this->error(lang('database_optimize_error'));
                }
            } else {
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");
                if($list){
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('database_optzs_success',[$tables]));
                } else {
                    $this->error(lang('database_optzs_error',[$tables]));
                }
            }
        } else {
            $this->error(lang('database_optimize_empty'));
        }
    }

    /**
     * 修复表
     * @param null|string|array $ids 表名
     */
    public function repair($id = null)
    {
        $tables = $id;
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list = Db::query("REPAIR TABLE `{$tables}`");

                if($list){
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('database_repair_success'));
                } else {
                    $this->error(lang('database_repair_error'));
                }
            } else {
                $list = Db::query("REPAIR TABLE `{$tables}`");
                if($list){
                    sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
                    $this->success(lang('database_reprs_success',[$tables]));
                } else {
                    $this->error(lang('database_reprs_error',[$tables]));
                }
            }
        } else {
            $this->error(lang('database_repair_empty'));
        }
    }

    /**
     * 删除备份文件
     * @param int $ids 备份时间
     * @return mixed
     */
    public function delete($time = 0)
    {
//        if ($time == 0) $this->error(lang('import_code_eror'));

        $name  = date('Ymd-His', $time) . '-*.sql*';
        $path  = realpath(tb_config('data_backup_path',1,$this->lang_id)) . DIRECTORY_SEPARATOR . $name;
        array_map("unlink", glob($path));
        if(count(glob($path))){
            $this->error(lang('delete_file_error'));
        } else {
            // 记录行为
            sys_log(lang('comm_update_success'),1);  //操作结果写入系统日志
            $this->success(lang('delete_file_success'));
        }
    }

}