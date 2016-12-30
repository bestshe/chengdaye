<?php

namespace App\Jobs\Dongguan;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,DB,Log;

class GetCompanyList implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $page;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($page)
    {
        //
        $this->page = $page;
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
            $fcBusinesslicenseno = self::validata($ent->fcBusinesslicenseno);
            $fcOrganizationcode = self::validata($ent->fcOrganizationcode);
            //查询是否存在;
            $has_ent = DB::table('get_dg_jy_company_info')
                ->where('remote_id',$remote_id)
                ->first();

            //转换日期格式
            $fdBuilddate = self::date_format($ent->fdBuilddate);
            $fdVaildenddate = self::date_format($ent->fdVaildenddate);
            $fdSafelicencesdate = self::date_format($ent->fdSafelicencesdate);
            $fdSafelicenceedate = self::date_format($ent->fdSafelicenceedate);
            //过滤无效字符
            $fcEntname = self::validata($ent->fcEntname);//公司名称
            $fcArtname = self::validata($ent->fcArtname);//法人
            $fcArtpapersn = self::validata($ent->fcArtpapersn);//法人身份证号码
            $fnEntstatus = self::validata((int)$ent->fnEntstatus);//企业营业执照状态
            $fcBelongareasn = self::validata((int)$ent->fcBelongareasn);//企业所在区域编码
            $fmEnrolfund = self::validata($ent->fmEnrolfund);//注册资金
            $fcEntlinkaddr = self::validata($ent->fcEntlinkaddr);//企业注册地址
            $fcEntlinktel = self::validata($ent->fcEntlinktel);//企业联系电话
            $fcSafelicencenumber = self::validata($ent->fcSafelicencenumber);//建筑企业安全生产许可号
            $fcSafelicencecert = self::validata($ent->fcSafelicencecert);//颁发建筑企业安全生产许可机构
            $fcEntinfodeclareperson = self::validata($ent->fcEntinfodeclareperson);//经办人姓名
            //转换企业性质
            $ent_type = self::validata((int)$ent->fcEntpropertysn);
            $ent_type = self::get_ent_type($ent_type);
            $contact_tel = self::validata($ent->fcEntinfodeclarepersontel);//经办人联系电话
            if ( strlen($contact_tel) > 11  ){
                $contact_tel = null;
            }
            //其它备案信息
            $fnIsotherprovinces = self::validata((int)$ent->fnIsotherprovinces);//是否进粤企业
            $fcIntogdadress = self::validata($ent->fcIntogdadress);//进粤信息
            $fnIsswotherprovinces = self::validata((int)$ent->fnIsswotherprovinces);//是否在水利厅备案
            $fcSwintogdadress = self::validata($ent->fcSwintogdadress);//水利厅备案信息
            $fnIsjtotherprovinces = self::validata((int)$ent->fnIsjtotherprovinces);//是否在公路建设市场信用备案
            $fcJtintogdadress = self::validata($ent->fcJtintogdadress);//公路建设市场信用备案信息
            $ent_arr[$key] = [];
            if ( $has_ent ) {
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcOrganizationcode',$has_ent->fcOrganizationcode,$fcOrganizationcode);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntname',$has_ent->fcEntname,$fcEntname);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcArtname',$has_ent->fcArtname,$fcArtname);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcArtpapersn',$has_ent->fcArtpapersn,$fcArtpapersn);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fnEntstatus',$has_ent->fnEntstatus,$fnEntstatus);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntpropertysn',$has_ent->fcEntpropertysn,$ent_type);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fdBuilddate',$has_ent->fdBuilddate,$fdBuilddate);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fdVaildenddate',$has_ent->fdVaildenddate,$fdVaildenddate);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcBelongareasn',$has_ent->fcBelongareasn,$fcBelongareasn);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fmEnrolfund',$has_ent->fmEnrolfund,$fmEnrolfund);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntlinkaddr',$has_ent->fcEntlinkaddr,$fcEntlinkaddr);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntlinktel',$has_ent->fcEntlinktel,$fcEntlinktel);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcSafelicencenumber',$has_ent->fcSafelicencenumber,$fcSafelicencenumber);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcSafelicencecert',$has_ent->fcSafelicencecert,$fcSafelicencecert);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fdSafelicencesdate',$has_ent->fdSafelicencesdate,$fdSafelicencesdate);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fdSafelicenceedate',$has_ent->fdSafelicenceedate,$fdSafelicenceedate);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntinfodeclareperson',$has_ent->fcEntinfodeclareperson,$fcEntinfodeclareperson);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcEntinfodeclarepersontel',$has_ent->fcEntinfodeclarepersontel,$contact_tel);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fnIsotherprovinces',$has_ent->fnIsotherprovinces,$fnIsotherprovinces);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcIntogdadress',$has_ent->fcIntogdadress,$fcIntogdadress);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fnIsswotherprovinces',$has_ent->fnIsswotherprovinces,$fnIsswotherprovinces);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcSwintogdadress',$has_ent->fcSwintogdadress,$fcSwintogdadress);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fnIsjtotherprovinces',$has_ent->fnIsjtotherprovinces,$fnIsjtotherprovinces);
                $ent_arr[$key] = self::validataLR($ent_arr[$key],'fcJtintogdadress',$has_ent->fcJtintogdadress,$fcJtintogdadress);
                if ( count($ent_arr[$key]) ){
                    $ent_arr[$key] = array_add($ent_arr[$key], 'updated_at', time());
                    //更新数据
                    DB::table('get_dg_jy_company_info')->where('id',$has_ent->id)->update($ent_arr[$key]);
                    self::mark_child($has_ent->id,$remote_id,1);
                }
                return true;
            }
            $ent_arr[$key] = [
                'remote_id' => $remote_id,
                'fcEntname' => $fcEntname,
                'fcArtname' => $fcArtname,
                'fcArtpapersn' => $fcArtpapersn,
                'fnEntstatus' => $fnEntstatus,
                'fcEntpropertysn' => $ent_type,
                'fdBuilddate' => $fdBuilddate,
                'fdVaildenddate' => $fdVaildenddate,
                'fcBelongareasn' => $fcBelongareasn,
                'fmEnrolfund' => $fmEnrolfund,
                'fcBusinesslicenseno' => $fcBusinesslicenseno,
                'fcOrganizationcode' => $fcOrganizationcode,
                'fcEntlinkaddr' => $fcEntlinkaddr,
                'fcEntlinktel' => $fcEntlinktel,
                'fcSafelicencenumber' => $fcSafelicencenumber,
                'fcSafelicencecert' => $fcSafelicencecert,
                'fdSafelicencesdate' => $fdSafelicencesdate,
                'fdSafelicenceedate' => $fdSafelicenceedate,
                'fcEntinfodeclareperson' => $fcEntinfodeclareperson,
                'fcEntinfodeclarepersontel' => $contact_tel,
                'fnIsotherprovinces' => $fnIsotherprovinces,
                'fcIntogdadress' => $fcIntogdadress,
                'fnIsswotherprovinces' => $fnIsswotherprovinces,
                'fcSwintogdadress' => $fcSwintogdadress,
                'fnIsjtotherprovinces' => $fnIsjtotherprovinces,
                'fcJtintogdadress' => $fcJtintogdadress,
                'created_at' => time(),
                'updated_at' => time()
            ];
            //插入数据
            $get_id = DB::table('get_dg_jy_company_info')->insertGetId($ent_arr[$key]);
            self::mark_child($get_id,$remote_id,1);
            Log::info($cur_time.'---GetCompanyList采集---'.$fcEntname.'---企业信息成功');
            return true;
        }
    }

    //转换企业性质分类ID
    public function get_ent_type($typeid){
        switch ($typeid)
        {
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
