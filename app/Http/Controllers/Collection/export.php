<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class export extends Controller
{
    //
    public function entlist()
    {
        /*$certlists = DB::table('get_gd_jy_company_cert')
            ->select('id')
            ->where('bc_id',119)
            ->get();*/
        $entlists = DB::table('get_gd_jy_company_cert')
            ->leftJoin('get_gd_jy_company_info', 'get_gd_jy_company_cert.remote_ent_id', '=', 'get_gd_jy_company_info.remote_id')
            ->select('get_gd_jy_company_info.fcEntname')
            ->where('get_gd_jy_company_cert.bc_id',119)
            ->get();
        foreach ($entlists as $n=>$list) {
            echo $list->fcEntname.'<br>';
        }
        /*echo '===============================分隔线===================================<br>';
        foreach ($certlists as $m=>$clist) {
            echo $m.'======='.$clist->id.'<br>';
        }*/
    }
}
