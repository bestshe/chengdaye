<?php

namespace App\Jobs\Dongguan;

use App\Service\CertService;
use App\Service\DGJY\CompanyCertService;
use App\Service\DGJY\CompanyInfoService;
use App\Service\DGJY\PublicFun;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,Log;

class GetCompanyCert implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $certFile_id;

    private $fun;
    private $companyCertService;
    private $certService;
    private $companyInfoService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($certFile_id,PublicFun $fun,CompanyCertService $companyCertService,
                                CertService $certService,CompanyInfoService $companyInfoService)
    {
        $this->certFile_id = $certFile_id;
        $this->fun = $fun;
        $this->companyCertService = $companyCertService;
        $this->certService = $certService;
        $this->companyInfoService = $companyInfoService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cur_time = date('y-m-d H:i:s',time());
        if (!Cache::has('get_dg_main_info')) {
            Log::info($cur_time.' —— GetCompanyCert —— 采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_cert->url;

        //查询是否存在该资质
        $certFile = $this->companyCertService->getOneByWhere('certFile',['id'=>$this->certFile_id],['remote_certfile_id','remote_ent_id']);

        if ( !$certFile ){
            Log::info($cur_time.' —— GetCompanyCert ——　'.$this->certFile_id.' 不需要采集');
            return true;
        }

        //采集开始
        $url = $url.$certFile->remote_certfile_id;
        $rules = array(
            'js_content' => array("script:eq(14)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match_all("/detailList: ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $cert_arr);
        if ( !count($cert_arr[0]) ){
            Log::info($cur_time.' —— GetCompanyCert —— '.$certFile->remote_cert_id.'企业没有资质.');
            return false;
        }
        $data = str_replace('detailList: ko.observableArray(','',$data[0][0]);
        $data = str_replace(')','',$data);
        $data = json_decode($data,true);
        //分资质入库
        foreach($data as $m=>$cert){
            $cert_id = $this->fun->ValidData($cert['id']);
            $certName = $this->fun->ValidData($cert['fcEntcertcaption']);
            $certName = $this->fun->ReplaceCertName($certName);

            $cert_list = [];
            //本地资质ID
            $cert_list['bc_id'] = $this->certService->getCertId($certName);
            //本地采集证书ID
            $cert_list['g_dgjy_c_c_id'] = $this->certFile_id;
            //远程企业ID
            $cert_list['remote_ent_id'] = $certFile->remote_ent_id;
            //远程证书ID
            $cert_list['remote_certfile_id'] = $certFile->remote_certfile_id;
            //远程资质ID
            $cert_list['remote_cert_id'] = $cert_id;
            //资质编号,交易中心自动生成的
            $cert_list['cert_code'] = $this->fun->ValidData($cert['fcEntcertcode']);
            //资质名称
            $cert_list['fcEntcertcaption'] = $certName;
            //有效期
            $cert_list['fdCertstartdate'] = $this->fun->DateFormat($cert['fdCertstartdate']);
            $cert_list['fdCertenddate'] = $this->fun->DateFormat($cert['fdCertenddate']);

            $has = $this->companyCertService->getOneByWhere('cert',['remote_cert_id'=>$cert_id]);

            //更新数据或插入新的数据
            if ( $has ) {
                $updateInfo = $this->fun->HandleInfo($cert_list,(array)$has);
                //更新数据
                if ( count($updateInfo) ){
                    $resultUpdate = $this->companyCertService->UpdateByWhere('cert',['id'=>$has->id],$updateInfo);
                    //更新是否采集标记
                    $resultCollect = $this->companyInfoService->markCollect($this->certFile_id,$certFile->remote_certfile_id,2,0);
                    if ( $resultUpdate === false ){
                        Log::info($cur_time.' —— GetCompanyCert —— 采集 —— '.$certFile->remote_certfile_id.' —— 出错了,代码02');
                    }
                    if ( $resultCollect === false ){
                        Log::info($cur_time.' —— GetCompanyCert —— 采集 —— '.$certFile->remote_certfile_id.' —— 出错了,代码03');
                    }
                }
            }else{
                //插入数据
                $get_id = $this->companyCertService->insertInfo('cert',$cert_list);
                //更新是否采集标记
                $resultCollect = $this->companyInfoService->markCollect($this->certFile_id,$certFile->remote_certfile_id,2,0);
                if ( $get_id === false ){
                    Log::info($cur_time.' —— GetCompanyCert —— 采集 —— '.$certFile->remote_certfile_id.' —— 出错了,代码04');
                }
                if ( $resultCollect === false ){
                    Log::info($cur_time.' —— GetCompanyCert —— 采集 —— '.$certFile->remote_certfile_id.' —— 出错了,代码05');
                }
                Log::info($cur_time.' —— GetCompanyCert —— 采集 —— '.$cert_id.' —— 资质成功');
            }

        }
        return true;
    }
}
