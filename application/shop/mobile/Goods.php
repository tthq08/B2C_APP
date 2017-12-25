<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 商城模块商品控制器	
// +----------------------------------------------------------------------
// | 版权所有 2013~2017
// +----------------------------------------------------------------------
// | 作者: 余剑华 <528526198@qq.com>
// +----------------------------------------------------------------------

namespace app\shop\mobile;

use app\mshop\home\HomeBase;
use think\Db;
use think\Request;
use app\shop\api\GoodsList;

class Goods extends HomeBase
{
	public function goodsList($id)
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

    public function search($key)
    {
        
        if(empty($key)){
            $this->error('没有搜索条件');
        }
        $param = explode('--', $key);
        $search_key = $param[0];
        $param[1] = isset($param[1])?$param[1]:'';
        $param[2] = isset($param[2])?$param[2]:'';
        $param[3] = isset($param[3])?$param[3]:'';
        $param[4] = isset($param[4])?$param[4]:'';
        $this->assign('search_key',$search_key);
        $filter_param['key'] = $search_key;

        $filter_param['brand_id'] = $param[1];
        $filter_param['attr'] = $param[2];
        $filter_param['price'] = $param[3];
        $sort = ['sort'=>'','sort_asc'=>''];
        if (!empty($param[4])) {
            $order_param = explode('-', $param[4]);  //排序条件
            $sort['sort'] = $order_param[0];
            $sort['sort_asc'] = $order_param[1];
        }
        // dump($sort);

        $this->assign($sort);
        $filter_goods = Db::name('shop_goods') ->field('GROUP_CONCAT(id) as id,GROUP_CONCAT(cat_id) as cate_id,count(id) as nums') ->where('title','LIKE',"%{$search_key}%") ->where(['status'=>1,'trash'=>0])->find();
        $filter_goods_id = $filter_goods['id'];
        if (empty($filter_goods)) {
            $this->assign('cate_ids',0);
        } else {
            $this->assign('cate_ids',$filter_goods['cate_id']);
        }
        $this->assign('goods_nums',$filter_goods['nums']);
        // dump($filter_goods_id);

        // 取出当前分类详细信息
        // $goodsCate = Db::name('goods_category') ->find($cate_id);
        // $this->assign('goodsCate',$goodsCate);

        // 显示当前分类的层级数据
        // $cateLv = explode(',', $goodsCate['parent_id_path']);
        // $lv_curr = explode(',', ltrim($goodsCate['parent_id_path'],'0,'));
        // unset($cateLv[count($cateLv)-1]);
        // foreach ($cateLv as $key => $lv) {
        //     $cate['id'] = $lv_curr[$key];
        //     $cate['title'] = Db::name('goods_category') ->cache(true) ->where('id',$lv_curr[$key]) ->value('name');
        //     $cate['brothers'] = Db::name('goods_category') ->cache(true) ->where(['pid'=>$lv,'is_show'=>1]) ->column('id,name,pid,level','id'); 
        //      $goods_category[] = $cate;         
        // }
        // $this->assign('goods_category',$goods_category);

        // 筛选商品列表
        // 品牌筛选
        if(!empty($filter_param['brand_id'])){
            $brand_id = $filter_param['brand_id'];
            $filter_goods_id = model('goods') -> getGoodsIDByBrand($brand_id,$filter_goods_id);
        }

        // 价格筛选
        if(!empty($filter_param['price'])){
            $price = $filter_param['price'];
            $filter_goods_id = model('goods') -> getGoodsIDByPrice($price,$filter_goods_id);
        }

        // 属性筛选
        if(!empty($filter_param['attr'])){
            $attr = $filter_param['attr'];
            $filter_goods_id = model('goods') -> getGoodsIDByAttr($attr,$filter_goods_id);
        }


        // 输出已选中条件
        $filter_menu = model('goods') -> getFilterMenu($filter_param,'shop/Goods/goodsList');
        $this->assign('filter_menu',$filter_menu);


        $filter_price = model('goods') -> getFilterPrice($filter_goods_id,$filter_param,'shop/Goods/goodsList');
        $this->assign('filter_price',$filter_price);

        // 过滤已选中属性
        $filter_attr = model('goods') -> getFilterAttr($filter_goods_id,$filter_param,'shop/Goods/goodsList');
        $this->assign('filter_attr',$filter_attr);

        // 输出品牌列表
        $filter_brand = model('goods') -> getFilterBrand($filter_goods_id,$filter_param,'shop/Goods/goodsList');
        $this->assign('filter_brand',$filter_brand);

        // $filter_param = [];
        $this->assign('filter_param',$filter_param);

        if (isset($order_param)) {
            $order = [$order_param[0]=>$order_param[1],'id'=>'DESC'];
        }else{
            $order = ['sales_sum'=>'DESC','id'=>'DESC'];
        }

        // dump($filter_goods_id);
        $goods_list = Db::name('shop_goods') ->where('id','IN',$filter_goods_id) ->where(['status'=>1,'trash'=>0]) ->order($order)  ->paginate(20);

        $this->assign('goodsList',$goods_list);
        // $page = $goods_list->render();
        // $this->assign('page', $page);
        return $this->fetch('goodslist');
    }

