<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use QL\QueryList;

class cert extends Controller
{
    public function addcert(Request $request)
    {
        $input = $request->only(['id', 'level']);
        $cid = (int)$input['id'];
        $certs = DB::table('b_cert')->where('id',$cid)->first();
        if ( (int)$input['level'] == 4 ){
            $certname[0]['name'] = $certs->name . '特级';
            $certname[1]['name'] = $certs->name . '一级';
            $certname[2]['name'] = $certs->name . '二级';
            $certname[3]['name'] = $certs->name . '三级';
            $certname[0]['parent_id'] = $cid;
            $certname[1]['parent_id'] = $cid;
            $certname[2]['parent_id'] = $cid;
            $certname[3]['parent_id'] = $cid;
        }

        if ( (int)$input['level'] == 3 ){
            $certname[0]['name'] = $certs->name . '一级';
            $certname[1]['name'] = $certs->name . '二级';
            $certname[2]['name'] = $certs->name . '三级';
            $certname[0]['parent_id'] = $cid;
            $certname[1]['parent_id'] = $cid;
            $certname[2]['parent_id'] = $cid;
        }

        if ( (int)$input['level'] == 2 ){
            $certname[0]['name'] = $certs->name . '一级';
            $certname[1]['name'] = $certs->name . '二级';
            $certname[0]['parent_id'] = $cid;
            $certname[1]['parent_id'] = $cid;
        }

        /*if ( (int)$input['level'] == 1 or (int)$input['level'] == null ){
            $certname[0]['name'] = $certs->name;
            $certname[0]['parent_id'] = $cid;
        }*/

        //DB::table('b_cert')->insert($certname);

        print_r($certname);
    }

