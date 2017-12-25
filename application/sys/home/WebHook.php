<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/11/10
 * Time: 下午5:43
 */

namespace app\sys\home;


use think\Controller;
use think\Log;

class WebHook extends Controller
{

    /**
     * autoDeploy密码
     * @var string
     */
    private static $autoDeployPass = 'Jungo_DistributionB2C_!@#';


    /**
     * autoDeploy日志配置
     * @var array
     */
    private static $autoDeployLogConfig = [
        'log_path' => DATA_PATH.'runtime/log/auto_deploy',
    ];



    /**
     * Git自动部署
     * @return mixed
     */
    public function autodeploy()
    {
        if( request()->isPost() ){
            $data = request()->post();
            $this->autoDeployLog($data);

//            $data = json_decode($data,1);
            if( $data['password'] == self::$autoDeployPass ){
                exec('sh ../data/shell/gitAutoDeploy.sh',$info,$status);
                $this->autoDeployLog($info);
            }else{
                $this->redirect(request()->domain());
            }
        }else{
            $this->redirect(request()->domain());
        }
    }



    /**
     * 保存autoDeploy日志
     * @param $data
     * @return mixed
     */
    private function autoDeployLog($data)
    {
        if( !is_dir(self::$autoDeployLogConfig['log_path']) ){
            mkdir(self::$autoDeployLogConfig['log_path']);
            chmod(self::$autoDeployLogConfig['log_path'],0777);
        }
        $data = is_array($data) ? json_encode($data) : $data;
        $fileName = date('ymdHis_').rand(100,999).'.log.json';
        $filePath = self::$autoDeployLogConfig['log_path'].'/'.$fileName;
        file_put_contents($filePath,$data);
        chmod($filePath,0777);
    }

}