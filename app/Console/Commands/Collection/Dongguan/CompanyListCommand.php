<?php

namespace App\Console\Commands\Collection\Dongguan;

use App\Console\Commands\BaseCommand;
use App\Jobs\Dongguan\GetCompanyList;
use App\Service\JobStatusService;
use Illuminate\Support\Facades\Cache;
use QL\QueryList;

class CompanyListCommand extends BaseCommand
{
    /**
     * 任务调度的命令名称.
     * 采集企业列表页数和总数命令
     *
     * @var string
     */
    protected $signature = 'company_list_command';

    private $jobStatusService;

    /**
     * 说明.
     *
     * @var string
     */
    protected $description = '查询东莞公共资源交易中心企业列表页数和总数，每天凌晨00:00执行一次';


    public function __construct(JobStatusService $jobStatusService)
    {
        parent::__construct();
        $this->jobStatusService = $jobStatusService;
    }

    /**
     * 执行命令.
     *
     * @return mixed
     */
    public function handle()
    {
        $cur_time = date('y-m-d H:i:s',time());
        //读取昨天的采集主信息
        $yesTd_total_pages = 0;
        if (Cache::has('get_dg_main_info')) {
            $yesTd_get_dg_main_info = json_decode(Cache::get('get_dg_main_info'));
            $yesTd_total_pages = $yesTd_get_dg_main_info->company_pages->total_pages;
        }
        //QueryList采集开始
        $url = 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/list';
        $rules = ['total_pages' => ["script:eq(9)","html"]];
        $data = QueryList::Query($url,$rules)->data;
        //正则匹配总页数
        preg_match_all('/\d+/', $data[0]['total_pages'],$arr);
        //拿到企业信息总页数；
        $total_pages = (int)$arr[0][0];
        $total_records  = (int)$arr[0][1];
        if ( !$total_pages || !$total_records ){
            $this->jobStatusService->jobFail($this->signature,'采集不到企业总页数');
            $this->info($cur_time.' —— company_list_command,采集不到企业总页数');
            return true;
        }
        $get_dg_main_info = [
            'company_pages' => [
                'title'         => '采集企业列表，并采集企业信息',
                'url'           => 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/findListByPage?currentPage=',
                'desc'          => '获取每页企业信息,拿到远程企业ID传到下一个采集接口.',
                'total_pages'   => $total_pages,
                'total_records' => $total_records
            ],
            'company_info' => [
                'title'         => '采集企业资质ID和人才ID,并采集企业资质列表和人才列表',
                'url'           => 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/view?id=',
                'desc'          => '获取单个企业资质和人才列表ID,传到下一个采集接口.',
            ],
            'company_cert' => [
                'title'         => '采集企业单个资质',
                'url'           => 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/CreditSystem/Enterprise/CertfileList?fcCertfileid=',
                'desc'          => '获取单个资质信息.',
            ],
            'company_person' => [
                'title'         => '采集企业单个人才',
                'url'           => 'https://www.dgzb.com.cn/ggzy/website/WebPagesManagement/EnterPublic/EntRegView?id=',
                'desc'          => '获取单个人才信息.',
            ],
        ];
        Cache::put('get_dg_main_info', $get_dg_main_info, 1500);
        $update_pages = $total_pages - $yesTd_total_pages;
        //没有新页面或更新第一页
        if ( $update_pages == 0 || $update_pages == 1 ) {
            dispatch(new GetCompanyList(1));
            $this->jobStatusService->jobSuccess($this->signature,'采集第一页成功推送到job');
            $this->info($cur_time.' —— company_list_command采集第一页成功');
            return true;
        }
        for ($page = 1 ; $page <= $update_pages ; $page++ ){
            dispatch(new GetCompanyList($page));
            $this->jobStatusService->jobSuccess($this->signature,'采集第'.$page.'页成功推送到job');
        }
        $this->jobStatusService->jobSuccess($this->signature,'采集了'.$update_pages.'页成功推送到job');
        $this->info($cur_time.' —— company_list_command采集'.$update_pages.'页成功');
        return true;
    }
}
