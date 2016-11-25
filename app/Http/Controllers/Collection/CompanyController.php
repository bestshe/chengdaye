<?php

namespace App\Http\Controllers\Collection;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use QL\QueryList;
use Cache;

class CompanyController extends Controller
{
    public $title;
    public $code;
    public $type_title;
    public $type;
    public $get_json;
    //采集企业信息总页数
    public function CompanyPages()
    {
        if ( Cache::has('get_main_info_ent_pages') ) {
            return Cache::get('get_main_info_ent_pages');
            exit;
        }
        $get_main_info = DB::table('get_main_info')->where('id',1)->get();
        if ( $get_main_info == null){
            return '非法操作';
            exit;
        }
        //是否重新更新企业信息页数，判断更新时间对比；
        $vaild_time = $get_main_info->updated_at + 86400;
        if ( $get_main_info->get_json == null or $vaild_time < time() ) {
            //先获取要采集的页面源码
            $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/list';
            $rules = array(
                'total_pages' => array("script:eq(9)","html")
            );
            //然后可以把页面源码或者HTML片段传给QueryList
            $data = QueryList::Query($url,$rules)->data;
            preg_match('/\d+/', $data[0]['total_pages'],$arr);
            //拿到企业信息总页数；
            $total_pages = (int)$arr[0];
            $get_json = json_encode(['total_pages'=>$total_pages]);
            //写入数据库中
            DB::table('get_main_info')
                ->where('id', 1)
                ->update([
                    'get_json' => $get_json,
                    'crea'
                ]);
            //
            $get_main_info_ent_pages = [
                'title' => $get_main_info->title,
                'code' => $get_main_info->code,
                'type_title' => $get_main_info->type_title,
                'type' => $get_main_info->type,
                'get_json' => [
                    'total_pages'=>$total_pages
                ]
            ];
            $get_main_info_ent_pages = json_encode($get_main_info_ent_pages);
            return $get_main_info_ent_pages;
            //Cache::put('get_main_info_ent_pages', $get_main_info_ent_pages, 120);

        }
    }
}
