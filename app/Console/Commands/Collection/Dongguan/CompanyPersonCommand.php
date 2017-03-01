<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Console\Commands\BaseCommand;
use App\Jobs\Dongguan\GetCompanyPerson;
use App\Service\DGJY\CompanyInfoService;
use App\Service\JobStatusService;


class CompanyPersonCommand extends BaseCommand
{
    /**
     * 任务调度的命令名称.
     * 采集企业人才证书推送给job执行
     *
     * @var string
     */
    protected $signature = 'company_person_command';

    /**
     * 说明.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业人才证书推送给job执行，每天凌晨02:25执行一次';

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
        $where = ['remote_id_type'=>3,'isget'=>1];
        $person_ids = $this->companyInfoService->getByWhere('collect',$where,['get_id as id'],0);
        if ( $person_ids === false ){
            $this->jobStatusService->jobFail($this->signature,'查询get_dgjy_collect表要更新的企业人才出错了');
            $this->info($cur_time.' —— CompanyPersonCommand —— 查询get_dgjy_collect表要更新的企业人才出错了');
            return true;
        }
        if ( !count($person_ids) ){
            $this->jobStatusService->jobNull($this->signature,'没有要更新的企业人才');
            $this->info($cur_time.' —— CompanyPersonCommand —— 没有要更新的企业人才');
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($person_ids as $person_id ){
            dispatch(new GetCompanyPerson($person_id->id));
            $title = '采集企业人才列表get_dgjy_person'.$person_id->id.'加入队列成功';
            $this->jobStatusService->jobSuccess($this->signature,$title);
            $this->info($cur_time.' —— CompanyPersonCommand —— '.$title);
        }
        return true;
    }
}
