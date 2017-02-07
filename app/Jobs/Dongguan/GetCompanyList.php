<?php

namespace App\Jobs\Dongguan;

use App\Service\DGJY\CompanyInfoService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\DGJY\PublicFun;
use Cache,Log;

class GetCompanyList implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $page;

    private $fun;
    private $companyInfoService;

    /**
     * 采集企业信息列表的队列
     *
     */
    public function __construct($page,PublicFun $fun,CompanyInfoService $companyInfoService)
    {
        //
        $this->page = $page;
        $this->fun = $fun;
        $this->companyInfoService = $companyInfoService;
    }

    /**
     * Execute the job.
     *
     */
    public function handle()
    {
        $cur_time = date('Y-m-d H:i:s',time());
        if (!Cache::has('get_dg_main_info')) {
            Log::info($cur_time.' —— GetCompanyList采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_pages->url;
        //将当前页的企业信息加入队列中
        $curl = $url.$this->page;
        $data = file_get_contents($curl);
        $data = json_decode($data);
        if ( !count($data->ls) ){
            Log::info($cur_time.' —— GetCompanyList采集出错了,代码02');
            return true;
        }
        foreach($data->ls as $key=>$ent){

            $remote_id = $ent->id;

            //查询是否存在;
            $has = $this->companyInfoService->getByWhere('companyinfo',['remote_id'=>$remote_id]);

            //转换企业性质
            $enttypeid = $this->fun->ValidData((int)$ent->fcEntpropertysn);
            $ent_type = $this->fun->GetEntType($enttypeid);

            //处理经办人联系电话
            $contact_tel = $this->fun->ValidData($ent->fcEntinfodeclarepersontel);//经办人联系电话
            if ( strlen($contact_tel) != 11  ){
                $contact_tel = null;
            }

            //拼接数组
            $info = [];
            $info['remote_id'] = $remote_id;
            $info['fcEntname'] = $this->fun->ValidData($ent->fcEntname);//公司名称
            $info['fcArtname'] = $this->fun->ValidData($ent->fcArtname);//法人
            $info['fcArtpapersn'] = $this->fun->ValidData($ent->fcArtpapersn);//法人身份证号码
            $info['fnEntstatus'] = (int)$this->fun->ValidData($ent->fnEntstatus);//企业营业执照状态
            $info['fcEntpropertysn'] = $ent_type;//企业性质
            $info['fdBuilddate'] = $this->fun->DateFormat($ent->fdBuilddate);//企业注册日期
            $info['fdVaildenddate'] = $this->fun->DateFormat($ent->fdVaildenddate);//企业注册失效日期
            $info['fcBelongareasn'] = (int)$this->fun->ValidData($ent->fcBelongareasn);//企业所在区域编码
            $info['fmEnrolfund'] = $this->fun->ValidData($ent->fmEnrolfund);//注册资金
            $info['fcBusinesslicenseno'] = $this->fun->ValidData($ent->fcBusinesslicenseno);//企业注册号
            $info['fcOrganizationcode'] = $this->fun->ValidData($ent->fcOrganizationcode);//企业三证合一号
            $info['fcEntlinkaddr'] = $this->fun->ValidData($ent->fcEntlinkaddr);//企业注册地址
            $info['fcEntlinktel'] = $this->fun->ValidData($ent->fcEntlinktel);//企业联系电话
            $info['fcSafelicencenumber'] = $this->fun->ValidData($ent->fcSafelicencenumber);//建筑企业安全生产许可号
            $info['fcSafelicencecert'] = $this->fun->ValidData($ent->fcSafelicencecert);//颁发建筑企业安全生产许可机构
            $info['fdSafelicencesdate'] = $this->fun->DateFormat($ent->fdSafelicencesdate);//安全生产许可开始日期
            $info['fdSafelicenceedate'] = $this->fun->DateFormat($ent->fdSafelicenceedate);//安全生产许可结束日期
            $info['fcEntinfodeclareperson'] = $this->fun->ValidData($ent->fcEntinfodeclareperson);//经办人姓名
            $info['fcEntinfodeclarepersontel'] = $contact_tel;//经办人手机号码
            $info['fnIsotherprovinces'] = (int)$this->fun->ValidData($ent->fnIsotherprovinces);//是否进粤企业
            $info['fcIntogdadress'] = $this->fun->ValidData($ent->fcIntogdadress);//进粤信息
            $info['fnIsswotherprovinces'] = (int)$this->fun->ValidData($ent->fnIsswotherprovinces);//是否在水利厅备案
            $info['fcSwintogdadress'] = $this->fun->ValidData($ent->fcSwintogdadress);//水利厅备案信息
            $info['fnIsjtotherprovinces'] = (int)$this->fun->ValidData($ent->fnIsjtotherprovinces);//是否在公路建设市场信用备案
            $info['fcJtintogdadress'] = $this->fun->ValidData($ent->fcJtintogdadress);//公路建设市场信用备案信息
            $info['no_import'] = 0;//是否导入库内,1不导入,0导入
            if ( empty($info['fcSafelicencenumber']) || empty($info['fdSafelicencesdate'])  || empty($info['fdSafelicenceedate']) ){
                $info['no_import'] = 1;
            }

            //更新数据或插入新的数据
            if ( $has ) {
                $updateInfo = $this->fun->HandleInfo($info,(array)$has);
                //更新数据
                if ( count($updateInfo) ){
                    $resultUpdate = $this->companyInfoService->UpdateByWhere('companyinfo',['id'=>$has->id],$updateInfo);
                    //更新是否采集标记
                    $resultCollect = $this->companyInfoService->markCollect($has->id,$remote_id,1,1);
                    if ( $resultUpdate === false ){
                        Log::info($cur_time.' —— GetCompanyList采集出错了,代码03');
                    }
                    if ( $resultCollect === false ){
                        Log::info($cur_time.' —— GetCompanyList采集出错了,代码04');
                    }
                }
            }else{
                //插入数据
                $get_id = $this->companyInfoService->insertInfo('companyinfo',$info);
                //更新是否采集标记
                $resultCollect = $this->companyInfoService->markCollect($get_id,$remote_id,1,1);
                if ( $get_id === false ){
                    Log::info($cur_time.' —— GetCompanyList —— 采集出错了,代码05');
                }
                if ( $resultCollect === false ){
                    Log::info($cur_time.' —— GetCompanyList —— 采集出错了,代码06');
                }
                Log::info($cur_time.' —— GetCompanyList —— 采集'.$fcEntname.'---企业信息成功');
            }
        }
    }

}
