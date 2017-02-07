<?php

namespace App\Jobs\Dongguan;

use App\Service\DGJY\CompanyInfoService;
use App\Service\DGJY\PersonService;
use App\Service\DGJY\PublicFun;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,Log;

class GetCompanyPerson implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $person_id;

    private $fun;
    private $personService;
    private $companyInfoService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($person_id,PublicFun $fun,PersonService $personService,CompanyInfoService $companyInfoService)
    {
        $this->person_id = $person_id;
        $this->fun = $fun;
        $this->personService = $personService;
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
            Log::info($cur_time.' —— GetCompanyPerson —— 采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_person->url;

        //查询是否存在该资质
        $person = $this->personService->getOneByWhere('person',['id'=>$this->person_id],['remote_person_id','remote_ent_id']);

        if ( !$person ){
            Log::info($cur_time.' —— GetCompanyPerson ——　'.$this->person_id.' 不需要采集');
            return true;
        }

        //采集开始
        $url = $url.$person->remote_person_id;
        $rules = array(
            'cert_type' => array('td:eq(0)','text'),
            'cert_code' => array('td:eq(1)','text'),
            'agency' => array('td:eq(2)','text'),
            'cert_name' => array('td:eq(3)','text'),
            'specialty' => array('td:eq(4)','text'),
            'cert_level' => array('td:eq(5)','text'),
            'validate' => array('td:eq(6)','text'),
        );
        $rang = 'tbody tr';

        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules,$rang)->data;
        if ( !count($data) ){
            Log::info($cur_time.' —— GetCompanyPerson —— '.$person->remote_person_id.'人才没有证件.');
            return false;
        }
        //合并同证多专业数据
        $data = $this->fun->MergeData($data);
        //分证入库
        foreach($data as $m=>$cert){
            preg_match_all('/(\d+)-(\d+)-(\d+)/i',$cert['validate'],$validate_arr);
            if ( empty($validate_arr[0]) ) {
                $validate_arr[0][0] = '9999-12-31';
            }
            $cert_type = $this->fun->ValidData($cert['cert_type']);//证件类型
            $cert_name = $this->fun->ValidData($cert['cert_name']);//证件名称
            $cert_code = $this->fun->ValidData($cert['cert_code']);//证件证号
            $cert_level = $this->fun->ValidData($cert['cert_level']);//证件等级
            $agency = $this->fun->ValidData($cert['agency']);//颁发机构
            $specialty = $cert['specialty'];//证件专业
            $cert_name = $this->fun->TurnEmpty($cert_type,$cert_level,$cert_name,$cert_code);
            $cert_id = $this->fun->LocalCertID($cert_type,$cert_level,$cert_name,$cert_code);

            $cert_list = [];
            //本地人才证件分类ID
            $cert_list['cert_id'] = $cert_id;
            //本地采集证书ID
            $cert_list['g_dgjy_p_id'] = $this->person_id;
            //远程企业ID
            $cert_list['remote_ent_id'] = $person->remote_ent_id;
            //远程人才ID
            $cert_list['remote_person_id'] = $person->remote_person_id;
            //远程证件名称
            $cert_list['cert_name'] = $cert_name;
            //远程证件编号
            $cert_list['cert_code'] = $cert_code;
            //颁发机构
            $cert_list['agency'] = $agency;
            //本地颁发机构ID
            $cert_list['agency_id'] = $this->fun->LocalAgencyID($agency);
            //远程专业名称
            $cert_list['specialty'] = $specialty;
            //远程专业名称ID
            $cert_list['specialty_id'] = $this->personService->LocalSpecialtyId($cert_id,$specialty);
            //有效期
            $cert_list['fdCertenddate'] = $this->fun->DateFormat($validate_arr[0][0]);

            $has = $this->personService->getOneByWhere('cert',['g_dgjy_p_id'=>$this->person_id]);

            //更新数据或插入新的数据
            if ( $has ) {
                $updateInfo = $this->fun->HandleInfo($cert_list,(array)$has);
                //更新数据
                if ( count($updateInfo) ){
                    $resultUpdate = $this->personService->UpdateByWhere('cert',['id'=>$has->id],$updateInfo);
                    //更新是否采集标记
                    $resultCollect = $this->companyInfoService->markCollect($this->person_id,$person->remote_person_id,3,0);
                    if ( $resultUpdate === false ){
                        Log::info($cur_time.' —— GetCompanyPerson —— 采集 —— '.$person->remote_person_id.' —— 出错了,代码02');
                    }
                    if ( $resultCollect === false ){
                        Log::info($cur_time.' —— GetCompanyPerson —— 采集 —— '.$person->remote_person_id.' —— 出错了,代码03');
                    }
                }
            }else{
                //插入数据
                $get_id = $this->personService->insertInfo('cert',$cert_list);
                //更新是否采集标记
                $resultCollect = $this->companyInfoService->markCollect($this->person_id,$person->remote_person_id,3,0);
                if ( $get_id === false ){
                    Log::info($cur_time.' —— GetCompanyPerson —— 采集 —— '.$person->remote_person_id.' —— 出错了,代码04');
                }
                if ( $resultCollect === false ){
                    Log::info($cur_time.' —— GetCompanyPerson —— 采集 —— '.$person->remote_person_id.' —— 出错了,代码05');
                }
                Log::info($cur_time.' —— GetCompanyPerson —— 采集 —— '.$person->remote_person_id.' —— 人才证件信息成功');
            }

        }
        return true;
    }
}
