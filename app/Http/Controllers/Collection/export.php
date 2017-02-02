<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use QL\QueryList;

class export extends Controller
{
    //
    public function entlist(Request $request)
    {
        /*$certlists = DB::table('get_dg_jy_company_cert')
            ->select('id')
            ->where('bc_id',119)
            ->get();*/
        $input = $request->only(['id']);
        $cid = (int)$input['id'];
        $entlists = DB::table('get_dg_jy_company_cert')
            ->leftJoin('get_dg_jy_company_info', 'get_dg_jy_company_cert.remote_ent_id', '=', 'get_dg_jy_company_info.remote_id')
            ->select('get_dg_jy_company_info.fcEntname')
            ->whereIn('get_dg_jy_company_cert.bc_id',[$cid])
            ->get();
        foreach ($entlists as $n=>$list) {
            echo $list->fcEntname.'<br>';
        }
        /*echo '===============================分隔线===================================<br>';
        foreach ($certlists as $m=>$clist) {
            echo $m.'======='.$clist->id.'<br>';
        }*/
    }

    /**
     * @return string
     */
    public function test()
    {
        //开始计算时间
        $stime = microtime(true);
        /*$arr = DB::table('get_child_boolean as get')
            ->leftJoin('get_dg_jy_company_info as ent','get.remote_id','ent.remote_id')
            ->select('ent.remote_id')
            ->where(['get.remote_id_type'=>1,'get.isget'=>0,'ent.no_import'=>0])
            ->get();
        $arr = DB::table('get_dg_jy_company_info')->select('remote_id')->where('no_import',0)->get();
        $arr = DB::table('get_dg_jy_company_info')
            ->select('remote_id')
            ->where([
                ['remote_id','2c9296bf588c5f4d0158903e14410f3c'],
                ['no_import',0]
            ])
            ->get();
        $etime = microtime(true);
        $gtotal = $etime-$stime;
        echo $gtotal.'<br>';
        foreach ($arr as $k=>$ent_id ){
            echo $k.'----'.$ent_id->remote_id.'<br>';
        }*/
        //return $arr;

        //采集开始
        /*$url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/view?id=2c9296bf588c5f4d0158903e14410f3c';
        $rules = array(
            'js_content' => array("script:eq(13)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match_all("/qualificationList:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $cert_arr);
        preg_match_all("/entpersonInfolist:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $person_arr);
        if ( !count($cert_arr[0]) or !count($person_arr[0]) ){
            //Log::info($cur_time.' —— GetCompanyCertPresonLists采集出错了,代码03');
            return true;
        }

        $cert_arr = str_replace('qualificationList:ko.observableArray(','',$cert_arr[0][0]);
        $cert_arr = str_replace(')','',$cert_arr);
        $cert_arr = json_decode($cert_arr,true);

        $person_arr = str_replace('entpersonInfolist:ko.observableArray(','',$person_arr[0][0]);
        $person_arr = str_replace(')','',$person_arr);
        $person_arr = json_decode($person_arr,true);*/
        //echo $cert_arr;

        $info = [];
        $info['remote_id'] = '2c9296be5896646a0158998c5aa60822';
        $info['fcEntname'] = '浙江省泰顺县交通工程有限公司';//公司名称
        $info['fcArtname'] = '薛永飞';//法人
        $info['fcArtpapersn'] = 4;//法人身份证号码
        $info['fnEntstatus'] = 5;//企业营业执照状态
        $info['fcEntpropertysn'] = 6;//企业性质
        $info['fdBuilddate'] = 7;//企业注册日期
        $info['fdVaildenddate'] = 8;//企业注册失效日期
        $info['fcBelongareasn'] = 9;//企业所在区域编码
        $info['fmEnrolfund'] = 10;//注册资金
        $info['fcBusinesslicenseno'] = 11;//企业注册号
        $info['fcOrganizationcode'] = 12;//企业三证合一号
        $info['fcEntlinkaddr'] = 13;//企业注册地址
        $info['fcEntlinktel'] = 14;//企业联系电话
        $info['fcSafelicencenumber'] = 15;//建筑企业安全生产许可号
        $info['fcSafelicencecert'] = 16;//颁发建筑企业安全生产许可机构
        $info['fdSafelicencesdate'] = 17;//安全生产许可开始日期
        $info['fdSafelicenceedate'] = 18;//安全生产许可结束日期
        $info['fcEntinfodeclareperson'] = 19;//经办人姓名
        $info['fcEntinfodeclarepersontel'] = 20;//经办人手机号码
        $info['fnIsotherprovinces'] = 21;//是否进粤企业
        $info['fcIntogdadress'] = 22;//进粤信息
        $info['fnIsswotherprovinces'] = 23;//是否在水利厅备案
        $info['fcSwintogdadress'] = 24;//水利厅备案信息
        $info['fnIsjtotherprovinces'] = 25;//是否在公路建设市场信用备案
        $info['fcJtintogdadress'] = 26;//公路建设市场信用备案信息

        $arr = DB::table('get_dgjy_company_info')->where('remote_id','2c9296be5896646a0158998c5aa60822')->first();

        return $this->HandleInfo($info,(array)$arr);


    }

    public function HandleInfo($remote,$local)
    {
        if ( !count($remote) || !count($local) ){
            return false;
        }
        $result = [];
        foreach ( $remote as $key=>$value ){
            if ( $value != $local[$key] ){
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function text(){
        return 'nima';
    }

}
