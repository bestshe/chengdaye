<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

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
}
