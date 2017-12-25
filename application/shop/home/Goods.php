<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块商品控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\home;

use app\shop\api\GoodsList;
use app\shop\api\SearchGoods;
use app\shop\home\HomeBase;
use app\shop\model\Goods as GoodsModel;
use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;

class Goods extends HomeBase
{

    public function index()
    {
        $this->redirect('shop/index/index');
    }


    public function goodsList()
    {
        // 获取当前参数
        $param = request()->param();
        if (empty($param['cate'])) {
            if (empty($param['id'])) {
                $this->redirect('/');
            } else {
                $cate_id = $param['id'];
            }
        } else {
            $cate_id = $param['cate'];
        }
        // 取出当前分类详细信息
        $goodsCate = api('shop', 'Goods', 'getCateInfo', [$cate_id]);
        if (empty($goodsCate)) {
            $this->redirect('/');
        }
        $this->assign('goodsCate', $goodsCate);

        // 商品列表实例
        $GLModel = new GoodsList();

        // 排序
        $sort = ['sort' => '', 'sort_asc' => ''];
        if (!empty($param['sort'])) {
            $sort = $GLModel->sort();
        }
        $this->assign($sort);

        $GLModel->setCateId($cate_id);
        // 筛选参数[]
        $goodsList = $GLModel->goodsList();
        $tmp = ['currentPage' => $goodsList->currentPage(), 'lastPage' => $goodsList->lastPage(), 'totalPage' => $goodsList->total(), 'goodsCount' => $GLModel->getGoodsCount(), 'brandList' => [], 'attrList' => $GLModel->attrList()];
        if( empty(array_key_exists('brand',$param)) ){
            $tmp['brandList'] = $GLModel->brandList();
        }
        // 显示当前分类的层级数据
        $cateLv = explode(',', $goodsCate['parent_id_path']);
        $lv_curr = explode(',', ltrim($goodsCate['parent_id_path'], '0,'));
        unset($cateLv[count($cateLv) - 1]);

        $goods_category = [];
        foreach ($cateLv as $key => $lv) {
            $cate['id'] = $lv_curr[$key];
            $cate['title'] = Db::name('goods_category')->cache(true)->where('id', $lv_curr[$key])->value('name');
            $cate['brothers'] = Db::name('goods_category')->cache(true)->where(['pid' => $lv, 'is_show' => 1])->column('id,name,pid,level', 'id');
            $goods_category[] = $cate;
        }

        $tmp['param'] = $param;
        $tmp['selected_param'] = $GLModel->getSelectedParam();
        $tmp['goods_category'] = $goods_category;
        $tmp['goodsList'] = $goodsList;
        $tmp['page'] = $goodsList->render();
        $this->assign($tmp);

        // 取出分类的模板
        $tpl = $this->getCateTpl($cate_id, 'list_template');
        return $this->fetch($tpl);
    }


    public function search()
    {
        // 获取当前参数
        $param = request()->param();
        $keyword = '';
        if (empty($param['keyword'])) {
            $this->redirect('/');
        } else {
            $keyword = $param['keyword'];
        }
        $this->assign('keyword',$keyword);

        // 商品列表实例
        $GLModel = new SearchGoods();

        // 排序
        $sort = ['sort' => '', 'sort_asc' => ''];
        if (!empty($param['sort'])) {
            $sort = $GLModel->sort();
        }
        $this->assign($sort);

        $GLModel->setKeyword($keyword);
        // 筛选参数[]
        $goodsList = $GLModel->goodsList();
        $tmp = ['currentPage' => $goodsList->currentPage(), 'lastPage' => $goodsList->lastPage(), 'totalPage' => $goodsList->total(), 'goodsCount' => $GLModel->getGoodsCount(), 'brandList' => [], 'attrList' => $GLModel->attrList()];
        if( empty(array_key_exists('brand',$param)) ){
            $tmp['brandList'] = $GLModel->brandList();
        }
        $tmp['param'] = $param;
        $tmp['selected_param'] = $GLModel->getSelectedParam();
        $tmp['goodsList'] = $goodsList;
        $tmp['page'] = $goodsList->render();
        $this->assign($tmp);

        // 取出分类的模板
        return $this->fetch('');
    }


