<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\web\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 字段模型
 * @package app\web\model
 */
class Field extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__WEB_FIELD__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 当前表名
    protected $_table_name = '';

    /**
     * 创建字段
     * @param null $field 字段数据
     * @return bool
     */
    public function newField($field = null)
    {
        if ($field === null) {
            $this->error = '缺少参数';
            return false;
        }
        $field_type = [
            'text'=>'VARCHAR',
            'textarea'=>'TEXT',
            'static'=>'VARCHAR',
            'password'=>'VARCHAR',
            'checkbox'=>'tinyint',
            'radio'=>'tinyint',
            'checkbox'=>'tinyint',
            'date'=>'date',
            'datetime'=>'datetime',
            'hidden'=>'int',
            'switch'=>'tinyint',
            'array'=>'text',
            'select'=>'varchar',
            'linkage'=>'varchar',
            'linkselect'=>'varchar',
            'linkages'=>'varchar',
            'img'=>'varchar',
            'images'=>'text',
            'ueditor'=>'text',
            'icon'=>'varchar',
            'tags'=>'text',
            'number'=>'int',
            'range'=>'varchar'
        ];

        $null_str = $field['allow_null']==1?'NULL':'NOT NULL';
        if (!empty($field['length'])) {
            $type_str = $field_type[$field['type']].'('.$field['length'].') ';
        } else {
            $type_str = $field_type[$field['type']].' ';
        }

        if (!empty($field['value'])) {
            $type_str .= 'DEFAULT "'.$field['value'].'" ';
        }
        
        $field['define'] = $type_str.$null_str;

        if ($this->tableExist($field['model'])) {
            $sql = <<<EOF
            ALTER TABLE `{$this->_table_name}`
            ADD COLUMN `{$field['name']}` {$field['define']} COMMENT '{$field['title']}';
EOF;
        } else {
            $mdoel_title = get_model_title($field['model']);

            // 新建普通扩展表
            $sql = <<<EOF
                CREATE TABLE IF NOT EXISTS `{$this->_table_name}` (
                `aid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文档id' ,
                `{$field['name']}` {$field['define']} COMMENT '{$field['title']}' ,
                PRIMARY KEY (`aid`)
                )
                ENGINE=MyISAM
                DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
                CHECKSUM=0
                ROW_FORMAT=DYNAMIC
                DELAY_KEY_WRITE=0
                COMMENT='{$mdoel_title}模型扩展表'
                ;
EOF;
        }

        try {
            Db::execute($sql);
        } catch(\Exception $e) {
            $this->error = '字段添加失败';
            return false;
        }

        return true;
    }

    /**
     * 更新字段
     * @param null $field 字段数据
     * @return bool
     */
    public function updateField($field = null)
    {
        if ($field === null) {
            return false;
        }

        $field_type = [
            'text'=>'VARCHAR',
            'textarea'=>'TEXT',
            'static'=>'VARCHAR',
            'password'=>'VARCHAR',
            'checkbox'=>'tinyint',
            'radio'=>'tinyint',
            'checkbox'=>'tinyint',
            'date'=>'date',
            'datetime'=>'datetime',
            'hidden'=>'int',
            'switch'=>'tinyint',
            'array'=>'text',
            'select'=>'varchar',
            'linkage'=>'varchar',
            'linkselect'=>'varchar',
            'linkages'=>'varchar',
            'img'=>'varchar',
            'images'=>'text',
            'ueditor'=>'text',
            'icon'=>'varchar',
            'tags'=>'text',
            'number'=>'int',
            'range'=>'varchar'
        ];

        $null_str = $field['allow_null']==1?'NULL':'NOT NULL';
        if (!empty($field['length'])) {
            $type_str = $field_type[$field['type']].'('.$field['length'].') ';
        } else {
            $type_str = $field_type[$field['type']].' ';
        }

        if (!empty($field['value'])) {
            $type_str .= 'DEFAULT "'.$field['value'].'" ';
        }
        
        $field['define'] = $type_str.$null_str;

        // 获取原字段名
        $field_name = $this->where('id', $field['id'])->value('name');

        if ($this->tableExist($field['model'])) {
            $sql = <<<EOF
            ALTER TABLE `{$this->_table_name}`
            CHANGE COLUMN `{$field_name}` `{$field['name']}` {$field['define']} COMMENT '{$field['title']}';
EOF;
            try {
                Db::execute($sql);
            } catch(\Exception $e) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除字段
     * @param null $field 字段数据
     * @return bool
     */
    public function deleteField($field = null)
    {
        if ($field === null) {
            return false;
        }

        if ($this->tableExist($field['model'])) {
            $sql = <<<EOF
            ALTER TABLE `{$this->_table_name}`
            DROP COLUMN `{$field['name']}`;
EOF;
            try {
                Db::execute($sql);
            } catch(\Exception $e) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查表是否存在
     * @param string $model 文档模型id
     * @return bool
     */
    private function tableExist($model = '')
    {
        if ($model==0) {
            $table = config('database.prefix').'web_content';
        } else {
            $table = Db::name('web_model') ->where('id',$model) ->value('table');
        }
        
        $this->_table_name = strtolower($table);
        return true == Db::query("SHOW TABLES LIKE '{$this->_table_name}'");
    }
}