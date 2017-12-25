<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/9/18
 * Time: 上午9:27
 */

namespace app\shop\api;

use think\Db;

class GoodsList
{
    /**
     * 已选参数
     * @var array
     */
    protected $selectedParam;

    /**
     * 排序方式
     * @var array
     */
    protected $sort;


    /**
     * 商品分类id
     * @var int
     */
    protected $cate_id;


    /**
     * 用户筛选参数
     * @var array
     */
    protected $param;


    /**
     * 设置分页条数
     * @var int
     */
    protected $pageRows = 20;


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

        $order = 'sg.view+sg.sales_sum';
        $priceSection = $this->price();

        $brandCondition = $this->brand();
        $goods_list = Db::name('shop_goods')
            // 两表关联
            ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id','LEFT')
            // 查询条件
            ->where('sg.cat_id',$this->cate_id) ->where($brandCondition) ->where($priceSection) ->where(['sg.status'=>1,'sg.trash'=>0]) ->where($where)
            // 查询字段,分组
            ->field('sg.id,sg.is_new,sg.is_comm,sg.thumb,sg.market_price,sg.shop_price,sg.name,sg.title,sg.url,sg.sales_sum') ->group('sg.`id`')
            // 查询排序
            ->order($this->sort) ->order('sg.sort') ->order($order)
            ->paginate($this->pageRows);
        if( empty($goods_list->items()) || count($goods_list->items()) < $this->pageRows ){
            // 获取当前分类的下级分类
            $cateChildList = Db::name('goods_category') ->where('parent_id_path','like','%,'.$this->cate_id.',%')->column('id');
            if( empty($cateChildList) ){
                return $goods_list;
            }
            array_push($cateChildList,$this->cate_id);
            // 获取分类下的子栏目
            $goods_list = Db::name('shop_goods')
                ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id','LEFT')
                ->where('sg.cat_id','in',$cateChildList) ->where($brandCondition) ->where($priceSection) ->where($where) ->where(['sg.status'=>1,'sg.trash'=>0])
                ->order($this->sort)->field('sg.id,sg.is_new,sg.is_comm,sg.thumb,sg.market_price,sg.shop_price,sg.name,sg.title,sg.url,sg.id,sg.sales_sum') ->group('sg.`id`')
                ->order($this->sort) ->order('sg.sort') ->order($order)
                ->paginate($this->pageRows);
            // 计算商品总数
            $this->goodsCount = Db::name('shop_goods') ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id','LEFT') ->where('sg.cat_id','in',$cateChildList) ->where($brandCondition) ->where($priceSection) ->where(['sg.status'=>1,'sg.trash'=>0])  ->where($where)->field('sg.id') ->group('sg.`id`') ->order('sg.sort') ->order($order)  ->count();
        }else{
            $this->goodsCount = Db::name('shop_goods') ->alias('sg') ->join('__SHOP_GOODS_ATTR__ sga','sg.id = sga.goods_id','LEFT')->where('sg.cat_id',$this->cate_id) ->where($brandCondition) ->where($priceSection) ->where(['sg.status'=>1,'sg.trash'=>0]) ->where($where) ->field('sg.`id`') ->group('sg.`id`') ->order($this->sort) ->order('sg.sort') ->order($order) ->count();
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
        if( empty(array_key_exists('brand',$this->param)) ){
            $brandList = api('shop','Goods','getBrandList',[$this->cate_id]);
        }
        return $brandList;
    }

    /**
     * 获取当前分类的属性
     * @return mixed
     */
    public function attrList()
    {
        $attrList = api('shop','Goods','getAttrList',[$this->cate_id]);
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
     * 设置商品分类id
     * @param $cate_id
     * @return $this
     */
    public function setCateId($cate_id)
    {
        $this->cate_id = $cate_id;
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

            $param['screen'] = str_replace(' ','{space}',$param['screen']);
            $param['screen'] = urldecode($param['screen']);
            $param['screen'] = str_replace(['%7Bspace%7D','{space}'],' ',$param['screen']);
            // 如果存在模板后缀,去除
            if( strpos($param['screen'],'.'.config('url_html_suffix')) ){
                $param['screen'] = mb_substr($param['screen'],0,mb_strlen($param['screen'])-5);
            }
            $screenParamArray = !empty($param['screen']) ? explode('%_',$param['screen']) : '';
            for ($i=0;$i<count($screenParamArray);$i++){
                $screen = explode('_',$screenParamArray[$i]);
                $screenKey = intval($screen[0]);
                unset($screen[0]);
                // 查找是否存在
                $screen = implode('_',$screen);
                $isExist = Db::name('shop_attribute')->where(['id'=>$screenKey])->where('FIND_IN_SET("'.$screen.'",attr_values)')->value('id');
                if( empty($isExist) || empty($screen) ){
                    continue;
                }
                $screenParam[$screenKey] = $screen;
                $this->selectedParam[] = [getTableValue('shop_attribute','id='.$screenKey,'attr_name'),$screen,'screen',$screenKey];
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