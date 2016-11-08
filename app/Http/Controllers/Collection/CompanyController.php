<?php

namespace App\Http\Controllers\Collection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    //
    public function clist()
    {
        //待采集的目标页面，PHPHub教程区

        $yu_ming = 'https://www.dgzb.com.cn/ggzy/website/';
        $ID = '2c9296be57fbc44201581a20b6ca7852';
        $page = $yu_ming . '/WebPagesManagement/CreditSystem/Enterprise/CertfileList?fcCertfileid=' . $ID;
        //采集规则
        $rules = array(
            //文章标题
            'title' => ['span ','text'],
            //文章链接
            'link' => ['.media-heading a','href'],
            //文章作者名
            'author' => ['.img-thumbnail','alt']
        );
        //列表选择器
        $rang = 'tbody';
        //采集
        $data = \QL\QueryList::Query($page,$rules,$rang)->data;
        //查看采集结果
        print_r($data);
    }
}
