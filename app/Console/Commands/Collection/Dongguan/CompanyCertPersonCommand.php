<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Service\DGJY\CompanyInfoService;
use Illuminate\Console\Command;
use App\Jobs\Dongguan\GetCompanyCertPersonLists;

class CompanyCertPersonCommand extends Command
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

    public function __construct(CompanyInfoService $companyInfoService)
    {
        parent::__construct();
        $this->initial = env('COLLECT_INITIAL',true);
        $this->companyInfoService = $companyInfoService;
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
        if ( !count($ent_ids) ){
            $this->info($cur_time.' —— CompanyCertPersonCommand —— 没有要更新的企业');
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($ent_ids as $ent_id ){
            dispatch(new GetCompanyCertPersonLists($ent_id->id));
            $this->info($cur_time.' —— CompanyCertPersonCommand —— 采集加入队列 '.$ent_id->id.' 成功');
        }
        return true;
    }
}
