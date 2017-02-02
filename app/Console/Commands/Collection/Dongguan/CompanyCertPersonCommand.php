<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Service\DGJY\CompanyInfoService;
use Illuminate\Console\Command;
use App\Jobs\Dongguan\GetCompanyCertPresonLists;
use Cache,DB,Log;

class CompanyCertPresonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company_cert_person_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业资质和人才信息，每天凌晨00:05执行一次';

    protected $initial;

    private $companyInfoService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CompanyInfoService $companyInfoService)
    {
        parent::__construct();
        $this->initial = env('COLLECT_INITIAL',true);
        $this->companyInfoService = $companyInfoService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $cur_time = date('y-m-d H:i:s',time());
        if (!Cache::has('get_dg_main_info')) {
            $this->info($cur_time.' —— CompanyCertPresonCommand采集出错了,代码01');
            return true;
        }
        //$get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        if ( $this->initial ){
            $ent_ids = $this->companyInfoService->getByWhere('company',['no_import'=>0],['id'],0);
        }else{
            $Rtable = 'get_dgjy_company_info as ent';
            $Rwhere = ['get.getid'=>'ent.id'];
            $where = ['get.remote_id_type'=>1,'get.isget'=>1,'ent.no_import'=>0];
            $fields = ['ent.id'];
            $ent_ids = $this->companyInfoService->collectEnt($Rtable,$Rwhere,$where,$fields);
        }
        if ( !$ent_ids ){
            return true;
        }
        foreach ($ent_ids as $ent_id ){
            dispatch(new GetCompanyCertPresonLists($ent_id->id));
            $this->info($cur_time.' —— CompanyCertPresonCommand采集加入队列 '.$ent_id->id.' 成功');
        }
        return true;
    }
}
