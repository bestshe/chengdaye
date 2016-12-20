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
        //获取采集企业信息
        $get_main_info = DB::table('get_main_info')->where('id',1)->first();
        if ( $get_main_info == null){
            return '非法操作';
            exit;
        }
        //是否重新更新企业信息页数，判断更新时间对比；
        $vaild_time = $get_main_info->updated_at + 86400;
        $get_json = json_decode($get_main_info->get_json);
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
            $get_json = ['total_pages'=>$total_pages];
            //写入数据库中
            DB::table('get_main_info')
                ->where('id', 1)
                ->update([
                    'get_json' => json_encode($get_json),
                    'updated_at' => time()
                ]);
        }
        //处理为json
        $get_main_info_ent_pages = [
            'title' => $get_main_info->title,
            'code' => $get_main_info->code,
            'type_title' => $get_main_info->type_title,
            'type' => $get_main_info->type,
            'get_json' => $get_json
        ];
        $get_main_info_ent_pages = json_encode($get_main_info_ent_pages);
        if (!Cache::has('get_main_info_ent_pages')) {
            Cache::put('get_main_info_ent_pages', $get_main_info_ent_pages, 120);
        }
        return Cache::get('get_main_info_ent_pages');
    }
    //采集企业信息
    public function Companyinfos(Request $request)
    {
        //开始计算时间
        $stime = microtime(true);
        if (Cache::has('get_main_info_ent_pages')) {
            $get_main_info_ent_pages = json_decode(Cache::get('get_main_info_ent_pages'));
            $total_pages = $get_main_info_ent_pages->get_json->total_pages;
        }
        if ( isset($total_pages) == false ){
            return '没有刷新总页面，请返回刷新';
        }
        $page = (int)$request->get('page');
        if ( $page < $total_pages ){
            $page == 0 ? $page = 1 : $page = $page;
            //当前页的企业列表
            $curl = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/findListByPage?currentPage='.$page;
            $data = file_get_contents($curl);
            $data = json_decode($data);
            //中间计算时间
            $mtime = microtime(true);
            //return $data->ls;
            foreach($data->ls as $key=>$ent){
                $remote_id = $ent->id;
                $fcBusinesslicenseno = $ent->fcBusinesslicenseno;
                //查询是否存在;
                $repeat_ent = DB::table('get_dg_jy_company_info')
                    ->where(['remote_id'=>$remote_id,'fcBusinesslicenseno'=>$fcBusinesslicenseno])
                    ->first();
                //转换企业性质
                $ent_type = (int)$ent->fcEntpropertysn;
                $ent_type = self::get_ent_type($ent_type);
                //转换日期格式
                $fdBuilddate = self::date_format($ent->fdBuilddate);
                $fdVaildenddate = self::date_format($ent->fdVaildenddate);
                $fdSafelicencesdate = self::date_format($ent->fdSafelicencesdate);
                $fdSafelicenceedate = self::date_format($ent->fdSafelicenceedate);
                $contact_tel = $ent->fcEntinfodeclarepersontel;
                if ( strlen($contact_tel) > 11  ){
                    $contact_tel = null;
                }
                $ent_arr[$key] = [];
                $update_ent_arr[$key] = [];
                if ( $repeat_ent ) {
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntname',$repeat_ent->fcEntname,$ent->fcEntname);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcArtname',$repeat_ent->fcArtname,$ent->fcArtname);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcArtpapersn',$repeat_ent->fcArtpapersn,$ent->fcArtpapersn);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fnEntstatus',$repeat_ent->fnEntstatus,(int)$ent->fnEntstatus);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntpropertysn',$repeat_ent->fcEntpropertysn,$ent_type);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fdBuilddate',$repeat_ent->fdBuilddate,$fdBuilddate);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fdVaildenddate',$repeat_ent->fdVaildenddate,$fdVaildenddate);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcBelongareasn',$repeat_ent->fcBelongareasn,(int)$ent->fcBelongareasn);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fmEnrolfund',$repeat_ent->fmEnrolfund,$ent->fmEnrolfund);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntlinkaddr',$repeat_ent->fcEntlinkaddr,$ent->fcEntlinkaddr);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntlinktel',$repeat_ent->fcEntlinktel,$ent->fcEntlinktel);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcSafelicencenumber',$repeat_ent->fcSafelicencenumber,$ent->fcSafelicencenumber);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcSafelicencecert',$repeat_ent->fcSafelicencecert,$ent->fcSafelicencecert);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fdSafelicencesdate',$repeat_ent->fdSafelicencesdate,$fdSafelicencesdate);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fdSafelicenceedate',$repeat_ent->fdSafelicenceedate,$fdSafelicenceedate);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntinfodeclareperson',$repeat_ent->fcEntinfodeclareperson,$ent->fcEntinfodeclareperson);
                    $ent_arr[$key] = self::compare_local_remote($ent_arr[$key],'fcEntinfodeclarepersontel',$repeat_ent->fcEntinfodeclarepersontel,$contact_tel);
                    if ( count($ent_arr[$key]) ){
                        $ent_arr[$key] = array_add($ent_arr[$key], 'updated_at', time());
                        //更新数据
                        DB::table('get_dg_jy_company_info')->where('id',$repeat_ent->id)->update($ent_arr[$key]);
                        $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'title', $ent->fcEntname.'===========已修改');
                        $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'sn', $ent->fcBusinesslicenseno.'===========已修改');
                    }else{
                        $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'title', $ent->fcEntname.'===========未修改');
                        $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'sn', $ent->fcBusinesslicenseno.'===========未修改');
                    }

                }else{
                    $ent_arr[$key] = [
                        'remote_id' => $remote_id,
                        'fcEntname' => $ent->fcEntname,
                        'fcArtname' => $ent->fcArtname,
                        'fcArtpapersn' => $ent->fcArtpapersn,
                        'fnEntstatus' => (int)$ent->fnEntstatus,
                        'fcEntpropertysn' => $ent_type,
                        'fdBuilddate' => $fdBuilddate,
                        'fdVaildenddate' => $fdVaildenddate,
                        'fcBelongareasn' => (int)$ent->fcBelongareasn,
                        'fmEnrolfund' => $ent->fmEnrolfund,
                        'fcBusinesslicenseno' => $fcBusinesslicenseno,
                        'fcEntlinkaddr' => $ent->fcEntlinkaddr,
                        'fcEntlinktel' => $ent->fcEntlinktel,
                        'fcSafelicencenumber' => $ent->fcSafelicencenumber,
                        'fcSafelicencecert' => $ent->fcSafelicencecert,
                        'fdSafelicencesdate' => $fdSafelicencesdate,
                        'fdSafelicenceedate' => $fdSafelicenceedate,
                        'fcEntinfodeclareperson' => $ent->fcEntinfodeclareperson,
                        'fcEntinfodeclarepersontel' => $contact_tel,
                        'created_at' => time(),
                        'updated_at' => time()
                    ];
                    //插入数据
                    DB::table('get_dg_jy_company_info')->insert($ent_arr[$key]);
                    $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'title', $ent->fcEntname.'===========新插入');
                    $update_ent_arr[$key] = array_add($update_ent_arr[$key], 'sn', $ent->fcBusinesslicenseno.'===========新插入');
                }
            }
            //中间计算时间
            $etime = microtime(true);
            $gtotal=$mtime-$stime;
            $ftotal=$etime-$mtime;
            $update_ent_arr = array_add($update_ent_arr,'gtotal','请求资源时间'.$gtotal);
            $update_ent_arr = array_add($update_ent_arr,'ftotal','循环处理时间'.$ftotal);
            $update_ent_arr = array_add($update_ent_arr,'msg','采集第'.$page.'页企业信息。');
            print_r($update_ent_arr);
            //跳转到下一页
            $next_page = $page + 1;
            $url = '/Companyinfos?page='.$next_page;
            header("refresh:1;url=".$url);
            exit;
        }
        return '没有了';
    }
    //日期格式化
    public function date_format($date){
        if ( $date ){
            return date($date);
        }else{
            return null;
        }
    }
    //对比本地和运程数据是否一致；
    public function compare_local_remote($arr,$field,$local_data,$remote_data){
        if ( $local_data != $remote_data ){
            return array_add($arr, $field, $remote_data);
        }else{
            return $arr;
        }
    }
    //转换企业性质分类ID
    public function get_ent_type($typeid){
        switch ($typeid)
        {
            /*
             * case "100":return "国有全资"; break;
            case "110":return "集体全资"; break;
            case "130":return "股份合作"; break;
            case "140":return "联 营"; break;
            case "149":return "其他联营"; break;
            case "142":return "集体联营"; break;
            case "141":return "国有联营"; break;
            case "150":return "有限责任(公司)"; break;
            case "159":return "股份有限公司";break;
            case "160":return "有限合伙企业"; break;
            */
            case 100:return 1;break;//国有全资
            case 110:return 2;break;//集体全资
            case 130:return 3;break;//股份合作
            case 140:return 4;break;//联 营
            case 149:return 5;break;//其他联营
            case 142:return 6;break;//集体联营
            case 141:return 7;break;//国有联营
            case 150:return 8;break;//有限责任(公司)
            case 159:return 9;break;//股份有限公司
            case 160:return 10;break;//有限合伙企业
            default:return 0;break;//其它
        }
    }
}
