<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/28
 * Time: 下午4:50
 */

namespace app\member\admin;


use app\sys\api\Excel;
use app\sys\controller\AdminBase;
use app\sys\controller\Api;
use think\Db;

class Address extends AdminBase
{

    /**
     *
     */
    private $condition = '';


    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->listFilter();
    }


    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $list = api('member','Address','allAddress',['',$this->condition]);
        $this->assign($list);
        return $this->fetch();
    }


    private function listFilter()
    {
        session('select_address_param',request()->param());
        if( !empty(request()->param('user')) ){
            $user = request()->param('user');
            $userCondition = '`nickname` like "%'.$user.'%" or `email` like "%'.$user.'%"';
            if( is_numeric($user) ){
                $userCondition .= ' or `id` = '.$user;
            }
            $userIdList = Db::name('users')->where($userCondition)->column('id');
            $userIdList = implode(',',$userIdList);
            if( !empty($userIdList) ){
                $this->condition = empty($this->condition) ? ' `user_id` in('.$userIdList.') ': $this->condition.' and `user_id` in('.$userIdList.') ';
            }
        }
        if( !empty(rtrim(request()->param('province'))) ){
            $this->condition = empty($this->condition) ? ' `province` = '.request()->param('province'): $this->condition.' and  `province` = '.request()->param('province');
        }
        if ( !empty(rtrim(request()->param('city'))) ){
            $this->condition = empty($this->condition) ? ' `city` = '.request()->param('city'): $this->condition.' and  `city` = '.request()->param('city');
        }
        if( !empty(rtrim(request()->param('district'))) ){
            $this->condition = empty($this->condition) ? ' `district` = '.request()->param('district'): $this->condition.' and  `district` = '.request()->param('district');
        }
        if( !empty(request()->param('addressKeyword')) ){
            $this->condition = empty($this->condition) ? ' `address` like "%'.request()->param('addressKeyword').'%"': $this->condition.' and  `address` like "%'.request()->param('addressKeyword').'%"';
        }
    }

    /**
     * 编辑地址
     * @param int $id
     * @return mixed
     */
    public function edit($id)
    {


        $addressInfo = Db::name('user_address')->where(['id'=>$id])->find();
        if( $addressInfo == '' ){
            $this->error(lang('user_address_wrong'));
        }
        //获取省份列表
        $provinceList = Api::getChildAddress(0,1);
        //获取城市列表
        $cityList = Api::getChildAddress($addressInfo['province'],2);
        //获取区县列表
        $districtList = Api::getChildAddress($addressInfo['city'],3);

        $this->assign('area_code',api('sys','Address','selectAreaCode'));
        $this->assign('address',$addressInfo);
        $this->assign('province',$provinceList);
        $this->assign('city',$cityList);
        $this->assign('district',$districtList);
        return $this->fetch();
    }


    /**
     * 保存地址
     * @return mixed
     */
    public function save()
    {
        $postData = request()->post();
        $rule = [
            'consignee' => 'require',
            'province' => 'require|number',
            'city' => 'require|number',
            'district' => 'require|number',
            'address' => 'require',
            'mobile' => 'require',
        ];
        $message = [
            'consignee.require'=>'Please enter your consignee!','province.require'=>'Please choose the Province!','province.number'=>'Error!','city.require'=>'Please choose the City!','city.number'=>'Error!','district.require'=>'Please choose the District!','district.number'=>'Error!','address.require'=>'Please enter your address!','zip.number'=>'Please enter postal code!','mobile.require'=>'Please enter you phone number!'
        ];
        $check = api('sys','Verification','valiCheck',[$rule,$postData,$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        $saveData['consignee'] = $postData['consignee'];
        $saveData['province'] = $postData['province'];
        $saveData['city'] = $postData['city'];
        $saveData['district'] = $postData['district'];
        $saveData['address'] = $postData['address'];
        $saveData['mobile'] = $postData['mobile'];
        $saveData['email'] = empty($postData['email']) ? '' : $postData['email'];
        $saveData['company_name'] = empty($postData['company_name']) ? '' : $postData['company_name'];
        $saveData['phone'] = isset($postData['phone']) ? $postData['phone'] : '';
        $saveData['zip'] = isset($postData['zipcode']) ? $postData['zipcode'] : '';

        //存入数据库
        $save = Db::name('user_address')->where(['id' => $postData['id']])->update($saveData);

        if ($save === false) {
            $this->error('收货地址保存失败');
            sys_log('收货地址保存失败',0);  //操作结果写入系统日志
        }
        sys_log('收货地址保存成功',1);  //操作结果写入系统日志
        if (empty(cookie('call_back'))) {
            $this->success('收货地址保存成功');
        }
        $this->success('收货地址保存成功!');
    }


    /**
     * 删除地址
     * @return mixed
     */
    public function delete()
    {
        $id = !empty(request()->post('id')) ? request()->post('id') :'';
        if( empty($id) ){
            $this->error('请选择需要删除的地址');
        }
        $del = api('member','Address','deleteAddress',[$id]);
        if( $del !== true ){
            sys_log(lang('comm_delete_error'),0);  //操作结果写入系统日志
            $this->error($del);
        }else{
            sys_log(lang('comm_delete_success'),1);  //操作结果写入系统日志
            $this->success(lang('comm_delete_success'));
        }
    }


    /**
     * 导出选择的地址信息
     * @return mixed
     */
    public function export()
    {
        if( request()->param('ids') ){
            $ids = explode(',',htmlspecialchars_decode(request()->param('ids')));
            $ids = array_filter($ids);
            foreach ($ids as $key => $value){
                $ids[$key] = intval($value);
            }
            $ids = implode(',',$ids);
            $condition = '`id` in ('.$ids.')';
        }else{
            $condition = session('select_address_param');
        }
        $list = api('member','Address','addressList',[$condition]);
        sys_log('用户收货地址导出',1);
        $this->exportData($list);
    }


    /**
     * 导出
     * @param $list
     */
    private function exportData($list)
    {
        $data[0] = ['ID','会员','收货人','名','姓','电话区号','手机号码','邮箱','公司名称','省份','城市','区县','详细地址','邮编'];
        $i = 1;
        foreach ($list as $key=>$item) {
            $data[$i]=[
                $item['id'],$item['user'],$item['consignee'],$item['area_code'],$item['mobile'],$item['email'],$item['company_name'],getAddressName($item['province']),getAddressName($item['city']),getAddressName($item['district']),$item['address'],$item['zip']
            ];
            $i++;
        }
        $excelData[0] = $data;
        $excel = new Excel('Excel2007');
        $fileName = date('Y_m_d').'_用户地址导出'.'.xlsx';
        $excel->setData($excelData);
        $excel->setFileName($fileName);
        $excel->export();

    }

}