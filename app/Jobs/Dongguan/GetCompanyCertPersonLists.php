<?php

namespace App\Jobs\Dongguan;

use App\Service\DGJY\CompanyInfoService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,DB,Log;

class GetCompanyCertPersonLists implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $ent_id;
    private $companyInfoService;

    /**
     * 采集企业资质和人才列表的队列
     *
     */
    public function __construct($ent_id,CompanyInfoService $companyInfoService)
    {
        //
        $this->ent_id = $ent_id;
        $this->companyInfoService = $companyInfoService;
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        $cur_time = date('y-m-d H:i:s',time());
        if (!Cache::has('get_dg_main_info')) {
            Log::info($cur_time.' —— GetCompanyCertPersonLists采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_info->url;

        //查询是否存在该企业
        $where = ['id'=>$this->ent_id,'no_import'=>0];
        $ent = $this->companyInfoService->getByWhere('company',$where,['remote_id']);

        if ( !$ent ){
            Log::info($cur_time.' —— GetCompanyCertPersonLists ——　'.$this->ent_id.'采集出错了,代码02');
            return true;
        }
        //采集开始
        $url = $url.$ent->remote_id;
        $rules = array(
            'js_content' => array("script:eq(13)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match_all("/qualificationList:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $cert_arr);
        preg_match_all("/entpersonInfolist:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $person_arr);
        if ( count($cert_arr[0]) ){
            $cert_arr = str_replace('qualificationList:ko.observableArray(','',$cert_arr[0][0]);
            $cert_arr = str_replace(')','',$cert_arr);
            $cert_arr = json_decode($cert_arr,true);
            foreach ($cert_arr as $n=>$certs){

            }
        }else{
            Log::info($cur_time.' —— GetCompanyCertPersonLists —— '.$ent->remote_id.'企业没有资质.');
        }
        if ( count($person_arr[0]) ){
            $person_arr = str_replace('entpersonInfolist:ko.observableArray(','',$person_arr[0][0]);
            $person_arr = str_replace(')','',$person_arr);
            $person_arr = json_decode($person_arr,true);
            foreach ($person_arr as $n=>$person){

            }
        }else{
            Log::info($cur_time.' —— GetCompanyCertPersonLists —— '.$ent->remote_id.'企业没有人才.');
        }
        return true;
    }

}
