<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/21
 * Time: 上午11:20
 */

namespace app\shop\api;

use think\Db;

class SearchGoods
{

    /**
     * 已选参数
     * @var array
     */
    protected $selectedParam;


    /**
     * 搜索出来的分类
     * @var array
     */
    protected $category;

    /**
     * 关键字
     * @var string
     */
    protected $search;

    /**
     * 排序方式
     * @var array
     */
    protected $sort;


    /**
     * 用户筛选参数
     * @var array
     */
    protected $param;


    /**
     * 设置分页条数
     * @var int
     */
    protected $pageRows;


    /**
     * 商品总数量
     * @var int
     */
    protected $goodsCount;


    public function __construct()
    {
        $this->param = request()->param();
        // 参数设置
        $this->param();
        $this->sort();
    }


    /**
     * 获取商品列表中的信息
     * @return mixed
     */
    public function goodsList()
    {
        // 设置查询条件
        $screenParam = $GLOBALS['screen'];
        $where = '';
        if( !empty($screenParam) ){
            $i = 1;
            $countScreen = count($screenParam);
            foreach ($screenParam as $key=>$item){
                $where .= '(sga.`attr_id` = "'.$key.'" and FIND_IN_SET("'.$item.'",sga.attr_value))';
                if( $i < $countScreen ){
                    $where .= ' OR ';
                }
                $i++;
            }
        }
        $order = 'sg.view+sg.sales_sum*10';
        $priceSection = $this->price();
        $brandCondition = $this->brand();

        $categoryKeysSearch = '`name` like "%'.$this->search.'%"';
        saveSearchLog($this->search);
        // 获取分类列表
        $categoryTopList = Db::name('goods_category')
            // 查询条件
            ->where($categoryKeysSearch)
            ->column('id');
        $categoryTopList2 = implode(',',$categoryTopList);
        if( !empty($categoryTopList2) ){
            $categoryList = Db::name('goods_category')
                // 查询条件
                ->where('pid','in',$categoryTopList2)
                ->column('id');
            $categoryList = array_merge($categoryList,$categoryTopList);
        }else{
            $categoryList = [];
        }
        $categoryList = implode(',',$categoryList);
        $searchCategory = empty($categoryList) ? '' : ' or sg.`cat_id` in ('.$categoryList.')';
        // 关键字查询
        $keysSearch = '( sg.`title` like "%'.$this->search.'%" or sg.`name` like "%'.$this->search.'%" '.$searchCategory.')';
        // 获取商品列表
        $goods_list = Db::name('shop_goods')
            // 两表关联
            ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id')
            // 查询条件
            ->where($keysSearch) ->where($brandCondition) ->where($priceSection) ->where(['sg.status'=>1,'sg.trash'=>0]) ->where($where)
            // 查询字段,分组
            ->field('sg.id,sg.is_new,sg.is_comm,sg.thumb,sg.market_price,sg.shop_price,sg.name,sg.title,sg.url,sg.id,sg.sales_sum,cat_id,count(id) as count') ->group('sg.`id`')
            // 查询排序
            ->order($this->sort) ->order('sg.sort') ->order($order)
            ->paginate($this->pageRows);


        // 获取商品总数
        $this->goodsCount = Db::name('shop_goods') ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id') ->where($keysSearch) ->where($brandCondition) ->where($priceSection) ->where(['sg.status'=>1,'sg.trash'=>0]) ->where($where)->field('count(id) as count') ->group('sg.`id`') ->order($this->sort) ->order('sg.sort') ->order($order) ->count();

        // 获取分类
        foreach ($goods_list as $key=>$goods) {
            $this->category[] = $goods['cat_id'];
        }



        return $goods_list;
    }


    /**
     * 获取当前分类下的商品总数量
     * @return mixed
     */
    public function getGoodsCount()
    {
        return $this->goodsCount;
    }


    /**
     * 获取当前分类下的品牌
     * @return mixed
     */
    public function brandList()
    {
        $brandList = [];
        // 获取分类下的品牌
        if( empty( array_key_exists('brand',$this->param) ) ){
            if( empty($this->category) ){
                return null;
            }
            foreach ($this->category as $key=>$cate){
                $list = api('shop','Goods','getBrandList',[$cate]);
                $brandList = array_merge($brandList,$list);
            }
        }
        return $brandList;
    }

    /**
     * 获取当前分类的属性
     * @return mixed
     */
    public function attrList()
    {
        $attrList = api('shop','Goods','getAttrList',['']);
        $attribute = [];
        foreach ($attrList as $key=>$item){
            if( !array_key_exists($item['id'],$GLOBALS['screen']) ){
                $attribute[$key] = $item;
                $attribute[$key]['attr_values'] = explode(',',$item['attr_values']);
            }
        }
        return $attribute;
    }