    /**
     * 商品详情
     * @param $id
     * @param string $s
     * @return mixed
     */
    public function goodsInfo($id,$s = '')
    {
        if (empty($id)) {
            $this->error(lang('param_is_failed'));
        }
        $goods_id = $id;

        $goodsInfo = api('shop','Goods','goodsInfo',[$goods_id]);
        if ($goodsInfo['status'] != 1 || $goodsInfo['trash'] == 1) {
            $this->error('当前商品未上架或不存在。');
        }

        // 将商品的浏览记录存入本地cookie
        $history = cookie('goods_history');
        $now = time();
        // cookie记录中没有当前商品，则加入到浏览记录中
        if (!empty($history)) {
            foreach ($history as $key => $his) {
                if ($his == $goods_id) {
                    unset($history[$key]);
                }
            }
        }
        $history[$now] = $goods_id;
        cookie('goods_history', $history);

        // 增加商品的点击量
        Db::name('shop_goods')->where('id', $goods_id)->setInc('view');

        $goodsInfo['comment_count'] = Db::name('shop_goods_comment')->where('goods_id', $goods_id)->count('id');
        $goodsInfo['brand_name'] = getTableValue('shop_brand', ['id' => $goodsInfo['brand_id']], 'name');

        $this->assign('goods', $goodsInfo);

        // 取商品所有的规格数据
        $spec_list = Db::name('shop_spec_price')->where('goods_id', $goods_id)->order(['price' => 'ASC'])->column('key_sign');

        // dump($spec_list);
        $this->assign('goods_specs', $spec_list);
        $filter_spec = [];
        if ($spec_list) {
            // 如果参数中没有规格,则将最低价格的规格作为当前规格
            if (empty($s)) {
                $spec_curr = $spec_list[0];
                $s = $spec_list[0];
            } else {
                $spec_curr = $s;
            }
            $lowPriceKey = explode('_', $spec_curr);
            $this->assign('lowPriceKey', $lowPriceKey);
            foreach ($lowPriceKey as $k => $v) {
                $spec_id = getTableValue('shop_spec_item', ['id' => $v], 'spec_id');
                $curr[$spec_id] = $v;
            }

            $spec_str = implode('_', $spec_list);
            $spec_arr = explode('_', $spec_str);
            $spec_arr = array_unique($spec_arr);
            sort($spec_arr);
            $spec_id = Db::name('shop_spec_item')->where('id', 'IN', $spec_arr)->column('spec_id');
            $spec_id = array_unique($spec_id);
            $second_spec = array_slice($spec_id, 1, 1);
            $this->assign('second_spec', end($second_spec));
            foreach ($spec_id as $key => $spec) {
                $filter_spec[$spec] = Db::name('shop_spec')->find($spec);
                $item = Db::name('shop_spec_item')->where('spec_id', $spec)->where('id', 'IN', $spec_arr)->cache(true)->select();
                foreach ($item as $k => $v){
                    $spec_ed = $curr;
                    $spec_ed[$spec] = $v['id'];
                    $key = implode('_', $spec_ed);
                    $url['id'] = $goods_id;
                    $url['s'] = $key;
                    $item[$k]['spec_key'] = $key;
                    $item[$k]['href'] = url('shop/Goods/goodsInfo', $url);
                    $item_where = ['goods_id' => $goods_id, 'spec_id' => $v['spec_id'], 'item_id' => $v['id']];
                    $item[$k]['icon'] = getTableValue('shop_spec_icon', $item_where, 'icon');
                }
                $filter_spec[$spec]['item'] = $item;
            }
        }
        // dump($filter_spec);
        $this->assign('filter_spec', []);

        // 检测商品当前是否在活动之中
        $is_promo = api('shop','Compute','promGoodsPrice',[$goods_id,$s,true]);
        $promo_goods = Db::name('shop_promotion_goods')->where(['goods_id' => $goods_id, 'prom_id' => $is_promo['prom_id']])->find();

        if ($promo_goods) {
            // 取出商品促销的规格
            $promo_spec_price = unserialize($promo_goods['goods_spec']);
            if ($promo_spec_price) {
                $promo_specs = array_keys($promo_spec_price);
                // 如果当前访问的规格在促销规格之中，则转到商品活动页面
                if (in_array($s, $promo_specs)) {
                    $this->redirect('shop/goods/activity', ['id' => $id,'s'=>$s]);
                }
            }
        }

        $goods_spec_str = '';
        if (!empty($spec_curr)) {
            $goods_spec_str = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $spec_curr])->value('key_name');
        }
        $this->assign('spec_str', $goods_spec_str);

        // 取商品属性数据
        $spec_attr = '0';
        if (isset($s)) {
            $spec_attr = $s;
        }

        // 输出当前商品的属性值
        $attr_list = Db::name('shop_goods_attr')->where(['goods_id' => $goods_id, 'spec_key' => $spec_attr])->column('attr_value', 'attr_id');
        if (empty($attr_list)) {
            $attr_list = Db::name('shop_goods_attr')->where(['goods_id' => $goods_id, 'spec_key' => '0'])->column('attr_value', 'attr_id');
        }
        $this->assign('attr_list', $attr_list);

        if (isset($s)) {
            // 取对应规格的商品详情
            $spec_goods = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $s])->field('price,store_count')->find();
            $goods_price = empty($spec_goods) ? $goodsInfo['shop_price'] : $spec_goods['price'];
            $goods_stock = empty($spec_goods) ? $goodsInfo['stock'] : $spec_goods['store_count'];
        } else {
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }
        // dump($goods_stock);
        $this->assign('goods_content', api('shop','Goods','getContent',[$goodsInfo['id'],$s]));
        $this->assign('goods_price', $goods_price);
        $this->assign('goods_stock', $goods_stock);

        // 取出商品所属分类下定义的商品详情页的模板
        $tpl = $this->getCateTpl($goodsInfo['cat_id'], 'detail_template');
        return $this->fetch($tpl);
    }


    public function activity($id,$s = '')
    {
        if (empty($id)) {
            $this->error(lang('param_is_failed'));
        }
        $goods_id = $id;

        // 获取商品详情数据
        $goodsInfo = api('shop','Goods','goodsInfo',[$goods_id]);
        if ($goodsInfo['status'] != 1 || $goodsInfo['trash'] == 1) {
            $this->error('当前商品未上架或不存在。');
        }
        // 增加商品的点击量
        Db::name('shop_goods')->where('id', $goods_id)->setInc('view');

        $goodsInfo['comment_count'] = Db::name('shop_goods_comment')->where('goods_id', $goods_id)->count('id');
        $goodsInfo['brand_name'] = getTableValue('shop_brand', ['id' => $goodsInfo['brand_id']], 'name');
        $goodsInfo['content'] = api('shop','Goods','getContent',[$goods_id]);
        $this->assign('goods', $goodsInfo);

        // 取商品所有的规格数据
        $spec_list = Db::name('shop_spec_price')->where('goods_id', $goods_id)->order(['price' => 'ASC'])->column('key_sign');
        $this->assign('goods_specs', $spec_list);
        $filter_spec = [];
        if ($spec_list) {
            // 如果参数中没有规格,则将最低价格的规格作为当前规格
            if (empty($s)) {
                $spec_curr = $spec_list[0];
                $s = $spec_list[0];
            } else {
                $spec_curr = $s;
            }
            $lowPriceKey = explode('_', $spec_curr);
            $this->assign('lowPriceKey', $lowPriceKey);
            foreach ($lowPriceKey as $k => $v) {
                $spec_id = getTableValue('shop_spec_item', ['id' => $v], 'spec_id');
                $curr[$spec_id] = $v;
            }
            // dump($curr);

            $spec_str = implode('_', $spec_list);
            $spec_arr = explode('_', $spec_str);
            $spec_arr = array_unique($spec_arr);
            sort($spec_arr);
            $spec_id = Db::name('shop_spec_item')->where('id', 'IN', $spec_arr)->column('spec_id');
            $spec_id = array_unique($spec_id);
            $second_spec = array_slice($spec_id, 1, 1);
            $this->assign('second_spec', end($second_spec));
            foreach ($spec_id as $key => $spec) {
                $filter_spec[$spec] = Db::name('shop_spec')->find($spec);
                $item = Db::name('shop_spec_item')->where('spec_id', $spec)->where('id', 'IN', $spec_arr)->cache(true)->select();
                foreach ($item as $k => $v) {
                    $spec_ed = $curr;
                    $spec_ed[$spec] = $v['id'];
                    $key = implode('_', $spec_ed);
                    $url['id'] = $goods_id;
                    $url['s'] = $key;
                    $item[$k]['spec_key'] = $key;
                    $item[$k]['href'] = url('shop/Goods/goodsInfo', $url);
                    $item_where = ['goods_id' => $goods_id, 'spec_id' => $v['spec_id'], 'item_id' => $v['id']];
                    $item[$k]['icon'] = getTableValue('shop_spec_icon', $item_where, 'icon');
                }
                $filter_spec[$spec]['item'] = $item;
            }
        }
        $this->assign('filter_spec', $filter_spec);

        // 检测商品当前是否在活动之中
        $is_promo = api('shop','Compute','promGoodsPrice',[$goods_id,'',true]);
        $promo_goods = Db::name('shop_promotion_goods')->where(['goods_id' => $goods_id, 'prom_id' => $is_promo['prom_id']])->find();

        if ($promo_goods) {
            // 取出商品促销的规格
            $promo_spec_price = unserialize($promo_goods['goods_spec']);
            if (!empty($promo_spec_price)) {
                $promo_specs = array_keys($promo_spec_price);
                if (!in_array($s, $promo_specs)) {
                    $this->redirect('shop/goods/goodsInfo', ['id' => $id,'s'=>$s]);
                }
            } else {
                $promo_goods_price = compute()->promGoodsPrice($goods_id);
            }
        } else {
            $this->redirect('shop/goods/goodsInfo', ['id' => $id]);
        }

        // 活动结束时间
        $end_time = $is_promo['end_time'];
        $this->assign('end_time', $end_time);

        $goods_spec_str = '';
        if (!empty($spec_curr)) {
            $goods_spec_str = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $spec_curr])->value('key_name');
        }
        $this->assign('spec_str', $goods_spec_str);

        // 取商品属性数据
        $spec_attr = '0';
        if (isset($s)) {
            $spec_attr = $s;
        }
        // 输出当前商品的属性值
        $attr_list = Db::name('shop_goods_attr')->where(['goods_id' => $goods_id, 'spec_key' => $spec_attr])->column('attr_value', 'attr_id');
        if (empty($attr_list)) {
            $attr_list = Db::name('shop_goods_attr')->where(['goods_id' => $goods_id, 'spec_key' => '0'])->column('attr_value', 'attr_id');
        }
        $this->assign('attr_list', $attr_list);
        // 输出当前商品的所有属性项目

        // 输出当前商品的所有属性项目
        $attribute = api('shop', 'Goods', 'getAttrList', [$goodsInfo['cat_id']]);
        $this->assign('attribute', $attribute);

        // 如果商品带有规格数据,则取对应规格的相册,如相册为空则取商品默认相册
        if (isset($s)) {
            // 取对应规格的商品相册
            $goods_images_list = Db::name('shop_spec_image')->where(['goods_id' => $goods_id, 'spec_key' => $s])->select();
            if (empty($goods_images_list)) {
                // 对应规格相册不存在,则取商品默认相册
                $goods_images_list = Db::name('shop_goods_images')->where('goods_id', $goods_id)->select();
            }

            // 取对应规格的商品详情
            $goods_content = api('shop','Goods','getContent',[$goods_id]);
            $spec_goods = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $s])->field('price,store_count')->find();

            $goods_content = empty($goods_content) ? api('shop','Goods','getContent',[$goods_id]) : $goods_content;
            $goods_price = empty($spec_goods) ? $goodsInfo['shop_price'] : $spec_goods['price'];
            $goods_stock = empty($spec_goods) ? $goodsInfo['stock'] : $spec_goods['store_count'];
        } else {
            // 商品没有规格参数,取商品的默认相册数据,默认的商品详情
            $goods_images_list = Db::name('shop_goods_images')->where('goods_id', $goods_id)->select();
            $goods_content = api('shop','Goods','getContent',[$goods_id]);
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }
        $this->assign('goods_images_list', $goods_images_list);
        $this->assign('goods_content', $goods_content);

        if (!empty($promo_spec_price)) {
            $goods_price = $promo_spec_price[$spec_curr];
        } else {
            if (isset($promo_goods_price)) {
                $goods_price = $promo_goods_price;
            }
        }
        // dump($goods_stock);
        $this->assign('goods_price', $goods_price);
        $this->assign('goods_stock', $goods_stock);

        // 取出当前商品的分类详细信息
        $goodsCate = Db::name('goods_category')->find($goodsInfo['cat_id']);
        // 显示当前分类的层级数据
        $cateLv = explode(',', $goodsCate['parent_id_path']);
        $lv_curr = explode(',', ltrim($goodsCate['parent_id_path'], '0,'));
        unset($cateLv[count($cateLv) - 1]);
        foreach ($cateLv as $key => $lv) {
            $cate['id'] = $lv_curr[$key];
            $cate['title'] = Db::name('goods_category')->cache(true)->where('id', $lv_curr[$key])->value('name');
            $cate['brothers'] = Db::name('goods_category')->cache(true)->where(['pid' => $lv, 'is_show' => 1])->column('id,name,pid,level', 'id');
            $goods_category[] = $cate;
        }
        $this->assign('goods_category', $goods_category);

        // 取出商品所属分类下定义的商品详情页的模板
        $tpl = $this->getCateTpl($goodsInfo['cat_id'], 'detail_template');
        return $this->fetch($tpl);
    }


    public function ajaxComment()
    {
        $goods_id = input("goods_id", '0');
        $commentType = input('type', '1'); // 1 全部 2好评 3 中评 4差评
        if ($commentType == 5) {
            $where = "is_show = 1 and  goods_id = $goods_id and parent_id = 0 and img !='' ";
        } else {
            $typeArr = array('1' => '0,1,2,3,4,5', '2' => '4,5', '3' => '3', '4' => '0,1,2');
            $where = "is_show = 1 and  goods_id = $goods_id and parent_id = 0 and ceil((deliver_rank + goods_rank + service_rank) / 3) in($typeArr[$commentType])";
        }
        $count = Db::name('shop_goods_comment')->where($where)->count();

        $list = Db::name('shop_goods_comment')->where($where)->order("add_time desc")->paginate(5);
        $show = $list->render();
        $replyList = Db::name('shop_goods_comment')->where("is_show = 1 and  goods_id = $goods_id and parent_id > 0")->order("add_time desc")->select();

        $list = $list->items();
        foreach ($list as $k => $v) {
            foreach ($list as $k => $v) {
                if( !empty($v['img']) ){
                    $v['img'] = explode(',',$v['img']);
                    $list[$k]['img'] = $v['img'][0]; // 晒单图片
                }
            }
        }
        $this->assign('count',$count);
        $this->assign('commentlist', $list);// 商品评论
        $this->assign('replyList', $replyList); // 管理员回复
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }


    // ajax获取咨询列表
    public function ajaxConsult($goods_id = 0)
    {
        $list = Db::name('shop_goods_consult')->where(['goods_id' => $goods_id, 'is_show' => 1, 'parent_id' => 0])->order("add_time desc")->paginate(5);
        $consultlist = $list->all();
        foreach ($consultlist as $key => $ask) {
            $consultlist[$key]['answer'] = Db::name('shop_goods_consult')->where(['parent_id' => $ask['id'], 'is_addones' => 0])->order("add_time asc")->limit(5)->select();
        }
        $page = $list->render();
        $this->assign('list', $consultlist);
        $this->assign('page', $page);
        return $this->fetch();
    }


    /**
     * 加入收藏
     */
    public function goods_collect()
    {
        $goods_id = intval(request()->param('goods_id'));
        if ($goods_id == 0) {
            $this->error(lang('goods_is_failed'));
        }
        if( empty(session('user.id')) ){
            return $this->error('请先登录');
        }
        $saveData['user_id'] = session('user.id');
        $saveData['goods_id'] = $goods_id;
        $saveData['status'] = 1;
        $saveData['add_time'] = date('Y-m-d H:i:s', NOW_TIME);
        //检测用户是否收藏
        $is_c = Db::name('shop_goods_collect')->where(['user_id' => session('user.id'), 'goods_id' => $goods_id, 'status' => 1])->value('id');
        if ($is_c > 0) {
            $this->error(lang('goods_is_collected'));
        }
        try {
            Db::name('shop_goods_collect')->insert($saveData);
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
        $this->success(lang('goods_collect_success'));
    }


    /**
     * 取出商品分类的模板，如当前分类没有定义，则取父级分类的，如整个分类结构都没有定义模板，则返回''
     * @param int $cat_id 分类ID
     * @param string $field 字段名，欲取得模板的种类，有首页模板、列表页模板、详情页模板
     * @return mixed
     */
    private function getCateTpl($cat_id, $field)
    {
        $cate_tree = Db::name('goods_category')->where('id', $cat_id)->value('parent_id_path');
        if ($cate_tree) {
            $cate_tree = ltrim($cate_tree, '0,');
            $cate_list = Db::name('goods_category')->field('index_template,list_template,detail_template')->where('id', 'IN', $cate_tree)->select();
            foreach ($cate_list as $key => $cate) {
                if (empty($cate[$field])) {
                    unset($cate_list[$key]);
                }
            }
            if (!empty($cate_list)) {
                $cate = end($cate_list);
                $tpl = 'skin/' . $cate[$field];
            } else {
                $tpl = '';
            }
            return $tpl;
        } else {
            return '';
        }
    }


}