<?php
/**
 * Created by PhpStorm.
 * User: TBMALL
 * Date: 2017/5/12
 * Time: 16:46
 */

namespace app\mshop\home;

use app\mshop\home\HomeBase;
use think\Db;
use app\shop\api\Pieces as PiecesApi;
use app\shop\api\Goods;

class Pieces extends HomeBase
{
    /**
     * 立即拼团 开团
     */
    public function index()
    {
        //查看type是否存在
        $type = request()->param('type');
        if( $type == 'hot_list' ){
            $group_goods = PiecesApi::availableList();
            $this->assign('group_goods_list',$group_goods);
            return $this->fetch('groups_list');
        }else{
            //获取今日热拼榜
            $group_goods = PiecesApi::availableList(5);
            //获取最新的一个
            $new_group_goods = PiecesApi::newPieces();
            $this->assign('group_goods_list',$group_goods);
            $this->assign('new_group_goods',$new_group_goods);
            return $this->fetch();
        }
    }

    /**
     * 拼团详情
     */
    public function pieces_group($id,$head = '')
    {
        if(empty($id)){
            $this->error('参数错误');
        }
        $param = explode('--', $id);
        $goods_id = $param[0];

        //获取团购活动数据
        $nowData = date('Y-m-d H:i:s');
        $group_info = Db::name('shop_pieces_group')->where('status',1)->where('start_time < "'.$nowData.'"')->where('end_time > "'.$nowData.'"')->where('goods',$id)->find();
        $pieces_id = $group_info['id'];
        //查看商品是否参团
        if( empty($group_info) ){
            return $this->error('该商品未参团');
        }
        //参加拼团的规格
        $spec = $group_info['goods_spec'];
        $goodsspec = unserialize($group_info['goods_spec_price']);

        //获取拼团的价格
        // 增加商品的点击量
        Db::name('shop_goods') ->where('id',$goods_id) ->setInc('view');

        // 获取商品详情数据
        $goodsInfo = Db::name('shop_goods') ->find($goods_id);
        $goodsInfo['comment_count'] = Db::name('shop_goods_comment') ->where('goods_id',$goods_id) ->count('id');
        $goodsInfo['brand_name'] = getTableValue('shop_brand',['id'=>$goodsInfo['brand_id']],'name');
        $this->assign('goods',$goodsInfo);
        // 取商品所有的规格数据
        if( !empty($spec) ){
            $spec_list = Db::name('shop_spec_price') ->where('id in('.$spec.')') ->where('goods_id',$goods_id) ->order(['price'=>'ASC']) ->column('key_sign');
            $filter_spec = [];
            if ($spec_list) {
                // 如果参数中没有规格,则将最低价格的规格作为当前规格
                if (!isset($param[1])) {
                    $spec_curr = $spec_list[0];
                    $param[1] = $spec_list[0];
                }else{
                    $spec_curr = $param[1];
                }
                //获取spec的ID
                $sed_spec = Db::name('shop_spec_price')->where('key_sign',$param['1'])->field('id,price')->find();
                if(!empty($goodsspec[$sed_spec['id']])){
                    $group_info['price'] = $goodsspec[$sed_spec['id']];
                }else{
                    $group_info['price'] = $sed_spec['price'];
                }

                $this->assign('spec_curr',$spec_curr);
                $lowPriceKey = explode('_', $spec_curr);
                $this->assign('lowPriceKey',$lowPriceKey);
                //查找已选字符
                $selectedSpec = '';
                foreach ($lowPriceKey as $k => $v) {
                    $spec_id = getTableValue('shop_spec_item',['id'=>$v],'spec_id');
                    $selectedSpec .= getTableValue('shop_spec_item',['id'=>$v],'item').',';
                    $curr[$spec_id] = $v;
                }
                //默认的规格金额
                $this->assign('selectedSpec',rtrim($selectedSpec,','));

                $spec_str  = implode('_', $spec_list);
                $spec_arr = explode('_', $spec_str);
                $spec_arr = array_unique($spec_arr);
                sort($spec_arr);
                $spec_id = Db::name('shop_spec_item') ->where('id','IN',$spec_arr) ->column('spec_id');
                $spec_id = array_unique($spec_id);
                foreach ($spec_id as $key => $spec) {
                    $filter_spec[$spec] = Db::name('shop_spec') ->find($spec);
                    $item = Db::name('shop_spec_item') ->where('spec_id',$spec) ->where('id','IN',$spec_arr) ->cache(true) ->select();
                    foreach ($item as $k => $v) {
                        $spec_ed = $curr;
                        $spec_ed[$spec] = $v['id'];
                        $key = implode('_', $spec_ed);
                        $url_param = $param;
                        $url_param[1] = $key;
                        $url['id'] = implode('--', $url_param);
                        $item[$k]['href'] = url('mshop/pieces/pieces_group',$url);
                    }
                    $filter_spec[$spec]['item'] = $item;
                }
                $this->assign('filter_spec',$filter_spec);
            }
        }

        $this->assign('group_info',$group_info);

        // 取商品属性数据
        $spec_attr = '0';
        if (isset($param[1])) {
            $spec_attr = $param[1];
        }
        // 输出当前商品的属性值
        $attr_list = Db::name('shop_goods_attr') ->where(['goods_id'=>$goods_id,'spec_key'=>$spec_attr]) ->column('attr_value','attr_id');
        $this->assign('attr_list',$attr_list);
        // 输出当前商品的所有属性项目
        $attribute = Db::name('shop_attribute') ->where('cate_id',$goodsInfo['type_id']) ->column('attr_name','id');
        $this->assign('attribute',$attribute);

        // 如果商品带有规格数据,则取对应规格的相册,如相册为空则取商品默认相册
        if (isset($param[1])) {
            // 取对应规格的商品相册
            $goods_images_list = Db::name('shop_spec_image') ->where(['goods_id'=>$goods_id,'spec_key'=>$param[1]]) ->select();
            if (empty($goods_images_list)) {
                // 对应规格相册不存在,则取商品默认相册
                $goods_images_list = Db::name('shop_goods_images') ->where('goods_id',$goods_id) ->select();
            }

            // 取对应规格的商品详情
            $goods_content = Db::name('shop_spec_content') ->where(['goods_id'=>$goods_id,'spec_key'=>$param[1]]) ->value('content');
            $spec_goods = Db::name('shop_spec_price') ->where(['goods_id'=>$goods_id,'key_sign'=>$param[1]]) ->field('price,store_count') ->find();
            $goods_content = empty($goods_content)?$goodsInfo['content']:$goods_content;
            $goods_price = empty($spec_goods)?$goodsInfo['shop_price']:$spec_goods['price'];
            $goods_stock = empty($spec_goods)?$goodsInfo['stock']:$spec_goods['store_count'];
        }else{
            // 商品没有规格参数,取商品的默认相册数据,默认的商品详情
            $goods_images_list = Db::name('shop_goods_images') ->where('goods_id',$goods_id) ->select();
            $goods_content = $goodsInfo['content'];
            $goods_price = $goodsInfo['shop_price'];
            $goods_stock = $goodsInfo['stock'];
        }
        $this->assign('goods_images_list',$goods_images_list);
        $this->assign('goods_content',$goods_content);
        $this->assign('goods_price',$goods_price);
        $this->assign('goods_stock',$goods_stock);

        //获取商品最新评论
        $new_comment = Db::name('shop_goods_comment')->where('goods_id',$goods_id)->where('content is not null')->find();
        if( $new_comment != '' ){
            //用户名称
            $new_comment['user'] = Db::name('users')->where('id',$new_comment['user_id'])->field('head_pic,nickname')->find();
            //评论图片
            $new_comment['img'] = json_decode($new_comment['img']);
            $new_comment['img_count'] = count($new_comment['img']);
            $this->assign('comment',$new_comment);
        }

        //扫码参团
        if( !empty($head) ){
            //检测该用户是否参加该团
            $head_id = getTableValue('users','sysid='.$head,'id');
            if( !empty($head_id) ){
                $head = PiecesApi::getHead($head_id,$pieces_id);
                session('pieces_head',$head);
            }
        }else{
            //查看当前用户是否参与该团购
            if( empty(session('pieces_head')) ){
                $head = PiecesApi::getHead(session('user.id'),$pieces_id);
            }else{
                $head = session('pieces_head');
            }
        }

        if( $head ){
            //获取团长信息
            $head_info = Db::name('users')->where('id',$head)->field('head_pic,nickname')->find();
            $this->assign('head_info',$head_info);
            //还差多少人成团
            $D_value = PiecesApi::getDvalue($pieces_id);
            $this->assign('D_value',$D_value);
        }

        $pieces = PiecesApi::get(PiecesApi::getPiecesId($goods_id),'title,end_time');
        $this->assign('pieces',$pieces);
        $this->assign('comment',$new_comment);
        if( request()->isAjax() ){
            $this->success($this->fetch('ajax_pieces_group'));
        }
        return $this->fetch();
    }


