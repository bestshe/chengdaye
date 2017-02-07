<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Jobs\Dongguan\GetCompanyPerson;
use App\Service\DGJY\CompanyInfoService;
use Illuminate\Console\Command;


class CompanyPersonCommand extends Command
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


    public function __construct(CompanyInfoService $companyInfoService)
    {
        parent::__construct();
        $this->companyInfoService = $companyInfoService;
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
        if ( !count($person_ids) ){
            $this->info($cur_time.' —— CompanyCertCommand —— 没有要更新的企业资质');
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($person_ids as $person_id ){
            dispatch(new GetCompanyPerson($person_id->id));
            $this->info($cur_time.' —— CompanyCertCommand —— 采集加入队列 '.$person_id->id.' 成功');
        }
        return true;
    }
}
