<?php
// +----------------------------------------------------------------------
// | ThinkBiz System
// | 功能： 促销管理api
// +----------------------------------------------------------------------
// | 版权所有 2013~2017 深圳市俊网网络有限公司 [ http://www.junnet.net ]
// +----------------------------------------------------------------------
// | 官方网站：http://www.junnet.net
// +----------------------------------------------------------------------
// | 作者: 吴跃忠 <357397264@qq.com>
// +----------------------------------------------------------------------


namespace app\shop\api;


use think\Cache;
use think\Db;

class Promotion
{
    protected static $_prom_DB;//总表模型
    protected $_prom_panic_DB;//抢购促销模型
    protected $_prom_group_DB;//团购促销模型
    protected $_prom_discount_DB;//优惠促销模型
    protected $_prom_order_DB;//订单促销模型
    protected $type;
    protected $now_time;

    public function __construct($model = '')
    {
        self::$_prom_DB = Db::name('shop_promotion');
        $this->_DB = self::model($model);
        $this->type = empty($model) ? 0 : $model;
        $this->now_time = request()->time();
    }

    /**
     * 设置促销模型
     * @param int $type
     */
    public static function model($model = ''){
        if( $model != '' ){
            switch ($model){
                case 1:
                    return Db::name('shop_promotion_panic');
                    break;
                case 2:
                    return Db::name('shop_promotion_group');
                    break;
                case 3:
                    return Db::name('shop_promotion_discount');
                    break;
                case 4:
                    return Db::name('shop_promotion_order');
            }
        }
    }

    /**
     * 获取抢购优惠信息
     * @param int $id 抢购促销ID
     * @param string $field 获取的字段
     * @return array
     */
    public function getPanicPromInfo($id,$field = ''){
        $id = intval($id);
        $panicInfo = $this->_DB->where('panic_id',$id)->field($field)->find();
        return $panicInfo;
    }

    /**
     * 获取团购优惠信息
     * @param int $id 团购促销ID
     * @param string $field 获取的字段
     * @return array
     */
    public function getGroupPromInfo($id,$field = ''){
        $id = intval($id);
        $panicInfo = $this->_DB->where('group_id',$id)->field($field)->find();
        return $panicInfo;
    }

    /**
     * 获取优惠促销信息
     * @param int $id 优惠促销ID
     * @param string $field 获取的字段
     * @return array
     */
    public function getDiscountPromInfo($id,$field = ''){
        $id = intval($id);
        $panicInfo = $this->_DB->where('discount_id',$id)->field($field)->find();
        return $panicInfo;
    }

    /**
     * 获取订单优惠信息
     * @param int $id 订单促销ID
     * @param string $field 获取的字段
     * @return array
     */
    public function getOrderPromInfo($id,$field = ''){
        $id = intval($id);
        $panicInfo = $this->_DB->where('order_id',$id)->field($field)->find();
        return $panicInfo;
    }


    /**
     * 查看商品（规格）加入的促销信息
     * @param int $goods 商品ID
     * @return array|bool
     */
    public function promInfoFormGoods($goods)
    {
        // 查找prom-id
        $now_date = date('Y-m-d H:i:s', NOW_TIME);
        $prom = Db::name('shop_promotion')->where('goods',$goods)->where('`start_time` < "'.$now_date.'" and `end_time` > "'.$now_date.'"')->order('`start_time` desc')->find();
        if( empty($prom) || $prom['p_type'] == 4 ){
            return false;
        }
        if( !empty(cache('promotion:'.$prom['p_type'].':'.$prom['p_id'])) ){
            switch ($prom['p_type']){
                case 1:
                    $p_name = 'panic';
                    break;
                case 2:
                    $p_name = 'group';
                    break;
                case 3:
                    $p_name = 'discount';
                    break;
                case 4:
                    $p_name = 'order';
                    break;
            }
            // 获取信息
            $promInfo = Db::name('shop_promotion_'.$p_name)->where($p_name.'_id',$prom['p_id'])->find();
            // 获取商品信息
            $prom_goods = Db::name('shop_promotion_goods')->where('prom_id',$prom['prom_id'])->find();
            $promInfo['prom_goods'] = $prom_goods;
            $promInfo['prom_goods']['goods_spec'] = unserialize($promInfo['prom_goods']['goods_spec']);
            if( empty($promInfo) ){
                return false;
            }
            cache('promotion:'.$prom['p_type'].':'.$prom['p_id'],$promInfo,'','shop-promotion');
        }else{
            $promInfo = cache('promotion:'.$prom['p_type'].':'.$prom['p_id']);
        }
        return $promInfo;
    }


    /**
     * 检测促销是否正常/能否使用该促销活动
     * @param int $prom_id 总表ID
     * @return bool
     */
    public function checkProm($prom_id){
        //获取促销类型，ID
        $prom = self::$_prom_DB->where('prom_id',$prom_id)->field('p_type,p_id,start_time,end_time')->find();
        $now_date = date('Y-m-d H:i:s');
        if( $prom['start_time'] > $now_date || $prom['end_time'] < $now_date ){
            return false;
        }
        switch ($prom['p_type']){
            case 1:
                $prom_info = $this->getPanicPromInfo($prom['p_id']);
                //验证促销是否可用
                if( $prom_info['status'] > 0 || $prom_info['is_end'] > 0 ){
                    return true;
                }
                break;
            case 2:
                $prom_info = $this->getGroupPromInfo($prom['p_id']);
                if( $prom_info['status'] > 0 || $prom_info['is_end'] > 0 ){
                    return true;
                }
                break;
            case 3:
                $prom_info = $this->getDiscountPromInfo($prom['p_id']);
                if( $prom_info['status'] > 0 || $prom_info['is_end'] > 0 ){
                    return true;
                }
                break;
            case 4:
                $prom_info = $this->getOrderPromInfo($prom['p_id']);
                if( $prom_info['status'] > 0 || $prom_info['is_end'] > 0 ){
                    return true;
                }
                break;
        }
        return false;
    }


