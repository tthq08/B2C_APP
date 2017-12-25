<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/8/14
 * Time: 下午3:53
 */

namespace app\shop\api;


use think\Db;

class IndexGoods
{

    /**
     * 分页数量
     * @var int
     */
    public $pageNum = 20;


    /**
     * 分页
     * @var string
     */
    public $render;


    /**
     * 获取首页商品
     * @return array
     */
    public function select()
    {
        $where = [
            '`is_index`' => 1,
            '`status`' => 1,
            '`trash`' => 0,
        ];
        // 多表查询,查询咨询师名称
        $goodsList = Db::name('shop_goods')->where('sg.is_audit',1)->where($where)->field('id,title,goods_remark,thumb,stock,view,goods_sn,market_price,shop_price,shop_id')->paginate($this->pageNum);

        $this->render = $goodsList->render();

        $data = $goodsList->items();
        return $data;
    }


    /**
     * 获取平台推荐商品
     * @return mixed
     */
    public function recommend()
    {
        if( empty(cache('recommend')) )
        {
            $where = [
                '`status`' => 1,
                '`trash`' => 0,
                'is_comm' => 1,
            ];
            $goodsList = Db::name('shop_goods')->where($where)->field('id,title,goods_remark,thumb,stock,view,goods_sn,market_price,stock,shop_price')->select();
            cache('recommend',$goodsList,'','shop-recommend');
        }else{
            $goodsList = cache('recommend');
        }


        return $goodsList;
    }




}