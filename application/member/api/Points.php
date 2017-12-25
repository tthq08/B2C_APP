<?php
/**
 * ThinkBiz^会员积分记录api
 * remark： 记录会员积分操作记录
 * company: 深圳市俊网网络有限公司
 * http: www.junnet.net
 * time: 2017/6/23
 * author：吴跃忠（357397264@qq.com）
 */

namespace app\member\api;
use think\Db;

class Points
{
    /**
     * 数据配置
     */
    // 积分来源
    protected $sourceConfig = [
        0=>'后台发放',
        1=>'签到',
        2=>'商品交易',// 包括促销活动、优惠券赠送
        3=>'虚拟商品交易',
        4=>'抽奖获取',
        5=>'注册',
        6=>'消费使用',
        7=>'抽奖使用',
        8=>'退货回收',
        9=>'惩罚回收',
        10=>'取消订单',
    ];


    /**
     * 记录会员日志信息,修改会员表总积分记录
     * @param int $user 会员ID
     * @param int $score 积分数量
     * @param int $source 积分来源
     * @param int $sn 编号：订单交易为订单编号，退货为退货编号，抽奖交易为抽奖编号，签到、注册、惩罚无。
     * @param int $auto 自动记录 or 手动记录
     * @param string $remark 积分备注
     * @return boolean
     * 说明：
     *      type1:  签到、抽奖获取、注册、消费使用、抽奖使用、退货回收、惩罚回收。为立即处理用户积分，不设置冻结时间。
     *      type2:  商品交易、虚拟商品交易。需要设置解冻时间，解冻时间指记录之日起至（记录之日加解冻时间），解冻时间由后台配置决定，解冻后添加进用户总积分，减少用户冻结积分量(如果退货,减少用户总积分)。
     *      type3:  签到、商品交易(商品赠送积分)、虚拟商品交易(商品赠送积分)、抽奖获取、注册。为用户收入积分，ior需设置为1
     *      type4:  消费使用(使用积分支付)、抽奖使用、退货回收、惩罚回收。为用户支出积分，ior需设置为0
     *      type5:  取消订单。从用户冻结资金取出。
     *
     *      注：该方法包含处理用户表总积分，冻结积分，请勿在该方法外重复设置。
     */
    public function record($user,$score,$source,$sn = '',$auto = 1,$remark = '')
    {
        if( $score == 0 )
        {
            return true;
        }
        $record['ior'] = in_array($source,[0,1,2,3,4,5,10]) ? 1 : 0 ;// 设置ior
        $record['user'] = $user;// 设置用户
        $record['score'] = $score;// 积分数量
        $record['auto'] = $auto;// 是否自动记录
        $record['remark'] = $remark;// 积分备注
        $record['sn'] = $sn;// 编号
        $record['time'] = NOW_TIME;

        // 设置记录地址、解冻时间
        $record['address'] = request()->module().'/'.request()->controller().'/'.request()->action();

        // 设置用户表修改字段 ： 总积分，冻结积分
        $field = in_array($source,[2,3,10]) ? 'pay_points' : 'pay_points';
        // 操作 setInc ： setDec
        $operation = $record['ior'] == 1 ? 'setInc' : 'setDec';
        try{
            Db::name('users')->where('id',$user)->$operation($field,$score);
            Db::name('user_points')->insert($record);
        }catch (\Exception $e){
            return $e;
        }
        return true;
    }

}