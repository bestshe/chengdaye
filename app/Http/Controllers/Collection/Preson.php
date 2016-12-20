<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use QL\QueryList;

class Preson extends Controller
{
    //
    /**
     * @return string
     */
    public function get_talent_persons(Request $request)
    {
        $id = (int)$request->get('id');
        //跳转到下一页
        $next_id = $id + 1;
        $next_url = '/TalentPersons?id='.$next_id;
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
        //采集企业人员列表
        $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/view?id='.$remote_id;
        $rules = array(
            'js_content' => array("script:eq(13)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match_all("/entpersonInfolist:ko\.observableArray\(\[.*?\]\)/is", $data[0]['js_content'], $arr);
        if ( !count($arr[0]) ){
            echo '没有,不存在2';
            header("refresh:1;url=".$next_url);
            exit;
        }
        $data = str_replace('entpersonInfolist:ko.observableArray(','',$arr[0][0]);
        $data = str_replace(')','',$data);
        $data = json_decode($data,true);
        foreach($data as $n=>$presons){
            //查询是否存在;
            $repeat_p = DB::table('get_dg_jy_person')
                ->where(['remote_person_id'=>$presons['id'],'remote_ent_id'=>$presons['fcEntid']])
                ->first();
            $p_list = [];
            $update_arr[$n] = [];
            $fcPersonname = self::validata($presons['fcPersonname']);
            $fcSex = self::validata($presons['fcSex']);
            $id = self::validata($presons['id']);
            $fcEntid = self::validata($presons['fcEntid']);
            $fcPersonsn = self::validata($presons['fcPersonsn']);
            $fcEntcode = self::validata($presons['fcEntcode']);
            $fcPersonphoto = self::validata($presons['fcPersonphoto']);
            $fcCopyidcard = self::validata($presons['fcCopyidcard']);
            $fcLinktel = self::validata($presons['fcLinktel']);
            $fcLinkaddr = self::validata($presons['fcLinkaddr']);
            $fcMaxschooling = self::validata($presons['fcMaxschooling']);
            $fcSchoolingspecialty = self::validata($presons['fcSchoolingspecialty']);
            $fcTechnicaltitle = self::validata($presons['fcTechnicaltitle']);
            $fcBelongareasn = self::validata($presons['fcBelongareasn']);
            if ( $repeat_p ){
                $p_list = self::compare_local_remote($p_list,'name',$repeat_p->name,$fcPersonname);
                $p_list = self::compare_local_remote($p_list,'sex',$repeat_p->sex,(int)$fcSex);
                $p_list = self::compare_local_remote($p_list,'fcPersonsn',$repeat_p->fcPersonsn,$fcPersonsn);
                $p_list = self::compare_local_remote($p_list,'fcPersonphoto',$repeat_p->fcPersonphoto,$fcPersonphoto);
                $p_list = self::compare_local_remote($p_list,'fcCopyidcard',$repeat_p->fcCopyidcard,$fcCopyidcard);
                $p_list = self::compare_local_remote($p_list,'fcLinktel',$repeat_p->fcLinktel,$fcLinktel);
                $p_list = self::compare_local_remote($p_list,'fcLinkaddr',$repeat_p->fcLinkaddr,$fcLinkaddr);
                $p_list = self::compare_local_remote($p_list,'fcMaxschooling',$repeat_p->fcMaxschooling,$fcMaxschooling);
                $p_list = self::compare_local_remote($p_list,'$fcSchoolingspecialty',$repeat_p->fcSchoolingspecialty,$fcSchoolingspecialty);
                $p_list = self::compare_local_remote($p_list,'fcTechnicaltitle',$repeat_p->fcTechnicaltitle,$fcTechnicaltitle);
                $p_list = self::compare_local_remote($p_list,'fcBelongareasn',$repeat_p->fcBelongareasn,$fcBelongareasn);
                if ( count($p_list) ){
                    $p_list = array_add($p_list, 'updated_at', time());
                    //更新数据
                    DB::table('get_dg_jy_person')->where('id',$repeat_p->id)->update($p_list);
                    $update_arr[$n] = array_add($update_arr[$n], 'name', $presons['fcPersonname'].'===========已修改');
                    $update_arr[$n] = array_add($update_arr[$n], 'sn', $presons['fcPersonsn'].'===========已修改');
                }else{
                    $update_arr[$n] = array_add($update_arr[$n], 'name', $presons['fcPersonname'].'===========未修改');
                    $update_arr[$n] = array_add($update_arr[$n], 'sn', $presons['fcPersonsn'].'===========未修改');
                }
            }else{
                $ps = [
                    'name' => $fcPersonname,
                    'sex' => (int)$fcSex,
                    'remote_person_id' => $id,
                    'remote_ent_id' => $fcEntid,
                    'fcEntcode' => $fcEntcode,
                    'fcPersonsn' => $fcPersonsn,
                    'fcPersonphoto' => $fcPersonphoto,
                    'fcCopyidcard' => $fcCopyidcard,
                    'fcLinktel' => $fcLinktel,
                    'fcLinkaddr' => $fcLinkaddr,
                    'fcMaxschooling' => $fcMaxschooling,
                    'fcSchoolingspecialty' => $fcSchoolingspecialty,
                    'fcTechnicaltitle' => $fcTechnicaltitle,
                    'fcBelongareasn' => (int)$fcBelongareasn,
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                //插入数据
                DB::table('get_dg_jy_person')->insert($ps);
                $update_arr[$n] = array_add($update_arr[$n], 'name', $presons['fcPersonname'].'===========新插入');
                $update_arr[$n] = array_add($update_arr[$n], 'sn', $presons['fcPersonsn'].'===========新插入');
            }
        }
        //print_r();
        var_dump($update_arr);
        header("refresh:1;url=".$next_url);
        exit;
    }

    //采集人员证书
    public function get_talent_certs(Request $request){
        $id = (int)$request->get('id');
        //跳转到下一页
        $next_id = $id + 1;
        $next_url = '/TalentCerts?id='.$next_id;
        if ( !$id ){
            return '非法入内';
        }
        $presons = DB::table('get_dg_jy_person')
            ->select('remote_person_id')
            ->where('id',$id)
            ->first();
        if ( !$presons ){
            echo '没有,不存在';
            header("refresh:1;url=".$next_url);
            exit;
        }
        $person_id = $presons->remote_person_id;
        $url_item = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/EnterPublic/EntRegView?id='.$person_id;
        $rules_item = array(
            'cert_type' => array('td:eq(0)','text'),
            'cert_code' => array('td:eq(1)','text'),
            'agency' => array('td:eq(2)','text'),
            'cert_name' => array('td:eq(3)','text'),
            'specialty' => array('td:eq(4)','text'),
            'cert_level' => array('td:eq(5)','text'),
            'validate' => array('td:eq(6)','text'),
        );
        $rang = 'tbody tr';
        $data_item = QueryList::Query($url_item,$rules_item,$rang)->data;
        if ( !count($data_item) ){
            echo '没有,不存在3';
            header("refresh:1;url=".$next_url);
            exit;
        }
        $data_item = self::merge_data($data_item);
        foreach ($data_item as $n => $certs){
            preg_match_all('/(\d+)-(\d+)-(\d+)/i',$certs['validate'],$valiarr);
            if ( empty($valiarr[0]) ) {
                $valiarr[0][0] = '9999-12-31';
            }
            $cs = [];
            $update_arr[$n] = [];
            $cert_type = self::validata($certs['cert_type']);
            $cert_name = self::validata($certs['cert_name']);
            $cert_code = self::validata($certs['cert_code']);
            $cert_name = self::turn_empty($cert_type,$cert_name,$cert_code);
            $cert_level = self::validata($certs['cert_level']);
            $cert_id = self::local_cert_id($cert_type,$cert_level,$cert_name,$cert_code);
            $agency = self::validata($certs['agency']);
            $agency_id = self::local_agency_id($agency);
            $specialty = self::validata($certs['specialty']);
            $validate = self::date_format($valiarr[0][0]);
            $specialty_id = self::local_specialty_id($cert_id,$specialty);

            //查询是否存在;
            $repeat_c = DB::table('get_dg_jy_person_cert')
                ->where([ 'g_dg_jy_p_id' => $id , 'cert_code' => $cert_code ])
                ->first();
            if ( $repeat_c ) {
                $cs = self::compare_local_remote($cs, 'cert_name', $repeat_c->cert_name, $cert_name);
                $cs = self::compare_local_remote($cs, 'cert_code', $repeat_c->cert_code, $cert_code);
                $cs = self::compare_local_remote($cs, 'cert_id', $repeat_c->cert_id, $cert_id);
                $cs = self::compare_local_remote($cs, 'agency_id', $repeat_c->agency_id, $agency_id);
                $cs = self::compare_local_remote($cs, 'agency', $repeat_c->agency, $agency);
                $cs = self::compare_local_remote($cs, 'specialty', $repeat_c->specialty, $specialty);
                $cs = self::compare_local_remote($cs, 'specialty_id', $repeat_c->specialty_id, $specialty_id);
                $cs = self::compare_local_remote($cs, 'fdCertenddate', $repeat_c->fdCertenddate, $validate);
                if ( count($cs) ){
                    $cs = array_add($cs, 'updated_at', time());
                    //更新数据
                    DB::table('get_dg_jy_person_cert')->where('id',$repeat_c->id)->update($cs);
                    $update_arr[$n] = array_add($update_arr[$n], 'cert_name', $cert_name.'===========已修改');
                    $update_arr[$n] = array_add($update_arr[$n], 'cert_code', $cert_code.'===========已修改');
                }else{
                    $update_arr[$n] = array_add($update_arr[$n], 'cert_name', $cert_name.'===========未修改');
                    $update_arr[$n] = array_add($update_arr[$n], 'cert_code', $cert_code.'===========未修改');
                }
            }else{
                $cs = [
                    'g_dg_jy_p_id' => $id,
                    'cert_id' => $cert_id,
                    'cert_name' => $cert_level.$cert_name,
                    'cert_code' => $cert_code,
                    'agency_id' => $agency_id,
                    'agency' => $agency,
                    'specialty' => $specialty,
                    'specialty_id' => $specialty_id,
                    'fdCertenddate' => $validate,
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                //插入数据
                DB::table('get_dg_jy_person_cert')->insert($cs);
                $update_arr[$n] = array_add($update_arr[$n], 'cert_name', $cert_name.'===========新插入');
                $update_arr[$n] = array_add($update_arr[$n], 'cert_code', $cert_code.'===========新插入');
            }

        }
        var_dump($update_arr);
        header("refresh:1;url=".$next_url);
        exit;

    }

    /**
     * @合并同一证件，多个专业;
     */
    public function merge_data($arr)
    {
        $item = [];
        foreach($arr as $k=>$v){
            if(!isset($item[$v['cert_code']])){
                $item[$v['cert_code']]=$v;
            }else{
                $item[$v['cert_code']]['specialty'].=','.$v['specialty'];
            }
        }
        $item = array_values($item);
        return $item;
    }

    //查询远程对应的本地证件ID
    public function local_cert_id($cert_type,$cert_level,$cert_name,$cert_code)
    {
        if ( $cert_type == '注册证' ){
            if ( $cert_name == '注册建造师' ){
                if ($cert_level == '一级' or $cert_level == '壹级'){
                    return 2;
                }
                if ($cert_level == '二级' or $cert_level == '贰级'){
                    return 3;
                }
                return null;
            }
            if ( $cert_name == '临时注册建造师' ){
                if ($cert_level == '一级' or $cert_level == '壹级'){
                    return 4;
                }
                if ($cert_level == '二级' or $cert_level == '贰级'){
                    return 5;
                }
                return null;
            }
            if ( $cert_name == '注册造价工程师' ){
                return 10;
            }
        }
        if ( $cert_type == '安考证' ){
            $tmp_a = explode('安A',$cert_code);
            $tmp_b = explode('安B',$cert_code);
            if( count($tmp_a)>1 ){
                return 12;
            }
            if( count($tmp_b)>1 ){
                return 13;
            }
        }
        return null;
    }

    /**
     * @转换空的证件名
     */
    public function turn_empty($cert_type,$cert_name,$cert_code){
        if ( $cert_type == '安考证' ){
            $tmp_a = explode('安A',$cert_code);
            $tmp_b = explode('安B',$cert_code);
            if( count($tmp_a)>1 ){
                return '企业负责人A证';
            }
            if( count($tmp_b)>1 ){
                return '企业项目负责人B证';
            }
        }
        if ( $cert_type == '其他证件' ){
            return '其他证件';
        }
        return $cert_name;
    }


    //查询远程对应的本地专业ID
    public function local_specialty_id($cert_id,$specialty){
        if ( !$specialty ){
            return null;
        }
        $str = '';
        if ($cert_id == 4 or $cert_id == 5 or $cert_id == 2 or $cert_id == 3 ){
            //查询是否存在;
            $sarr = explode(',',$specialty);
            foreach ($sarr as $v){
                $repeat_specialty = DB::table('t_specialty')
                    ->select('id')
                    ->where(['name'=>$v,'cert_id'=>$cert_id])
                    ->first();
                if ( !$repeat_specialty ){
                    $ID = DB::table('t_specialty')->insertGetId(['name'=>$v,'cert_id'=>$cert_id]);
                    $str .= ','.$ID;
                }else{
                    $str .= ','.$repeat_specialty->id;
                }
            }
            $str = substr_replace($str,'',0,1);
            return $str;
        }
        return null;

    }
    //查询远程对应的本地颁发机构ID
    public function local_agency_id($remote_data){
        if ( $remote_data == '住建部' ){
            return 3;
        }
        return null;
    }

    //日期格式化
    public function date_format($date){
        if ( $date ){
            return date($date);
        }else{
            return null;
        }
    }

    //判断是否为空
    public function validata($string){
        $string = trim($string);
        if ( $string == '' or $string == '无' or $string == '/'){
            return null;
        }else{
            return $string;
        }
    }

    //对比本地和运程数据是否一致;
    public function compare_local_remote($arr,$field,$local_data,$remote_data){
        if ( $local_data != $remote_data ){
            return array_add($arr, $field, $remote_data);
        }else{
            return $arr;
        }
    }
}
