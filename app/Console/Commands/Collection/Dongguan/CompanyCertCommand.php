<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Jobs\Dongguan\GetCompanyCert;
use App\Service\DGJY\CompanyInfoService;
use Illuminate\Console\Command;


class CompanyCertCommand extends Command
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
        $where = ['remote_id_type'=>2,'isget'=>1];
        $cert_ids = $this->companyInfoService->getByWhere('collect',$where,['get_id as id'],0);
        if ( !count($cert_ids) ){
            $this->info($cur_time.' —— CompanyCertCommand —— 没有要更新的企业资质');
            return true;
        }
        //推送到Job队列执行每个采集
        foreach ($cert_ids as $cert_id ){
            dispatch(new GetCompanyCert($cert_id->id));
            $this->info($cur_time.' —— CompanyCertCommand —— 采集加入队列 '.$cert_id->id.' 成功');
        }
        return true;
    }
}
