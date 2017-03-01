<?php
/**
 * Created by PhpStorm.
 * User: Chayton
 * Date: 2017/2/16
 * Time: 15:46
 */
namespace App\Service;

use App\Model\JobStatus;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class JobStatusService{

    private $jobStatus_model;

    public function __construct(){
        $jobStats = new JobStatus();
        $this->jobStatus_model = $jobStats;
    }

    /*
     * 生成日志
     * */
    public function log($name,$message){
        $file_name = storage_path($name.'/'.date('Ymd').'.log');
        $stream = new StreamHandler($file_name);
        $logger = new Logger($name,array($stream));
        $logger->debug(json_encode($message, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Job成功记录
     * @author Chayton
     *
     * @param $jobName
     * @param string $data
     */
    public function jobSuccess($jobName,$data=''){
        $data = is_array($data) ? json_encode($data) : $data;
        $this->jobStatus_model->saveStatus($jobName,0,$data);
    }

    /**
     * Job失败记录
     * @author Chayton
     *
     * @param $jobName
     * @param string $data
     */
    public function jobFail($jobName,$data=''){
        $data = is_array($data) ? json_encode($data) : $data;
        $this->jobStatus_model->saveStatus($jobName,1,$data);
    }

    /**
     * Job无记录
     * @author Chayton
     *
     * @param $jobName
     * @param string $data
     */
    public function jobNull($jobName,$data=''){
        $data = is_array($data) ? json_encode($data) : $data;
        $this->jobStatus_model->saveStatus($jobName,2,$data);
    }
}