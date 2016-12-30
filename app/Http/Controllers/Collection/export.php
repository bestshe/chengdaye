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
        return $this->text();
    }

    private function text(){
        return 'nima';
    }

}