    /**
     * 设置查询关键词
     * @param $keyword
     * @return $this
     */
    public function setKeyword($keyword)
    {
        $this->search = $keyword;
        return $this;
    }


    /**
     * 设置分页条数
     * @param $pageRows
     * @return $this
     */
    public function setPageRows($pageRows)
    {
        $this->pageRows = $pageRows;
        return $this;
    }


    /**
     * 参数设置
     */
    public function param()
    {
        $param = $this->param ;
        // 参数筛选
        $screenParam = [];
        $urlArray = preg_split('/(screen=){1}([^&]+)(&)?/',request()->url());
        if( !empty($param['screen']) ){
            // 如果存在模板后缀,去除
            if( strpos($param['screen'],'.'.config('url_html_suffix')) ){
                $param['screen'] = mb_substr($param['screen'],0,mb_strlen($param['screen'])-5);
            }
            $screenParamArray = !empty($param['screen']) ? explode('%',$param['screen']) : '';
            for ($i=0;$i<count($screenParamArray);$i++){
                $screen = explode('_',$screenParamArray[$i]);
                if( empty($screen[0])){
                    array_shift($screen);
                }
                $screenParam[$screen[0]] = $screen[1];
                $this->selectedParam[] = [getTableValue('shop_attribute','id='.$screen[0],'attr_name'),$screen[1],'screen',$screen[0]];
            }
        }
        $GLOBALS['screen'] = $screenParam;
        $GLOBALS['screenUrlArray'] = $urlArray;
    }


    /**
     * 排序方式 sort/price-up
     * @return mixed
     */
    public function sort()
    {
        // 排序
        if( !empty($this->param['sort']) ){
            $sort = $this->param['sort'];
            $order_param = explode('_',$sort);  //排序条件
            $urlArray = preg_split('/(sort=){1}([^&]+)(&)?/',request()->url());
            $GLOBALS['sort'] = $sort;
            $GLOBALS['sortUrlArray'] = $urlArray;
            switch ($order_param[0]){
                case 'sale':
                    $sort_key = 'sg.sales_sum';
                    break;
                case 'hot':
                    $sort_key = 'sg.is_hot';
                    break;
                case 'comm':
                    $sort_key = 'sg.is_comm';
                    break;
                case 'new':
                    $sort_key = 'sg.is_new';
                    break;
                case 'price':
                    $sort_key = 'sg.shop_price';
                    break;
                default:
                    $sort_key = '';
                    break;
            }
            if( !empty($sort_key) ){
                if( !empty($order_param[1]) ){
                    $sort_value = $order_param[1] == 'down' ? 'desc' : 'asc';
                }else{
                    $sort_value = 'desc';
                }
                $this->sort = $sort_key.' '.$sort_value;
            }
        }else{
            $order_param = ['',''];
            $this->sort = null;
        }
        return ['sort'=>$order_param[0],'sort_asc'=>$order_param[1]];
    }


    /**
     * 金额筛选
     * @return mixed
     */
    public function price()
    {
        // 排序
        if( !empty($this->param['price']) ){
            $price = $this->param['price'];
            $price_param = explode('_',$price);  //排序条件
            $urlArray = preg_split('/(price=){1}([^&]+)(&)?/',request()->url());
            $GLOBALS['price'] = $price;
            $GLOBALS['priceUrlArray'] = $urlArray;
            if( !empty(intval($price_param[0])) ){
                $price_section = 'sg.shop_price >= '.floor($price_param[0]);
            }else{
                $price_section = 'sg.shop_price > 0';
            }
            if( !empty(intval($price_param[1])) ){
                $price_section .= ' and sg.shop_price <= '.floor($price_param[1]);
            }
        }else{
            return '';
        }
        return $price_section;
    }



    /**
     * 金额筛选
     * @return mixed
     */
    public function brand()
    {
        $brand_section = '';
        // 排序
        if( !empty($this->param['brand']) ){
            $brand = intval($this->param['brand']);
            $urlArray = preg_split('/(brand=){1}(\d+)(&)?/',request()->url());
            $GLOBALS['brand'] = $brand;
            $GLOBALS['brandUrlArray'] = $urlArray;
            if( !empty($brand) ){
                $brand_section = 'sg.brand_id = '.$brand;
                $this->selectedParam[] = ['品牌',getTableValue('shop_brand','id='.$brand,'name'),'brand'];
            }
        }
        return $brand_section;
    }


    /**
     * 获取已经选择的参数
     * @return array
     */
    public function getSelectedParam()
    {
        return $this->selectedParam;
    }

}