    public function get_company_cert(Request $request)
    {
        $id = (int)$request->get('id');
        //跳转到下一页
        $next_id = $id + 1;
        $next_url = '/CompanyCerts?id='.$next_id;
        if ( !$id ){
            return '非法入内';
        }
        $ent = DB::table('get_dg_jy_company_info')
            ->select('remote_id')
            ->where([
                ['id',$id],
                ['no_import',0]
            ])
            ->first();
        if ( !$ent ){
            echo '没有,不存在';
            header("refresh:1;url=".$next_url);
            exit;
        }
        $remote_id = $ent->remote_id;
        //采集企业资质列表
        $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/view?id='.$remote_id;
        $rules = array(
            'js_content' => array("script:eq(13)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match_all("/qualificationList:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $arr);
        if ( !count($arr[0]) ){
            echo '没有资质';
            header("refresh:1;url=".$next_url);
            exit;
        }
        $data = str_replace('qualificationList:ko.observableArray(','',$arr[0][0]);
        $data = str_replace(')','',$data);
        $data = json_decode($data,true);
        foreach($data as $n=>$certs){
            //采集企业每个资质
            $url_item = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/CertfileList?fcCertfileid='.$certs['id'];
            $rules_item = array(
                'js_content' => array("script:eq(14)","html")
            );
            $data_item = QueryList::Query($url_item,$rules_item)->data;
            preg_match_all("/detailList: ko\.observableArray\(\[.*?\]\)/is", $data_item[0]['js_content'], $arrs);
            if ( !count($arrs[0]) ){
                continue;
            }
            $data_item = str_replace('detailList: ko.observableArray(','',$arrs[0][0]);
            $data_item = str_replace(')','',$data_item);
            $data_item = json_decode($data_item,true);
            foreach($data_item as $m=>$cert){
                //查询是否存在;
                $repeat_cert = DB::table('get_dg_jy_company_cert')
                    ->where(['remote_cert_id'=>$certs['id'],'cert_code'=>$cert['fcEntcertcode']])
                    ->first();
                //重置处理数据
                $fdCertstartdate = self::date_format($cert['fdCertstartdate']);
                $fdCertenddate = self::date_format($cert['fdCertenddate']);
                $certname = self::replace_certname($cert['fcEntcertcaption']);
                $cert_list = [];
                $update_arr[$n] = [];
                if ( $repeat_cert ){
                    $cert_list = self::compare_local_remote($cert_list,'remote_ent_id',$repeat_cert->remote_ent_id,$remote_id);
                    $bc_id = self::local_cert_id($certname);
                    $cert_list = self::compare_local_remote($cert_list,'bc_id',$repeat_cert->bc_id,$bc_id);
                    $cert_list = self::compare_local_remote($cert_list,'cert_file_code',$repeat_cert->cert_file_code,$certs['fcCertfilecode']);
                    $agency_id = self::local_agency_id($certs['fcCertfileauditorgan']);
                    $cert_list = self::compare_local_remote($cert_list,'agency_id',$repeat_cert->agency_id,$agency_id);
                    $cert_list = self::compare_local_remote($cert_list,'agency',$repeat_cert->agency,$certs['fcCertfileauditorgan']);
                    $cert_list = self::compare_local_remote($cert_list,'fcEntcertcaption',$repeat_cert->fcEntcertcaption,$certname);
                    $cert_list = self::compare_local_remote($cert_list,'fdCertstartdate',$repeat_cert->fdCertstartdate,$fdCertstartdate);
                    $cert_list = self::compare_local_remote($cert_list,'fdCertenddate',$repeat_cert->fdCertenddate,$fdCertenddate);
                    if ( count($cert_list) ){
                        $cert_list = array_add($cert_list, 'updated_at', time());
                        //更新数据
                        DB::table('get_dg_jy_company_cert')->where('id',$repeat_cert->id)->update($cert_list);
                        $update_arr[$n] = array_add($update_arr[$n], 'title', $cert['fcEntcertcaption'].'===========已修改');
                        $update_arr[$n] = array_add($update_arr[$n], 'sn', $cert['fcEntcertcode'].'===========已修改');
                    }else{
                        $update_arr[$n] = array_add($update_arr[$n], 'title', $cert['fcEntcertcaption'].'===========未修改');
                        $update_arr[$n] = array_add($update_arr[$n], 'sn', $cert['fcEntcertcode'].'===========未修改');
                    }

                }else{
                    //远程企业ID
                    $cert_list['remote_ent_id'] = $remote_id;
                    //远程证书ID
                    $cert_list['remote_cert_id'] = $certs['id'];
                    //本地资质分类ID
                    $cert_list['bc_id'] = self::local_cert_id($certname);
                    //证书号
                    $cert_list['cert_file_code'] = $certs['fcCertfilecode'];
                    //颁发机构ID
                    $cert_list['agency_id'] = self::local_agency_id($certs['fcCertfileauditorgan']);
                    //颁发机构
                    $cert_list['agency'] = $certs['fcCertfileauditorgan'];
                    //资质名称
                    $cert_list['fcEntcertcaption'] = $certname;
                    //资质编号
                    $cert_list['cert_code'] = $cert['fcEntcertcode'];
                    //有效期
                    $cert_list['fdCertstartdate'] = $fdCertstartdate;
                    $cert_list['fdCertenddate'] = $fdCertenddate;
                    $cert_list['created_at'] = time();
                    $cert_list['updated_at'] = time();

                    //插入数据
                    DB::table('get_dg_jy_company_cert')->insert($cert_list);
                    $update_arr[$n] = array_add($update_arr[$n], 'title', $cert['fcEntcertcaption'].'===========新插入');
                    $update_arr[$n] = array_add($update_arr[$n], 'sn', $cert['fcEntcertcode'].'===========新插入');
                }

            }

        }

        //print_r($cert_list);
        print_r($update_arr);
        header("refresh:1;url=".$next_url);
        exit;
    }

    //查询远程对应的本地资质分类ID
    public function local_cert_id($remote_data)
    {
        if ( !$remote_data ){
            return null;
        }
        //查询是否存在;
        $repeat_cert = DB::table('b_cert')
            ->select('id')
            ->where('name',$remote_data)
            ->first();
        if ( !$repeat_cert ){
            return null;
        }
        return $repeat_cert->id;
    }

    //查询远程对应的本地颁发机构ID
    public function local_agency_id($remote_data)
    {
        if ( !$remote_data ){
            return null;
        }
        //查询是否存在;
        $repeat_agency = DB::table('b_cert_agency')
            ->select('id')
            ->where('name',$remote_data)
            ->first();
        if ( !$repeat_agency ){
            $ID = DB::table('b_cert_agency')->insertGetId(['name'=>$remote_data]);
            return $ID;
        }
        return $repeat_agency->id;
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
    //转换资质名称
    public function replace_certname($name){
        $name = trim($name);
        $name = str_replace('壹级','一级',$name);
        $name = str_replace('贰级','二级',$name);
        $name = str_replace('叁级','三级',$name);
        $name = str_replace('肆级','四级',$name);
        $name = str_replace('伍级','五级',$name);
        $name = str_replace('分项','',$name);
        switch ($name)
        {
            case '防水防腐保温工程专业承包二级':return '防腐防水防腐保温工程专业承包二级';break;
            case '防水防腐保温工程专业承包一级':return '防腐防水防腐保温工程专业承包一级';break;
            case '预拌混凝土专业承包不分 等级':return '预拌混凝土专业承包';break;
            case '公路交通工程（公路机电工程）专业承包':return '公路交通工程（公路机电工程）专业承包一级';break;
            case '公路交通工程（公路安全设施）专业承包':return '公路交通工程（公路安全设施）专业承包一级';break;
            case '特种工程专业承包不分等级':return '特种工程专业承包';break;
            case '特种工程专业承包（结构补强） 不分等级':return '特种工程（结构补强）专业承包';break;
            case '特种工程专业承包（建筑物纠偏和平移） 不分等级':return '特种工程（建筑物纠偏和平移）专业承包';break;
            case '特种工程专业承包（特种防雷） 不分等级':return '特种工程（特种防雷）专业承包';break;
            case '特种工程专业承包（特殊设备起重吊装）不分等级':return '特种工程（特种设备的起重吊装）专业承包';break;
            default:return $name;break;//其它
        }
    }

    public function Null_cert()
    {
        //->where(['bc_id' => null,'fcEntcertcaption' => '特种工程专业承包不分等级'])
        $data = DB::table('get_dg_jy_company_cert')
            ->select('id','fcEntcertcaption')
            ->where(['bc_id' => null])
            ->get();
        if (!count($data)){
            echo '没有';
            exit;
        }
        foreach ($data as $key=>$item) {
            //echo $key.'=>'.$item->id.'***************************************************';
            $arrs[$key] = ['id' => $item->id,'fcEntcertcaption' => $item->fcEntcertcaption];
            //插入数据
            /*DB::table('get_dg_jy_company_cert')->where('id',$item->id)
                ->update([
                    'bc_id' => 51,
                    'fcEntcertcaption' => '特种工程专业承包'
                ]);*/
        }
        return $arrs;
    }
    
}
