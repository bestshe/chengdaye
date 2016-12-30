<?php

namespace App\Jobs\Dongguan;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,DB,Log;

class GetCompanyCertPresonLists implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $ent_remote_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->ent_remote_id = $ent_remote_id;
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
            Log::info($cur_time.' —— GetCompanyCertPresonLists采集出错了,代码01');
            return true;
        }
        $get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
        $url = $get_dg_main_info->company_info->url;
        $ent = DB::table('get_dg_jy_company_info')
            ->select('remote_id')
            ->where([
                ['remote_id',$this->ent_remote_id],
                ['no_import',0]
            ])
            ->first();
        if ( !$ent ){
            Log::info($cur_time.' —— GetCompanyCertPresonLists ——　'.$this->ent_remote_id.'采集出错了,代码02');
            return true;
        }
        //采集开始
        $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/view?id='.$this->ent_remote_id;
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
        }else{
            Log::info($cur_time.' —— GetCompanyCertPresonLists —— '.$this->ent_remote_id.'企业没有资质.');
        }
        if ( count($person_arr[0]) ){
            $person_arr = str_replace('entpersonInfolist:ko.observableArray(','',$person_arr[0][0]);
            $person_arr = str_replace(')','',$person_arr);
            $person_arr = json_decode($person_arr,true);
        }else{
            Log::info($cur_time.' —— GetCompanyCertPresonLists —— '.$this->ent_remote_id.'企业没有人才.');
        }
        return true;
    }

    //日期格式化
    public function date_format($date){
        if ( $date ){
            return date($date);
        }
        return null;
    }

    //判断是否为空
    public function validata($string){
        $string = trim($string);
        if ( $string == '' or $string == '无' or $string == '/'){
            return null;
        }
        return $string;
    }

    //对比本地和运程数据是否一致;
    public function validataLR($arr,$field,$local_data,$remote_data){
        if ( $local_data != $remote_data ){
            return array_add($arr, $field, $remote_data);
        }
        return $arr;
    }

    /**
     * 标记采集企业是否更新或新的企业信息，下一级采集标记符
     * @return null|string
     */
    public function mark_child($get_id,$remote_id,$remote_id_type)
    {
        $has_ent = DB::table('get_child_boolean')
            ->select('id')
            ->where(['get_id'=>$get_id,'remote_id'=>$remote_id,'remote_id_type'=>$remote_id_type])
            ->first();
        if ( !$has_ent ){
            DB::table('get_child_boolean')->insert(['get_id'=>$get_id,'remote_id'=>$remote_id,'remote_id_type'=>$remote_id_type,'isget'=>1]);
            return 1;
        }
        DB::table('get_child_boolean')->where('id',$has_ent->id)->update('isget',1);
        return 2;
    }
}
