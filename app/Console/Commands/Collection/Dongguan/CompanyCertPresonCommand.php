<?php

namespace App\Console\Commands\Collection\Dongguan;

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
    protected $signature = 'company_cert_preson_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业资质和人才信息，每天凌晨00:05执行一次';

    protected $isinitial = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
            Log::info($cur_time.' —— CompanyCertPresonCommand采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        if ( $this->isinitial ){
            $ent_ids = DB::table('get_dg_jy_company_info')->select('remote_id')->where('no_import',0)->get();
        }else{
            $ent_ids = DB::table('get_child_boolean as get')
                ->leftJoin('get_dg_jy_company_info as ent','get.remote_id','ent.remote_id')
                ->select('ent.remote_id')
                ->where(['get.remote_id_type'=>1,'get.isget'=>1,'ent.no_import'=>0])
                ->get();
        }
        if ( !$ent_ids ){
            return true;
        }
        foreach ($ent_ids as $ent_id ){
            dispatch(new GetCompanyCertPresonLists($ent_id->remote_id));
        }
        return true;
    }
}
