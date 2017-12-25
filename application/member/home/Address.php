<?php
/**
 * 个人中心用户地址
 * User: jungo
 * Date: 2017/12/19
 * Time: 11:30
 */

namespace app\member\home;

use think\Db;
use app\common\validate\userAddress;
use app\sys\controller\Api;

class Address extends Homebase
{

    /**
     * 用户地址管理
     */
    public function index(){
        //获取用户收货地址
        $nums = Db::name('user_address')->where(['user_id'=>session('user.id'),'status'=>1]) ->count();
        $this->assign('addr_nums',$nums);
        $address = Db::name('user_address')->where('user_id',session('user.id'))->where('status',1)->order('is_default desc') ->paginate(5);
        $addressList = $address -> all();
        foreach ($addressList as $key=>$item){
            $addressList[$key]['address'] = getAddressName($item['province']).getAddressName($item['city']).getAddressName($item['district']).$item['address'];
        }
        $this->assign('lists',$addressList);
        $page = $address -> render();
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * 用户地址添加
     * @return mixed
     */
    public function add(){
        //获取一级地址
        $province = Api::getChildAddress(0,1);
        $this->assign('province',$province);
        return $this->fetch('address_do');
    }


    /**
     * 用户地址编辑
     * @return mixed
     */
    public function edit(){

        //获取当前地址信息
        $id = request()->param('id');
        $addressInfo = Db::name('user_address')->where(['user_id'=>session('user.id'),'id'=>$id,'status'=>1])->find();
        if( $addressInfo == '' ){
            $this->error(lang('user_address_wrong'));
        }
        //获取省份列表
        $provinceList = Api::getChildAddress(0,1);
        //获取城市列表
        $cityList = Api::getChildAddress($addressInfo['province'],2);
        //获取区县列表
        $districtList = Api::getChildAddress($addressInfo['city'],3);

        $this->assign('address',$addressInfo);
        $this->assign('province',$provinceList);
        $this->assign('city',$cityList);
        $this->assign('district',$districtList);
        return $this->fetch('edit_address');
    }

    /**
     * 用户地址保存
     */
    public function save(){

        $postData = request()->post();
        //查看是保存还是添加
        if( empty($postData['id']) ){
            //计算当前用户的收货地址数是否达到最大状态
            //获取用户当前地址数量
            $UAddressNum = Db::name('user_address')->where(['user_id'=>session('user.id'),'status'=>1])->count('id');
            if( $UAddressNum >= 20 ){
                $this->error(lang('user_address_reach_limit'));
            }
            if( $UAddressNum == 0 ){
                $postData['is_default'] = 1;
            }
        }
        $rule = [
            'consignee' => 'require',
            'province' => 'require|number',
            'city' => 'require|number',
            'district' => 'require|number',
            'address' => 'require',
            'zip' => 'number|length:6',
            'mobile' => 'require|number|length:11',
        ];
        $message = [
            'consignee.require'=>'请输入收货人姓名','province.require'=>'请选择省份','province.number'=>'省份选择错误,请重新选择','city.require'=>'请选择城市','city.number'=>'城市选择错误,请重新选择','district.require'=>'请选择区县','district.number'=>'区县选择错误,请重新选择','address.require'=>'请输入地址','zip.number'=>'请输入邮政编码','mobile.require'=>'请输入手机号码'
        ];
        $check = api('sys','Verification','valiCheck',[$rule,$postData,$message]);
        if( $check['code'] == 0 ){
            $this->error($check['error']);
        }
        $saveData['consignee'] = isset($postData['consignee']) ? $postData['consignee'] : '';
        $saveData['province'] = $postData['province'];
        $saveData['city'] = $postData['city'];
        $saveData['district'] = $postData['district'];
        $saveData['address'] = $postData['address'];
        $saveData['mobile'] = $postData['mobile'];
        $saveData['phone'] = isset($postData['phone']) ? $postData['phone'] : '';
        $saveData['zip'] = isset($postData['zip']) ? $postData['zip'] : '';
        $saveData['is_default'] = isset($postData['is_default']) ? 1 : 0;

        if( !empty($saveData['is_default']) ){
            $updateDefault = Db::name('user_address')->where('user_id',session('user.id'))->update(['is_default'=>0]);
        }
        //存入数据库
        if (empty($postData['id'])) {
            //插入
            $saveData['user_id'] = session('user.id');
            $save = Db::name('user_address')->insert($saveData);
        } else {
            //保存
            $save = Db::name('user_address')->where(['user_id' => session('user.id'), 'id' => $postData['id']])->update($saveData);
        }
        if ($save === false) {
            $this->error(lang('user_save_failed'));
        }
        if (empty(cookie('call_back'))) {
            $this->success(lang('user_save_success'), 'mshop/user/address_list');
        }
        $this->success(lang('user_save_success'), cookie('call_back'));
    }

    /**
     * 获取子地址，默认为0
     */
    public function getAddressList($parent_id = 0,$level = 1){
        $addressList = Api::getChildAddress($parent_id,$level);
        $addressHtml = '';
        if( is_array( $addressList ) && count($addressList) >0 ){
            foreach ($addressList as $address) {
                $addressHtml .= "<option value='{$address['id']}'>{$address['name']}</option>";
            }
        }
        return $addressHtml;
    }

    public function ajaxCheckPwd()
    {
        $data = input();
        $pwd_serv = Db::name('users')->where('id',session('user.id')) ->value('password');
        if ($pwd_serv == encrypt_pwd($data['pass'])) {
            cookie('info_pwd_verify','yes',3600);
            $this->success('密码验证通过');
        }else{
            $this->error('密码验证失败，请重试。');
        }
    }

    /**
     * 用户收货地址设为默认
     */
    public function set_default(){
        //修改收货地址为默认
        $id = request()->param('id');
        intval($id);
        if($id > 0){
            // dump($id);
            Db::startTrans();
            try{
                Db::name('user_address')->where('user_id',session('user.id'))->update(['is_default'=>0]);
                Db::name('user_address')->where('id',$id)->where('user_id',session('user.id'))->update(['is_default'=>1]);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            // die;
            $this->success(lang('user_set_success'));
        }else{
            $this->error(lang('user_select_address_failed'));
        }
    }

    /**
     * 删除用户收货地址
     */
    public function delete(){
        //修改收货地址为默认
        $id = request()->param('id');
        intval($id);
        if($id > 0){
            $del = Db::name('user_address')->where('id',$id)->where('user_id',session('user.id'))->update(['status'=>0]);
            if( $del === false ){
                $this->error(lang('user_del_address_failed'));
            }
            $this->success(lang('user_del_address_success'));
        }else{
            $this->error(lang('user_select_address_failed'));
        }
    }

    /**
     * 批量删除选中的用户收货地址
     */
    public function deletes(){
        //修改收货地址为默认
        $data = input('');
        // dump($data['id']);die;
        $id = $data['id'];
        if(count($id) > 0){
            $del = Db::name('user_address')->where('id','IN',$id)->where('user_id',session('user.id'))->delete();
            if( $del === false ){
                $this->error(lang('user_del_address_failed'));
            }
            $this->success(lang('user_del_address_success'));
        }else{
            $this->error(lang('user_select_address_failed'));
        }
    }

    function _empty()
    {
        $this->redirect('index');
    }

}