    /**
     * 确认订单页面
     */
    public function pay()
    {
        //检测用户是否登录
        if( empty(session('user.id')) ){
            $this->error(lang('cart_please_login'),url('mshop/user/login'));
        }
        $pay_data['goods_spec'] = empty(request()->param('goods_spec')) ? '' : request()->param('goods_spec');
        $pay_data['goods_num'] = empty(request()->param('goods_num')) ? '' : request()->param('goods_num');
        $pay_data['goods_id'] = empty(request()->param('goods_id')) ? '' : request()->param('goods_id');
        $this->assign($pay_data);

        $data = input('');
        $spec_key = isset($data['goods_spec']) ? $data['goods_spec'] : 0;
        if ($spec_key==0) {
            $goods_specs = Db::name('shop_spec_price') ->where('goods_id',$data['goods_id']) ->find();
            if ($goods_specs) {
                return ['code'=>'-1','msg'=>'选择商品规格'];
            }
        }
        $spec = Db::name('shop_spec_price') ->field('id,key_name') ->where(['goods_id'=>$data['goods_id'],'key_sign'=>$spec_key]) ->find();
        //获取价格
        $spec_id = empty($spec['id']) ? '' : $spec['id'];
        $spec['price'] = PiecesApi::getSpecPrice($data['goods_id'],$spec_id);

        $goods = Db::name('shop_goods') ->field('id,title,thumb,shop_price,goods_sn') ->find($data['goods_id']);
        //检测是否有当前用户的订单
        $pieces_id = PiecesApi::getPiecesId($goods['id']);
        $userOrder = PiecesApi::getUserOrder(session('user.id'),$pieces_id);
        if( $userOrder !== false ) {
            if ($userOrder['is_pay'] == 1) {
                $this->error('您已参加过当前拼团');
            }
        }
        $this->assign('spec',$spec);
        $this->assign('goods',$goods);
        //查看是否选择地址，没有的话选择默认地址
        if( request()->param('addres') ){
            //获取地址
            $address = Db::name('user_address')->where(['id'=>request()->param('addres'),'user_id'=>session('user.id')])->find();
        }else{
            //获取默认地址
            $address = Db::name('user_address')->where(['is_default'=>1,'user_id'=>session('user.id')])->find();
            if( empty($address) ){
                $address = Db::name('user_address')->where(['user_id'=>session('user.id')])->find();
            }
        }
        //赋值
        $now_address = getAddressName($address['province']).'&nbsp;'.getAddressName($address['city']).'&nbsp;'.getAddressName($address['district']).'&nbsp;'.$address['address'];
        $this->assign('address_id',$address['id']);
        $this->assign('address',$now_address);
        $this->assign('consignee',$address['consignee']);
        $this->assign('phone',$address['mobile']);

        return $this->fetch();
    }

