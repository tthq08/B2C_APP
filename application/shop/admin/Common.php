<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/9
 * Time: 上午11:06
 */

namespace app\shop\admin;


use app\sys\controller\AdminBase;

class Common extends AdminBase
{


    /**
     * 获取商品分类
     * 三级联动选择
     * @return mixed
     */
    public function goodsCategory()
    {
        if( !empty(request()->param('pid')) ){
            $pid = request()->param('pid');
        }else{
            $pid = 0;
        }
        $goodsCategory = api('shop','Goods','category',$pid);
        if( empty($goodsCategory) ){
            $this->error('该分类没有下级分类');
        }
        $categoryTemp = '<option value="">请选择</option>';
        foreach ($goodsCategory as $category)
        {
            $categoryTemp .= '<option value="'.$category['id'].'">'.$category['name'].'</option>';
        }
        $this->success($categoryTemp);
    }

}