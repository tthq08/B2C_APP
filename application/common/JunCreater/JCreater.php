<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 表格、表单生成器
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\common\JunCreater;

use think\Controller;
use think\Exception;

class JCreater extends Controller
{
    
    /**
     * 生成列表页列表表头
     * @param array $header 表头参数
     * @return $tb_head 组合后的表头参数数组
     */
    public function table_filter($filter=[])
    {
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组),(验证),'']
        $title = ['title','type','name','value','placeholder','tips','option','verify'];
        // 定义diy字段，这些字段需要引用各自的资源文件
        $field_diy = ['tags','ueditor','images'];
        $content = "<form class='layui-form' id='{$filter['post_id']}' method='{$filter['method']}' action='".url($filter['action'])."'><div class='layui-form-item'>";
        $fields = $filter['fields'];
        foreach ($fields as $key => $field) {
            foreach ($title as $kk => $node) {
                $item = isset($field[$kk])?$field[$kk]:'';
                $array[$node] = $item;
            }

            // 如果字段是diy字段，由于diy字段需要引用独立的资源文件，所以设置只在每种diy字段第一次加载时引用资源，后面再出现时不再引用。下面即是记录diy字段出现的次数
            if (in_array($array['type'], $field_diy)) {
                if (isset($times[$array['type']])) {
                    $times[$array['type']] += 1;                    
                }else{
                    $times[$array['type']] = 1; 
                }
                $this->assign('times',$times);
            }
            $array['line'] = 1;
            $this->assign($array);
            $content_field = $this->fetch('common/JunCreater@field/'.$array['type']);

            $content .= $content_field;
        }

        $content .= "   <button type='submit' class='layui-btn layui-btn' title='{$filter['btn_title']}'>{$filter['btn_title']}</button></div></form>";
//        dump($content);
        return $content;
    }
    
    /**
     * 生成列表页列表表头
     * @param array $header 表头参数
     * @return $tb_head 组合后的表头参数数组
     */
    public static function table_header($header=[])
    {
        $title = ['field','title','type','url','pram','options','css'];
        $tb_head = [];
        foreach ($header as $key => $head) {
            foreach ($title as $kk => $node) {
                $item = isset($head[$kk])?$head[$kk]:'';
                $array[$node] = $item;
            }
            $tb_head[] = $array;
        }
        return $tb_head;
    }

    /**
     * 生成列表页各行操作按钮
     * @param array $btnArray 按钮设置数组
     * @return $tb_btn 组合后的表头参数数组
     */
    public static function table_btn($btnArray=[])
    {
        $title = ['title','type','msg','icon','class','url','pram','condition','show_title'];
        $tb_btn = [];
        foreach ($btnArray as $key => $btn) {
            foreach ($title as $kk => $node) {
                $item = isset($btn[$kk])?$btn[$kk]:'';
                $array[$node] = $item;
            }
            $tb_btn[] = $array;
        }
        return $tb_btn;
    }

    /**
     * 生成列表页选项卡
     * @param array $tabs 选项卡参数数组
     * @param array $tab_now 当前激活 Tab 的标记
     * @return array 组合后的选项卡参数数组
     */
    public static function setTabNav($tabs=[],$tab_now='')
    {
        return ['nav_list'=>$tabs,'tab_now'=>$tab_now];
    }

    /**
     * 生成表单页
     * @param array $fields 表单中字段集合数组
     * @return array 组合后的页面HTML代码
     */
    public function form_build($fields=[])
    {
        // [标题,类型,字段,(数据值),(placehoder文本),(提示文本),(选项数组)]
        $title = ['title','type','name','value','placeholder','tips','option','verify'];
        // 定义diy字段，这些字段需要引用各自的资源文件
        $field_diy = ['tags','ueditor','images'];
        $content = '';
        foreach ($fields as $key => $field) {
            foreach ($title as $kk => $node) {
                $item = isset($field[$kk])?$field[$kk]:'';
                $array[$node] = $item;
            }

            // 如果字段是diy字段，由于diy字段需要引用独立的资源文件，所以设置只在每种diy字段第一次加载时引用资源，后面再出现时不再引用。下面即是记录diy字段出现的次数
            if (in_array($array['type'], $field_diy)) {
                if (isset($times[$array['type']])) {
                    $times[$array['type']] += 1;                    
                }else{
                    $times[$array['type']] = 1; 
                }
                $this->assign('times',$times);
            }
            
            $this->assign($array);
            $content .= $this->fetch('common/JunCreater@field/'.$array['type']);
        }
        return $content;
    }
}
?>