    /**
     * 保存订单
     */
    public function save_order()
    {
        if( empty(session('user.id')) ){
            $this->error('请先登录');
        }
        $data = request()->param();
        //获取当前的选中值
        $spec_key = isset($data['goods_spec']) ? $data['goods_spec'] : 0;
        if ($spec_key==0) {
            $goods_specs = Db::name('shop_spec_price') ->where('goods_id',$data['goods_id']) ->find();
            if ($goods_specs) {
                return ['code'=>'-1','msg'=>'选择商品规格'];
            }
        }
        $spec = Db::name('shop_spec_price') ->field('id,key_name') ->where(['goods_id'=>$data['goods_id'],'key_sign'=>$spec_key]) ->find();
        //获取价格
        $spec_id = empty($spec['id']) ? '' : $spec['id'];
        $spec['price'] = PiecesApi::getSpecPrice($data['goods_id'],$spec_id);

        $goods = Db::name('shop_goods') ->field('id,cid,shop_id,title,market_price,shop_price,goods_sn') ->find($data['goods_id']);
        //检测商品库存
        if( Goods::getStock($goods['id'],$spec_id) < $data['goods_num'] ){
            return $this->error('商品库存不足');
        }

        //查看是否选择地址，没有的话选择默认地址
        if( request()->param('addres') ){
            //获取地址
            $address = Db::name('user_address')->where(['id'=>request()->param('addres'),'user_id'=>session('user.id')])->find();
        }else{
            //获取默认地址
            $address = Db::name('user_address')->where(['is_default'=>1,'user_id'=>session('user.id')])->find();
            if( empty($address) ){
                $address = Db::name('user_address')->where(['user_id'=>session('user.id')])->find();
            }
        }

        //检测是否有当前用户的订单
        $pieces_id = PiecesApi::getPiecesId($goods['id']);
        $userOrder = PiecesApi::getUserOrder(session('user.id'),$pieces_id);
        if( $userOrder !== false ) {
            if ($userOrder['is_pay'] == 1) {
                $this->error('您已参加过当前拼团');
            }
        }
        $pieces_sn = PiecesApi::createPiecesSn();
        //检测是否有团长
        $head_id = empty(session('pieces_head')) ? session('user.id') : session('pieces_head');
        //是否是团长
        $is_head = $head_id == session('user.id') ? 1 : 0;
        //计算商品全部金额
        $piecesData = [
            'user_id' => session('user.id'),
            'pieces_sn' => $pieces_sn,
            'pieces_id' => $pieces_id,
            'head_id' => $head_id,
            'is_head' => $is_head,
            'goods' => $goods['id'],
            'goods_num' => $data['goods_num'],
            'postage' => 0,
            'total_price' => $goods['shop_price']*$data['goods_num'],//商品总价格
            'payable_price' => $spec['price'],
            'change_mny' => 0,
            'points_price' => 0,
            'spec' => $spec_id,
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'address' => $address['address'],
            'consignee' => $address['consignee'],
            'phone' => $address['mobile'],
            'add_time' => date('Y-m-d H:i:s'),
            'status' => 1,
        ];
        $insert = PiecesApi::addPiecesOrder($piecesData, true);
        if (intval($insert) == 0) {
            $this->error($insert);
        }
        $piecesData['id'] = $insert;
        $this->success('提交成功，将跳转到支付页面',url('mshop/Pieces/do_pay',['id'=>$piecesData['id']]));
    }

    /**
     *
     */
    public function do_pay()
    {
        $pieces_order_id = empty(request()->param('id')) ? 0 : intval(request()->param('id'));
        if( $pieces_order_id == 0 ){
            $this->error('订单错误，请确认是否输入正确!');
        }
        $pieces_id = getTableValue('shop_pieces_order','id='.$pieces_order_id,'pieces_id');
        $piecesData = PiecesApi::getUserOrder(session('user.id'),$pieces_id);
        if( $piecesData['is_pay'] == 1 || $piecesData['status'] == 0 ){
            $this->error('订单已支付');
        }

        $this->assign('D',$piecesData);
        $client = 'phone';
        if ( strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ) {
            $client = 'wechat';
            $this->assign('time_now',time());
        }
        $this->assign('client',$client);
        $payList = getPayList();
        $this->assign('payList',$payList);
        return $this->fetch();
    }

    /**
     * 获取用户的收货地址
     * @return mixed
     */
    public function select_address()
    {
        //获取用户的收货地址
        $address_list = Db::name('user_address')->where('user_id',session('user.id'))->where('status',1)->order('is_default desc')->select();
        $this->assign('address_list',$address_list);
        return $this->fetch('ajax_address');
    }

}