    /**
     *
     */
    public function showPanicList($type,$limit = '',$page = 1)
    {
        $time_slot = $this->getTimeSlot($type);
        $selected_time_slot = $time_slot['selected_time_slot'];
        $date = date('y-m-d',$this->now_time).$selected_time_slot['time'];
        if( empty(cache('promotion_panic_list:'.$date.'page:'.$page)) ) {
            $condition = [
                'spp.time_slot' => $selected_time_slot['time'],
                'spp.start_time' => ['<=',datetime_format($selected_time_slot['start_time'])],
                'spp.end_time' => ['>=',datetime_format($selected_time_slot['end_time'])],
                'spp.status' => 1,
                'sp.p_type' => 1,
            ];
            $pageRow = $limit;
            // 获取当前抢购活动
            $panicRow = Db::name('shop_promotion_panic')->alias('spp')->join('__SHOP_PROMOTION__ sp','spp.panic_id = sp.p_id')->where($condition)->field('spp.*,sp.prom_id')->paginate($pageRow);

            $pages = $panicRow->render();
            $list = [];
            foreach ($panicRow as $item=>$panic)
            {
                $list[$item] = $panic;
                $list[$item]['goods_spec'] = json_decode(htmlspecialchars_decode($panic['goodsspec']),1);
                // 获取商品信息
                $list[$item]['goods']  = Db::name('shop_promotion_goods')->alias('spg')->join('__SHOP_GOODS__ sg','spg.goods_id = sg.id')->where('spg.prom_id',$panic['prom_id'])->field('spg.*,sg.thumb,sg.shop_price,sg.title')->find();
            }
            // 取下期展示商品
            $start_time = strtotime(date('Y-m-d',time()))+32*3600;
            $nextList= [];
            $nextRow = Db::name('shop_promotion_panic')->alias('spp')->join('__SHOP_PROMOTION__ sp','spp.panic_id = sp.p_id')->where('sp.p_type',1)
                ->where('spp.start_time <= "'.date('Y-m-d H:i:s',$start_time).'"')->field('spp.*,sp.prom_id')->limit(4)->select();
            foreach ($nextRow as $item=>$panic)
            {
                $nextList[$item] = $panic;
                $nextList[$item]['goods_spec'] = json_decode(htmlspecialchars_decode($panic['goodsspec']),1);
                // 获取商品信息
                $nextList[$item]['goods']  = Db::name('shop_promotion_goods')->alias('spg')->join('__SHOP_GOODS__ sg','spg.goods_id = sg.id')->where('spg.prom_id',$panic['prom_id'])->field('spg.*,sg.thumb,sg.shop_price,sg.title')->find();
            }

            $panicList = [
                'pages' => $pages,
                'list' => $list,
                'next_list' => $nextList,
            ];
            cache('promotion_panic_list:'.$date.'page:'.$page,$panicList,'','shop-promotion');
        }else{
            $panicList = cache('promotion_panic_list:'.$date.'page:'.$page);
        }

        return ['time_slot'=>$time_slot['time_slot'],'now_time_slot'=>$time_slot['now_time_slot'],'pages'=>$panicList['pages'],'list'=>$panicList['list'],'next_list'=>$panicList['next_list'],'now_end_time'=>$time_slot['now_time_slot']['end_time'],'selected_time_slot'=>$time_slot['selected_time_slot']];
    }



    /**
     *
     */
    public function getTimeSlot($type)
    {
        // 定义初始数据
        $flash_time = tb_config('flash_buy_times',1);
        $flash_time = explode(',',$flash_time);
        $now_item = 1;
        $time_slot = [];
        $now_time_slot = [];
        $i = 1;
        foreach ($flash_time as $item=>$vo)
        {
            // 当前时间段开始时间
            $start_time = strtotime(date('Y-m-d').' '.$vo);
            // 当前时间段结束时间
            if( !empty($flash_time[$item+1]) )
            {
                $end_time = strtotime(date('Y-m-d').' '.$flash_time[$item+1]);
            }else{
                $end_time = strtotime(date('Y-m-d 23:59:59'));
            }

            if( $start_time > $this->now_time && $end_time > $this->now_time )
            {
                // 未开始
                $time_slot[$i]['status'] = 2;
            }elseif( $start_time < $this->now_time && $end_time > $this->now_time ){
                // 正在进行中
                $time_slot[$i]['status'] = 1;
                $now_item = $i;
            }elseif ( $start_time < $this->now_time && $end_time < $this->now_time )
            {
                // 已过期
                $time_slot[$i]['status'] = 0;
            }
            $time_slot[$i]['time'] = $vo;
            $time_slot[$i]['start_time'] = $start_time;
            $time_slot[$i]['end_time'] = $end_time;
            if( $time_slot[$i]['status'] == 1 )
            {
                $now_time_slot = $time_slot[$i];
            }
            $i++;
        }
        // 查询商品(如果当期时间段没有开始,取第一条时间段数据)
        if( empty( request()->param('type') ) ){
            $selected_time_slot = $time_slot[$now_item];
            $selected_time_slot['item'] = $now_item;
        }else{
            if( empty($time_slot[$type]) )
            {
                $selected_time_slot = $time_slot[$now_item];
                $selected_time_slot['item'] = $now_item;
            }else{
                $selected_time_slot = $time_slot[$type];
                $selected_time_slot['item'] = $type;
            }
        }
        return ['time_slot'=>$time_slot,'now_time_slot'=>$now_time_slot,'selected_time_slot'=>$selected_time_slot];
    }

}