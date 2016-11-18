<?php

namespace App\Http\Controllers\Collection;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use QL\QueryList;

class CompanyController extends Controller
{
    public $title;
    public $code;
    public $type_title;
    public $type;
    public $get_json;
    //定义个处理方法用于QL回调
    public function CompanyPages()
    {

        //可以先手动获取要采集的页面源码
        $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/list';
        $rules = array(
            'total_pages' => array("script:eq(9)","html")
        );
        //然后可以把页面源码或者HTML片段传给QueryList
        $data = QueryList::Query($url,$rules)->data;
        preg_match('/\d+/', $data[0]['total_pages'],$arr);
        print_r($arr);
        exit;
    }
}