	public function goodsInfo($id,$s='')
	{
        if (empty($id)) {
            $this->error(lang('param_is_failed'));
        }
        $goods_id = $id;
        $this->assign('pannel',' ');

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
        $this->assign('filter_spec', $filter_spec);

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
        // 输出当前商品的所有属性项目
        $attribute = api('shop', 'Goods', 'getAttrList', [$goodsInfo['cat_id']]);
        $this->assign('attribute', $attribute);

        // 如果商品带有规格数据,则取对应规格的相册,如相册为空则取商品默认相册
        if (isset($s)) {
            // 取对应规格的商品相册
            $goods_images_list = Db::name('shop_spec_image')->where(['goods_id' => $goods_id, 'spec_key' => $s])->order('image_sort asc')->select();
            if (empty($goods_images_list)){
                // 对应规格相册不存在,则取商品默认相册
                $goods_images_list = Db::name('shop_goods_images')->where('goods_id', $goods_id)->order('image_sort asc')->select();
            }

            // 取对应规格的商品详情
            $spec_goods = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $s])->field('price,store_count')->find();
            $goods_content = api('shop','Goods','getContent',[$goodsInfo['id'],$s]);
            $goods_price = empty($spec_goods) ? $goodsInfo['shop_price'] : $spec_goods['price'];
            $goods_stock = empty($spec_goods) ? $goodsInfo['stock'] : $spec_goods['store_count'];
        } else {
            // 商品没有规格参数,取商品的默认相册数据,默认的商品详情
            $goods_images_list = Db::name('shop_goods_images')->where('goods_id', $goods_id)->order('image_sort asc')->select();
            $goods_content = api('shop','Goods','getContent',[$goodsInfo['id']]);
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }

        // dump($goods_stock);
        $this->assign('goods_images_list', $goods_images_list);
        $this->assign('goods_content', $goods_content);
        $this->assign('goods_price', $goods_price);
        $this->assign('goods_stock', $goods_stock);

        // 取出商品所属分类下定义的商品详情页的模板
        $tpl = $this->getCateTpl($goodsInfo['cat_id'], 'detail_template');
        return $this->fetch($tpl);
	}

    public function activity($id,$pannel='')
    {
        if (empty($id)) {
            $this->error(lang('param_is_failed'));
        }
        $goods_id = $id;
        $this->assign('pannel',' ');

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
        if (isset($s)){
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
            // 取对应规格的商品详情
            $spec_goods = Db::name('shop_spec_price')->where(['goods_id' => $goods_id, 'key_sign' => $s])->field('price,store_count')->find();

            $goods_price = empty($spec_goods) ? $goodsInfo['shop_price'] : $spec_goods['price'];
            $goods_stock = empty($spec_goods) ? $goodsInfo['stock'] : $spec_goods['store_count'];
        } else {
            // 商品没有规格参数,取商品的默认相册数据,默认的商品详情
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }

        $this->assign('goods_content', api('shop','Goods','getContent',[$goods_id,request()->param('s')]));

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

    public function ajax_ask()
    {
        $data = input('post.');
        $goodsInfo = Db::name('shop_goods') ->field('id,shop_id,cid') ->find($data['goods_id']);
        $cookie_key = 'counsult_'.$data['goods_id'].'_'.session('user.id');
        if (!empty(cookie($cookie_key))) {
            $this->error('同一商品，24小时内只能发布一次提问');
        }
        $is_hidename = empty($data['hidename'])?0:$data['hidename'];
        $consultData = [
            'goods_id' => $data['goods_id'],
            'cid' => $goodsInfo['cid'],
            'shop_id' => $goodsInfo['shop_id'],
            'user_id' => session('user.id'),
            'username' => session('user.nickname'),
            'add_time' => date('Y-m-d H:i:s'),
            'content' => $data['content'],
            'is_show' => tb_config('consult_auto_show',1),
            'is_hidename' => $is_hidename
        ];
        $res = Db::name('shop_goods_consult') ->insert($consultData);
        if ($res !== false) {
            cookie($cookie_key,md5($cookie_key),24*3600);
            $this->success('提问提交成功,耐心等待平台或商家回复哦...');
        } else {
            $this->error('提问提交失败，请重试');
        }
    }


    // ajax获取咨询列表
    public function ajaxConsult($goods_id=0)
    {
        $list = Db::name('shop_goods_consult')->where(['goods_id'=>$goods_id,'is_show'=>1,'parent_id'=>0]) ->order("add_time desc")->paginate(5);
        $consultlist = $list->all();
        foreach ($consultlist as $key => $ask) {
            $consultlist[$key]['answer'] = Db::name('shop_goods_consult') ->where(['parent_id'=>$ask['id'],'is_addones'=>0]) ->order("add_time asc") ->limit(5) ->select();
        }
        $page = $list ->render();
        $this->assign('list',$consultlist);
        $this->assign('page',$page);
        return $this->fetch();
    }


	 // ajax获取评价列表
    public function ajaxComment()
    {
        $goods_id = input("goods_id",'0');
        $commentType = input('type','1'); // 1 全部 2好评 3 中评 4差评
        if($commentType==5){
            $where = "is_show = 1 and  goods_id = $goods_id and parent_id = 0 and img !='' ";
        }else{
            $typeArr = array('1'=>'0,1,2,3,4,5','2'=>'4,5','3'=>'3','4'=>'0,1,2');
            $where = "is_show = 1 and  goods_id = $goods_id and parent_id = 0 and ceil((deliver_rank + goods_rank + service_rank) / 3) in($typeArr[$commentType])";
        }              
              
        $list = Db::name('shop_goods_comment')->where($where) ->where('add_time','<= time',time()-60) ->order("add_time desc")->paginate(5);
        $commentlist = $list->all();
        foreach($commentlist as $k => $v){
            $commentlist[$k]['reply'] = Db::name('shop_goods_comment') ->where('parent_id',$v['id']) ->order(['add_time'=>'DESC']) ->select();
            if (!empty($commentlist['img'])) {
                $commentlist[$k]['img'] = explode(',',$v['img']); // 晒单图片            
            } else {
                $commentlist[$k]['img'] = [];
            }
        }
        
        $this->assign('commentlist',$list);// 商品评论
        $show = $list->render();
        $this->assign('page',$show);// 赋值分页输出        
        return $this->fetch();
    }

	/**
     * 加入收藏
     */
	public function goods_collect(){
	    $goods_id = intval(request()->param('goods_id'));
	    if( $goods_id == 0){
	        $this->error('商品错误');
        }
        $saveData['user_id'] = session('user.id');
	    $saveData['goods_id'] = $goods_id;
	    $saveData['status'] =1;
	    $saveData['add_time'] = date('Y-m-d H:i:s',time());
	    //检测用户是否收藏
        $is_c = Db::name('shop_goods_collect')->where(['user_id'=>session('user.id'),'goods_id'=>$goods_id,'status'=>1])->value('id');
        if( $is_c >0  ){
            $this->error('该商品已收藏');
        }
	    try{
            Db::name('shop_goods_collect')->insert($saveData);
        }catch (\Exception $exception){
	        $this->error($exception->getMessage());
        }
        $this->success('收藏成功,请到个人中心查看');
    }


    // 回复评论
    public function reply_comment($id=''){
        if( request()->isPost() ){
            $postData = request()->post();
            // dump($postData);die;
            $saveData['topic_id'] = $postData['note_id'];           // 笔记id
            $saveData['replay_user'] = $postData['user_name'];      // 评论人姓名
            $saveData['replay_user_id'] = $postData['user_id'];     // 评论人id
            $saveData['replay_content'] = $postData['comment'];     // 评论内容
            $saveData['replay_time'] = date('Y-m-d H:i',time());                 // 评论时间
            $saveData['replay_parent_id'] = $postData['replay_parent_id'];                    // 上级评论id 主键  
            $saveData['zan'] = '0';                                 // 评论点赞数
            // dump($saveData);
            $is_ok = Db::name('web_note_comment')->insert($saveData);
            if($is_ok){
                $this->success('回复成功','/vinfo/'.$postData['note_id']); 
                // $this->redirect('shop/goods/vinfo', ['id' => $postData['note_id']]);
            }else{
                $this->error('回复失败'); 
            }
        }
        $this->assign('comment_id',$id);
        return $this->fetch();
        
    }

    /**
     * 评论详情
     */
    public function comment_msg($id){
        // dump($id);
        $all_comment = Db::name('web_note_comment')->where(['replay_parent_id'=>$id])->order(['replay_time'=>'DESC'])->select();
        $this->assign('all_comment',$all_comment);

        $m_comment = Db::name('web_note_comment')->where(['id'=>$id])->select();
        $this->assign('m_comment',$m_comment[0]);

        return $this->fetch();
    }

    /**
     * 所有评论列表
     */
    public function comment_list($id){
        // 输出评论】
        $note_comment = Db::name('web_note_comment')->where(['topic_id'=>$id,'replay_parent_id'=>0])->order(['replay_time'=>'DESC'])->select();
        foreach ($note_comment as $key => $comment) {

            $reply = Db::name('web_note_comment')->where(['topic_id'=>$id,'replay_parent_id'=>$comment['id']])->order(['replay_time'=>'DESC'])->select();
            $note_comment[$key]['reply'] = $reply;
            $note_comment[$key]['reply_nums'] = count($reply);

        }
        // dump($note_comment);
        $this->assign('note_comment',$note_comment);
        return $this->fetch();
    }


    /**
     *
     * 评论点赞
     */
    public function save_pzan(){
        if( request()->isPost() ){
            $postData = request()->post();
            $is_exist = Db::name('web_note_comment')->where(['id'=>$postData['id'],'status'=>1])->select();
            // dump($is_exist);
            if(!empty($is_exist[0]['zid'])){
                // $this->error('you');
                $zid = explode(',',$is_exist[0]['zid']);
                if(in_array(session('user.id'),$zid)){
                    $this->error('已经赞过了');
                }else{
                    $ff=implode(',',$zid);
                    $ff.=session('user.id').',';
                    // dump($ff);

                    Db::name('web_note_comment')->where(['id'=>$postData['id']])->setInc('zan');
                    Db::name('web_note_comment')->where(['id'=>$postData['id']])->update(['zid'=>$ff]);
                    $this->success('ok'); 
                }
            }else{
                Db::name('web_note_comment')->where(['id'=>$postData['id']])->setInc('zan');
                Db::name('web_note_comment')->where(['id'=>$postData['id']])->update(['zid'=>session('user.id').',']);
                $this->success('ok');             
            }

        }
    }

    /*//===========首页==========///*/

    /**
     * 疯抢  按商品推荐查询
     */
    public function fc(){
        $goods_Hotlist = Db::name('shop_goods') ->where(['status'=>1,'trash'=>0]) ->order(['sales_sum'=>'DESC'])->select();
        $this->assign('goods_Hotlist',$goods_Hotlist);
        // dump($goods_Hotlist);
        return $this->fetch();
    }


    /**

     * 热门笔记

     */
    public function hot_more(){

        $note_tag = Db::name('web_note_hot_tag')->where(['status'=>1,'tag_type'=>3])->order(['c_num'=>'DESC'])->select();
        foreach ($note_tag as $key => $value) {
            $hot_note_num = Db::name('web_note')->where(['tid'=>$value['id'],'tuijian'=>3])->count('*');
            // $this->assign('hot_note_num',$hot_note_num);
            // // 参与人数 笔记数量
            $note_tag[$key]['hot_note_num'] = $hot_note_num;
        }
        $this->assign('note_tag',$note_tag);
        return $this->fetch();
    }

	/**
	 * 热门笔记详情
	 */
	public function note_msg($id){
//		echo $id;die;
		$hot_note = Db::name('web_note')->where('id',$id)->find();
		//相关容妆
		$xg_note = Db::name('web_note')->where('id','>',$id)->select();
		$pic      =explode(',',$hot_note['link']);
		unset($pic[0]);
		$this->assign('hot_note',$hot_note);
		$this->assign('xg_note',$xg_note);
		$this->assign('pic',$pic);
//		echo "<pre/>";
//		print_r($xg_note);
//		die;

//		print_r($pic);
		return $this->fetch();
	}


	/**
	 * 每日一妆
	 */
	public function day(){

        $note_tag = Db::name('web_note_hot_tag')->where(['status'=>1,'tag_type'=>1])->order(['c_num'=>'DESC'])->select();
        foreach ($note_tag as $key => $value) {
            $hot_note_num = Db::name('web_note')->where(['tid'=>$value['id'],'tuijian'=>1])->count('*');
            // $this->assign('hot_note_num',$hot_note_num);
            // // 参与人数 笔记数量
            $note_tag[$key]['hot_note_num'] = $hot_note_num;
        }
        $note_list = Db::name('web_note')->order(['view'=>'DESC'])->select();
        $this->assign('note_list',$note_list);
        $this->assign('note_tag',$note_tag);
        return $this->fetch();
	}
	/**
	 * 每日一妆详情
	 */
	public function day_msg(){

		return $this->fetch();
	}

	/**
	 * 爱豆
	 */
	public function aidou(){
        $note_tag = Db::name('web_note_hot_tag')->where(['status'=>1,'tag_type'=>2])->order(['c_num'=>'DESC'])->select();
        foreach ($note_tag as $key => $value) {
            $hot_note_num = Db::name('web_note')->where(['tid'=>$value['id'],'tuijian'=>2])->count('*');
            // $this->assign('hot_note_num',$hot_note_num);
            // // 参与人数 笔记数量
            $note_tag[$key]['hot_note_num'] = $hot_note_num;
        }
        $note_list = Db::name('web_note')->order(['view'=>'DESC'])->select();
        $this->assign('note_list',$note_list);
        $this->assign('note_tag',$note_tag);
        return $this->fetch();
	}

	/**
	 * 爱豆详情
	 */
	public function aidou_msg(){

		return $this->fetch();
	}


	/**
     * 种草
	 * 热门笔记详情
     * $id 话题ID
     * $cid  商品分类ID
     * $by 排序
     * $tpid 话题类别ID  1每日一妆  2爱豆推荐  3热门笔记
     */
    public function note_list($id=null,$cid=null,$by=null,$tpid=null){
        // session('note_tid',$id);
        $this->assign('tpid',$tpid);
        $tids = Request::instance()->param('id');
        if(!empty($by)){
            switch ($by) {
                case 'sort':
                    $order = ['add_time'=>'DESC'];
                    break;
                case 'zan':
                    $order = ['zan'=>'DESC'];
                    break;
                case 'collect':
                    $order = ['shouc'=>'DESC'];
                    break;
                case 'recomm':
                    $order = ['comment_num'=>'DESC'];
                    break;                    
                case 'view':
                    $order = ['view'=>'DESC'];
                    break;  
                case 'time':
                    $order = ['add_time'=>'DESC'];
                    break;                      
            }
        }else{
            $order = ['view'=>'DESC'];
        }
        // dump($order);
        if(!empty($id)){
            $note_tag = Db::name('web_note_hot_tag')->where(['id'=>$id,'status'=>1,'tag_type'=>$tpid])->select();
            // dump($note_tag);die;
            $hot_note = Db::name('web_note')->where(['tid'=>$note_tag[0]['id'],'tuijian'=>$tpid])->order($order)->select();
            $hot_note_num = Db::name('web_note')->where(['tid'=>$note_tag[0]['id'],'tuijian'=>$tpid])->count('*');
            $this->assign('hot_note_num',$hot_note_num);
            $this->assign('note_tag',$note_tag[0]);
            $this->assign('hot_note',$hot_note);

            // 推荐

            $note_tag_tui = Db::name('web_note_hot_tag')->where(['status'=>1,'tag_type'=>$tpid,'is_tui'=>1])->order(['c_num'=>'DESC'])->limit(3)->select();
            if(!empty($note_tag_tui)){
                // dump($note_tag_tui);
                $this->assign('note_tag_tui',$note_tag_tui[0]);
                // $this->assign('note_tui',$note_tag_tui);
            }
            

        }else{
    		$note_tag = Db::name('web_note_hot_tag')->where(['status'=>1])->order(['c_num'=>'DESC'])->limit(1)->select();
            $hot_note = Db::name('web_note')->where(['tid'=>$note_tag[0]['id']])->order($order)->select();
            $hot_note_num = Db::name('web_note')->where(['tid'=>$note_tag[0]['id']])->count('*');
            // dump($hot_note_num);
            $this->assign('hot_note_num',$hot_note_num);
    		$this->assign('note_tag',$note_tag[0]);
            $this->assign('hot_note',$hot_note);
        }

        /*if(!empty($cid)){
            // $note_tag = Db::name('web_note_hot_tag')->where(['id'=>$id])->select();
            $hot_note = Db::name('web_note')->where(['tid'=>session('note_tid'),'cid'=>$cid])->order(['view'=>'DESC'])->select();
            // $hot_note_num = Db::name('web_note')->where(['tid'=>$note_tag[0]['id']])->count('*');
            // dump($hot_note_num);
            // $this->assign('hot_note_num',$hot_note_num);
            // $this->assign('note_tag',$note_tag[0]);
            $this->assign('hot_note',$hot_note);            
        }*/

        
        // dump($hot_note);
        return $this->fetch();
    }


    /**
     * 排行 活动投票
     */
    public function ranking(){

        return $this->fetch();
    }

    /**
     * 排行 粉丝排行
     */
    public function ranking1(){

        return $this->fetch();
    }
    /**
     * 排行 销量排行
     */
    public function ranking2(){

        return $this->fetch();
    }
    /**
     * 排行 店铺排行
     */
    public function ranking3(){

        return $this->fetch();
    }


    /**
     * 预约彩妆培训
     */
    public function train()
    {
        return $this->fetch();
    }

    // 取最近的学校及该学校的培训师列表
    public function getNearSchool()
    {
        $data = input('post.');
        $lat = $data['pos_lat'];
        $lng = $data['pos_long'];
        $table = config('database.prefix').'user_school';
        $field = ['lat'=>'pos_lat','lng'=>'pos_long'];
        $pos = ['lat'=>$lat,'lng'=>$lng];

        $nearest = getNearByList($table,$field,$pos);
        $nearest = $nearest[0];
        $Html = $this->getSchoolTrainer($nearest['name'],0);
        return ['code'=>1,'msg'=>'获取成功','html'=>$Html,'school'=>$nearest['name']];
    }

    public function getSchoolTrainer($school='',$ajax=1)
    {
        $trainers = Db::name('user_trainer') ->where('school',$school) ->select();
        $this->assign('trainers',$trainers);
        $Html = $this->fetch('ajaxTrainerList');
        if ($ajax==1) {
            return ['code'=>1,'msg'=>'获取成功','html'=>$Html];
        } else {
            return $Html;
        }
        
    }

    // 选择学校
    public function schools()
    {
        $schools = Db::name('user_school') ->where('status',1) ->select();
        $this->assign('schools',$schools);
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function train_msg($id)
    {
        $trainer = Db::name('user_trainer') ->find($id);
        $this->assign('trainer',$trainer);

        $recomm_list = Db::name('user_trainer') ->where('school',$trainer['school']) ->where('id','<>',$id) ->select();
        $this->assign('recomm_trainer',$recomm_list);
        return $this->fetch();
    }

    /**
     * 彩妆预约 提交订单
     */
    public function train_order(){

        return $this->fetch();
    }

    /**
     * 热卖单品
     */
    public function hot(){

        return $this->fetch();
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