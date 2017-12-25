<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/20
 * Time: 上午10:16
 */

namespace app\shop\api;

use think\Db;
class Recommend
{

    /**
     * 获取推荐商品
     * @param int $cid 分类id
     * @param int $is_new 是否是新品
     * @param string $sort 排序
     * @param string $condition 条件
     * @param int $limit 获取数量
     * @return mixed
     */
    public function recommendGoodsList($cid, $is_new = '', $sort = '', $condition = '', $limit = 10)
    {
        $where = '';
        if( !empty($is_new) ){
            $where['is_new'] = $is_new;
        }
        $goodsList = Db::name('shop_goods')->where($where)->where($condition)->where('cat_id',$cid)->order('rand()')->order($sort)->order('sort desc')->limit($limit)->select();
        if( empty($goodsList) ) {
            // 获取当前分类的下级分类
            $cateChildList = Db::name('goods_category')->where('parent_id_path', 'like', '%,' . $cid . ',%')->column('id');
            if (empty($cateChildList)) {
                return $goodsList;
            }
            $goodsList = Db::name('shop_goods')->where($where)->where($condition)->where('cat_id','in',$cateChildList)->order('rand()')->order($sort)->order('sort desc')->limit($limit)->select();
        }
        return $goodsList;
    }

    /**
     * 获取推荐位列表
     * @return mixed
     */
    public function lists()
    {
        $conten = Db::name('shop_recommend')->where(['trash' => 0])->order('sort desc') -> order('id desc')->paginate(tb_config('list_rows', 1));  //取出当前语言版本下的所有记录
        $content_list = $conten->all();
        foreach ($content_list as $key => $con) {
            // 获取推荐位商品数量
            $content_list[$key]['goods_num'] = $this->getGoodsNum($con['id']);
            $content_list[$key]['update_time'] = datetime_format($con['update_time']);
            if( empty($content_list[$key]['cate_id']) ){
                $content_list[$key]['cate_id'] = '通用推荐位';
            }else{
                $content_list[$key]['cate_id'] = getTableValue('goods_category', ['id' => $con['cate_id']], 'name');
            }
        }
        return ['list'=>$content_list,'page'=>$conten->render()];
    }


    /**
     * 获取推荐位基本信息
     * @param $id
     * @return array
     */
    public function recommendInfo($id)
    {
        $info = Db::name('shop_recommend')->find($id);
        return $info;
    }

    /**
     * 获取推荐位商品数量
     * @param $recommend_id
     * @return int
     */
    public function getGoodsNum($recommend_id)
    {
        $where = ['recommend_id'=>$recommend_id,'trash'=>0];
        $num = Db::name('shop_recommend_goods')->where($where)->count();
        return $num;
    }


    /**
     * 获取推荐位列表
     * @return mixed
     */
    public function goodsLists($recommend_id)
    {
        $where = ['recommend_id'=>$recommend_id,'trash'=>0];
        $conten = Db::name('shop_recommend_goods')->where($where)->order('sort')->paginate(tb_config('list_rows', 1));  //取出当前语言版本下的所有记录
        $content_list = $conten->all();
        foreach ($content_list as $key=>$rgoods){
            $content_list[$key]['goods_title'] = getTableValue('shop_goods','id='.$rgoods['goods_id'],'title');
            $content_list[$key]['start_time'] = datetime_format($rgoods['start_time']);
            $content_list[$key]['end_time'] = datetime_format($rgoods['end_time']);
        }
        return ['list'=>$content_list,'page'=>$conten->render()];
    }
}