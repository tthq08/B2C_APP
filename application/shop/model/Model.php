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

namespace app\shop\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 内容模型
 * @package app\shop\model
 */
class Model extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__SHOP_MODEL__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 删除数据表
     * @param null $model 内容模型id
     * @return bool
     */
    public static function deleteTable($model = null)
    {
        if ($model === null) {
            return false;
        }

        $table_name = self::where('id', $model)->value('table');
        return false !== Db::execute("DROP TABLE IF EXISTS `{$table_name}`");
    }

    /**
     * 创建独立模型表
     * @param mixed $data 模型数据
     * @return bool
     */
    public static function createTable($data)
    {
        if ($data['type'] == 2) {
            // 新建独立扩展表
            $sql = <<<EOF
            CREATE TABLE IF NOT EXISTS `{$data['table']}` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文档id' ,
            `cid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '栏目id' ,
            `uid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id' ,
            `uname` VARCHAR(200) NULL DEFAULT '' COMMENT '发布人' ,
            `lang` int(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '语言id' ,
            `model` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模型id' ,
            `title` varchar(256) NOT NULL DEFAULT '' COMMENT '标题' ,
            `create_time` DATETIME NOT NULL COMMENT '创建时间' ,
            `update_time` DATETIME NOT NULL COMMENT '更新时间' ,
            `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序' ,
            `status` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态' ,
            `view` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击量' ,
            `trash` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回收站' ,
            PRIMARY KEY (`id`)
            )
            ENGINE=MyISAM
            DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
            CHECKSUM=0
            ROW_FORMAT=DYNAMIC
            DELAY_KEY_WRITE=0
            COMMENT='{$data['title']}模型表'
            ;
EOF;
        } else {
            // 新建普通扩展表
            $sql = <<<EOF
                CREATE TABLE IF NOT EXISTS `{$data['table']}` (
                `aid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文档id' ,
                PRIMARY KEY (`aid`)
                )
                ENGINE=MyISAM
                DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
                CHECKSUM=0
                ROW_FORMAT=DYNAMIC
                DELAY_KEY_WRITE=0
                COMMENT='{$data['title']}模型扩展表'
                ;
EOF;
        }

        try {
            Db::execute($sql);
        } catch(\Exception $e) {
            return false;
        }

        if ($data['type'] == 2) {
            // 添加默认字段
            $default = [
                'model'       => $data['id'],
                'level'       => '',
                'create_time' => request()->timer(),
                'update_time' => request()->timer(),
                'status'      => 1
            ];
            $data = [
                [
                    'name'        => 'id',
                    'title'       => '文档id',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'hidden',
                    'show'        => 0
                ],
                [
                    'name'        => 'uid',
                    'title'       => '用户id',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'hidden',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'uname',
                    'title'       => '发布人',
                    'define'      => "VARCHAR(200) DEFAULT '' NULL",
                    'type'        => 'text',
                    'show'        => 1,
                    'value'       => '',
                ],                
                [
                    'name'        => 'model',
                    'title'       => '文档模型',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'hidden',
                    'show'        => 1,
                    'value'       => 0,
                ],
                [
                    'name'        => 'title',
                    'title'       => '标题',
                    'define'      => 'varchar(256) NOT NULL',
                    'type'        => 'text',
                    'show'        => 1
                ],
                [
                    'name'        => 'create_time',
                    'title'       => '创建时间',
                    'define'      => 'DATETIME NOT NULL',
                    'type'        => 'datetime',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'update_time',
                    'title'       => '更新时间',
                    'define'      => 'DATETIME NOT NULL',
                    'type'        => 'datetime',
                    'show'        => 0,
                    'value'       => 0,
                ],
                [
                    'name'        => 'sort',
                    'title'       => '排序',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 1,
                    'value'       => 100,
                ],
                [
                    'name'        => 'status',
                    'title'       => '状态',
                    'define'      => 'tinyint(2) NOT NULL',
                    'type'        => 'radio',
                    'show'        => 1,
                    'value'       => 1,
                    'options'     => '0:禁用,1:启用'
                ],
                [
                    'name'        => 'view',
                    'title'       => '点击量',
                    'define'      => 'int(11) UNSIGNED NOT NULL',
                    'type'        => 'text',
                    'show'        => 0,
                    'value'       => 0
                ],
                [
                    'name'        => 'trash',
                    'title'       => '回收站',
                    'define'      => 'tinyint(2) NOT NULL',
                    'type'        => 'radio',
                    'show'        => 0,
                    'value'       => 0
                ]
            ];

            foreach ($data as $item) {
                $item = array_merge($item, $default);
                Db::name('shop_field')->insert($item);
            }
        }
        return true;
    }
}