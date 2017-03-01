<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Console\Commands\BaseCommand;
use App\Service\DGJY\CompanyInfoService;
use App\Jobs\Dongguan\GetCompanyCertPersonLists;
use App\Service\JobStatusService;

class CompanyCertPersonCommand extends BaseCommand
{
    /**
     * 任务调度的命令名称.
     * 采集企业资质列表和人才列表命令
     *
     * @var string
     */
    protected $signature = 'company_cert_person_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业资质和人才信息，每天凌晨00:20执行一次';

    protected $initial;
    private $companyInfoService;
    private $jobStatusService;

    public function __construct(CompanyInfoService $companyInfoService,JobStatusService $jobStatusService)
    {
        parent::__construct();
        $this->initial = env('COLLECT_INITIAL',true);
        $this->companyInfoService = $companyInfoService;
        $this->jobStatusService = $jobStatusService;
    }

    /**
     * 执行命令
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $cur_time = date('y-m-d H:i:s',time());
        if ( $this->initial ){
            $ent_ids = $this->companyInfoService->getByWhere('company',['no_import'=>0],['id'],0);
        }else{
            $where = ['remote_id_type'=>1,'isget'=>1];
            $ent_ids = $this->companyInfoService->getByWhere('collect',$where,['get_id as id'],0);
        }
        if ( $ent_ids === false ){
            $title = '在查询企业是否要更新资质列表和人才列表时出错了';
            $this->jobStatusService->jobFail($this->signature,$title);
            $this->info($cur_time.' —— '.$title);
            return true;
        }

        if ( !count($ent_ids) ){
            $title = '在查询不存在需要更新的企业资质列表和人才列表.';
            $this->jobStatusService->jobNull($this->signature,$title);
            $this->info($cur_time.' —— '.$title);
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($ent_ids as $ent_id ){
            dispatch(new GetCompanyCertPersonLists($ent_id->id));
            $title = '采集企业资质列表和人才列表,get_dgjy_company_info表'.$ent_id->id.',加入队列成功';
            $this->jobStatusService->jobSuccess($this->signature,$title);
            $this->info($cur_time.' —— '.$title);
        }
        return true;
    }
}
