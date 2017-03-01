<?php

namespace App\Jobs\Dongguan;

use App\Service\CertService;
use App\Service\DGJY\CompanyCertService;
use App\Service\DGJY\CompanyInfoService;
use App\Service\DGJY\PersonService;
use App\Service\DGJY\PublicFun;
use App\Service\JobStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Support\Facades\Cache;
use QL\QueryList;
use Log;

class GetCompanyCertPersonLists implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $ent_id;
    private $fun;
    private $companyInfoService;
    private $companyCertService;
    private $personService;
    private $certService;
    private $jobStatusService;

    /**
     * 采集企业资质和人才列表的队列
     *
     */
    public function __construct($ent_id)
    {
        $this->ent_id = $ent_id;

        $fun = new PublicFun();
        $companyInfoService = new CompanyInfoService();
        $companyCertService = new CompanyCertService();
        $personService = new PersonService();
        $certService = new CertService();
        $this->fun = $fun;
        $this->companyInfoService = $companyInfoService;
        $this->companyCertService = $companyCertService;
        $this->personService = $personService;
        $this->certService = $certService;

        $jobStatusService = new JobStatusService();
        $this->jobStatusService = $jobStatusService;
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        $cur_time = date('y-m-d H:i:s',time());
        if (!Cache::has('get_dg_main_info')) {
            $title = '采集企业资质列表和人才列表时,获取不到缓存get_dg_main_info内容';
            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
            return;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_info->url;

        //查询是否存在该企业
        $where = ['id'=>$this->ent_id,'no_import'=>0];
        $ent = $this->companyInfoService->getByWhere('company',$where,['remote_id']);

        if ( $ent === false ){
            $title = '采集企业资质列表和人才列表,查询get_dgjy_company_info表'.$this->ent_id.'时出错了';
            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
            return;
        }

        if ( !$ent ){
            $title = '采集企业资质列表和人才列表,查询get_dgjy_company_info表'.$this->ent_id.'为空,不需要采集';
            $this->jobStatusService->jobNull('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
            return;
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

        //采集企业资质列表
        if ( count($cert_arr[0]) ){
            $cert_arr = str_replace('qualificationList:ko.observableArray(','',$cert_arr[0][0]);
            $cert_arr = str_replace(')','',$cert_arr);
            $cert_arr = json_decode($cert_arr,true);

            foreach ($cert_arr as $n=>$certs){
                $certFile_id = $this->fun->ValidData($certs['id']);
                $has = $this->companyCertService->getOneByWhere('certFile',['remote_certfile_id'=>$certFile_id]);
                $cert_list = [];
                //远程企业ID
                $cert_list['remote_ent_id'] = $ent->remote_id;
                //远程证书ID
                $cert_list['remote_certfile_id'] = $certFile_id;
                //证书号
                $cert_list['cert_file_code'] = $this->fun->ValidData($certs['fcCertfilecode']);
                //颁发机构
                $agency = $this->fun->ValidData($certs['fcCertfileauditorgan']);
                $cert_list['agency'] = $agency;
                //颁发机构ID
                $agency_id = $this->certService->getAgencyId($agency);
                $cert_list['agency_id'] = $agency_id;
                //有效期
                $cert_list['fdCertstartdate'] = $this->fun->DateFormat($certs['fdCertstartdate']);
                $cert_list['fdCertenddate'] = $this->fun->DateFormat($certs['fdCertenddate']);

                //更新数据或插入新的数据
                if ( $has ) {
                    $updateInfo = $this->fun->HandleInfo($cert_list,(array)$has);
                    //更新数据
                    if ( count($updateInfo) ){
                        $resultUpdate = $this->companyCertService->UpdateByWhere('certFile',['id'=>$has->id],$updateInfo);
                        //更新是否采集标记
                        $resultCollect = $this->companyInfoService->markCollect($has->id,$certFile_id,2,1);
                        if ( $resultUpdate === false ){
                            $updateInfo['title'] = '采集证书列表时，更新企业证书列表信息出错了，远程证书ID为：'.$certFile_id;
                            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$updateInfo);
                            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$updateInfo['title']);
                            continue;
                        }
                        if ( $resultCollect === false ){
                            $title = '采集证书列表时，标记是否更新采集资质出错了，远程证书ID为：'.$certFile_id;
                            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
                            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                            continue;
                        }
                        $updateInfo['title'] = '采集证书列表时，更新企业证书列表信息，远程证书ID为: '.$certFile_id.',更新企业证书列表成功了';
                        $this->jobStatusService->jobSuccess('GetCompanyCertPersonLists',$updateInfo);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$updateInfo['title']);
                        continue;
                    }else{
                        $title = '采集证书列表时，更新企业证书列表信息，远程企业ID为: '.$ent->remote_id.',不需要更新';
                        $this->jobStatusService->jobNull('GetCompanyCertPersonLists',$title);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                        continue;
                    }
                }else{
                    //插入数据
                    $get_id = $this->companyCertService->insertInfo('certFile',$cert_list);
                    //更新是否采集标记
                    $resultCollect = $this->companyInfoService->markCollect($get_id,$certFile_id,2,1);
                    if ( $get_id === false ){
                        $cert_list['title'] = '采集证书列表时，插入企业证书列表信息出错了，远程证书ID为：'.$certFile_id;
                        $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$cert_list);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$cert_list['title']);
                        continue;
                    }
                    if ( $resultCollect === false ){
                        $title = '采集证书列表时，标记是否更新采集资质出错了，远程证书ID为：'.$certFile_id;
                        $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                        continue;
                    }
                    $cert_list['title'] = '采集证书列表时，插入企业证书列表信息成功了，远程证书ID为：'.$certFile_id;
                    $this->jobStatusService->jobSuccess('GetCompanyCertPersonLists',$cert_list);
                    $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$cert_list['title']);
                    continue;
                }
            }

        }else{
            $title = '采集证书列表时,远程企业没证书,不需要采集,远程企业ID:'.$ent->remote_id;
            $this->jobStatusService->jobNull('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
        }

        //采集企业人才列表
        if ( count($person_arr[0]) ){
            $person_arr = str_replace('entpersonInfolist:ko.observableArray(','',$person_arr[0][0]);
            $person_arr = str_replace(')','',$person_arr);
            $person_arr = json_decode($person_arr,true);
            foreach ($person_arr as $n=>$persons){
                $person_id = $this->fun->ValidData($persons['id']);
                $has = $this->personService->getOneByWhere('person',['remote_person_id'=>$person_id]);
                $person_list = [];
                //姓名
                $person_list['name'] = $this->fun->ValidData($persons['fcPersonname']);
                //性别
                $person_list['sex'] = (int)$this->fun->ValidData($persons['fcSex']);
                //人才ID
                $person_list['remote_person_id'] = $person_id;
                //企业ID
                $person_list['remote_ent_id'] = $ent->remote_id;
                //企业执照号码
                $person_list['fcEntcode'] = $this->fun->ValidData($persons['fcEntcode']);
                //身份证号码
                $person_list['fcPersonsn'] = $this->fun->ValidData($persons['fcPersonsn']);
                //专业
                $person_list['fcSchoolingspecialty'] = $this->fun->ValidData($persons['fcSchoolingspecialty']);
                //职称
                $person_list['fcTechnicaltitle'] = $this->fun->ValidData($persons['fcTechnicaltitle']);

                //更新数据或插入新的数据
                if ( $has ) {
                    $updateInfo = $this->fun->HandleInfo($person_list,(array)$has);
                    //更新数据
                    if ( count($updateInfo) ){
                        $resultUpdate = $this->personService->UpdateByWhere('person',['id'=>$has->id],$updateInfo);
                        //更新是否采集标记
                        $resultCollect = $this->companyInfoService->markCollect($has->id,$person_id,3,1);
                        if ( $resultUpdate === false ){
                            $updateInfo['title'] = '采集人才列表时，更新企业人才列表信息出错了，远程人才ID为：'.$person_id;
                            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$updateInfo);
                            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$updateInfo['title']);
                            continue;
                        }
                        if ( $resultCollect === false ){
                            $title = '采集人才列表时，标记是否更新采集人才出错了，远程人才ID为：'.$person_id;
                            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
                            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                            continue;
                        }
                        $updateInfo['title'] = '采集人才列表时，更新企业人才列表信息，远程人才ID为: '.$person_id.',更新企业人才列表成功了';
                        $this->jobStatusService->jobSuccess('GetCompanyCertPersonLists',$updateInfo);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$updateInfo['title']);
                        continue;
                    }else{
                        $title = '采集人才列表时，更新企业人才列表信息，远程企业ID为: '.$ent->remote_id.',不需要更新';
                        $this->jobStatusService->jobNull('GetCompanyCertPersonLists',$title);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                        continue;
                    }
                }else{
                    //插入数据
                    $get_id = $this->personService->insertInfo('person',$person_list);
                    //更新是否采集标记
                    $resultCollect = $this->companyInfoService->markCollect($get_id,$person_id,3,1);
                    if ( $get_id === false ){
                        $person_list['title'] = '采集人才列表时，插入企业人才列表信息出错了，远程人才ID为：'.$person_id;
                        $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$person_list);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$person_list['title']);
                        continue;
                    }
                    if ( $resultCollect === false ){
                        $title = '采集人才列表时，标记是否更新采集人才出错了，远程人才ID为：'.$person_id;
                        $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
                        $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
                        continue;
                    }
                    $person_list['title'] = '采集人才列表时，插入企业人才列表信息成功了，远程人才ID为：'.$person_id;
                    $this->jobStatusService->jobSuccess('GetCompanyCertPersonLists',$person_list);
                    $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$person_list['title']);
                    continue;
                }

            }
        }else{
            $title = '采集人才列表时，更新企业人才列表信息，远程企业ID为: '.$ent->remote_id.',不需要更新';
            $this->jobStatusService->jobNull('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
        }

        //采集完成,标记企业不需要再采集;
        $resultCollect = $this->companyInfoService->markCollect($this->ent_id,$ent->remote_id,1,0);
        if ( $resultCollect === false ){
            $title = '标记企业不需要再采集，远程企业ID为: '.$ent->remote_id;
            $this->jobStatusService->jobFail('GetCompanyCertPersonLists',$title);
            $this->jobStatusService->log('Job/GetCompanyCertPersonLists',$cur_time.' —— '.$title);
        }
        return;
    }

}
