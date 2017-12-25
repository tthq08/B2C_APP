<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
use think\Db;

Route::get('categoty/:id','shop/Goods/goodslist');
Route::get('goods/:id','shop/Goods/goodsinfo');
Route::get('cart','shop/Cart/cart');
Route::get('mglist/:id','mshop/Goods/goodslist');
Route::get('detail/:id','mshop/Goods/goodsinfo');
Route::get('cate/:id','web/Index/cate');
Route::get('glist/:id','web/goods/lists');
Route::get('gshow/:id','web/goods/show');
Route::get('info/:id','web/Index/cate_show');

Route::get('video/:id','mshop/Goods/video');
Route::get('vinfo/:id','mshop/Goods/vinfo');

// 域名自定义
$info = api('sys','Domain','getDomainInfo',[$_SERVER['HTTP_HOST']]);
if( !empty( $info ) )
{
    $url = $info['module'].'/'.$info['controller'].'/'.$info['action'].'/'.$info['param'];
    Route::get('/',$url);
}
// 自定义地址
$dispatch = request()->pathinfo();

$diy_url = Db::name('admin_diy_url') ->where('url_sign',$dispatch)->find();
if( !empty($diy_url) ){
    Route::get($dispatch,$diy_url['real_url']);
}else{
    $params = explode('/', $dispatch);
    $url = $params[0];
    unset($params[0]);
    $modules = getDir(APP_PATH);
    // 如果路径参数中只有一个参数，并且不是已有模块，则为自定义路径
    if (!in_array($dispatch, $modules)) {
        $relurl = explode('.',$url);
        $relurl2 = $relurl[0];
        $diy_url = Db::name('admin_diy_url') ->where('url_sign',$relurl2)->find();
        if( $diy_url ){
            $code = '';
            if( !empty($params[1]) ){
                if (strpos($diy_url['real_url'], '?')) {
                    $code = '&';
                }else{
                    $code = '?';
                }
            }
            // dump($params);die;
            for ($i=1; $i <= count($params); $i+=2) {
                if(!empty($params[$i+1])){
                    $code .= $params[$i].'='.$params[$i+1].'&';
                }
            }
            $ruler = $diy_url['real_url'].rtrim($code,'&');
            if( !empty($relurl[1]) ){
                if( $relurl[1] == config('default_return_type') ){
                    Route::get($relurl2,$ruler);
                }
            }
            Route::get($url,$ruler);
        }
    }
}

