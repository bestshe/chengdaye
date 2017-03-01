<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Console\Commands\BaseCommand;
use App\Jobs\Dongguan\GetCompanyCert;
use App\Service\DGJY\CompanyInfoService;
use App\Service\JobStatusService;


class CompanyCertCommand extends BaseCommand
{
    /**
     * 任务调度的命令名称.
     * 采集企业资质推送给job执行
     *
     * @var string
     */
    protected $signature = 'company_cert_command';

    /**
     * 说明.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业资质推送给job执行，每天凌晨02:25执行一次';

    private $companyInfoService;
    private $jobStatusService;


    public function __construct(CompanyInfoService $companyInfoService,JobStatusService $jobStatusService)
    {
        parent::__construct();
        $this->companyInfoService = $companyInfoService;
        $this->jobStatusService = $jobStatusService;
    }

    /**
     * 执行命令.
     *
     * @return mixed
     */
    public function handle()
    {
        $cur_time = date('y-m-d H:i:s',time());
        $where = ['remote_id_type'=>2,'isget'=>1];
        $cert_ids = $this->companyInfoService->getByWhere('collect',$where,['get_id as id'],0);
        if ( $cert_ids === false ){
            $this->jobStatusService->jobFail($this->signature,'查询get_dgjy_collect表要更新的企业资质出错了');
            $this->info($cur_time.' —— CompanyCertCommand —— 查询get_dgjy_collect表要更新的企业资质出错了');
            return true;
        }
        if ( !count($cert_ids) ){
            $this->jobStatusService->jobNull($this->signature,'没有要更新的企业资质');
            $this->info($cur_time.' —— CompanyCertCommand —— 没有要更新的企业资质');
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($cert_ids as $cert_id ){
            dispatch(new GetCompanyCert($cert_id->id));
            $title = '采集企业资质列表get_dgjy_company_certfile'.$cert_id->id.'加入队列成功';
            $this->jobStatusService->jobSuccess($this->signature,$title);
            $this->info($cur_time.' —— CompanyCertCommand —— '.$title);
        }
        return true;
    }
}
