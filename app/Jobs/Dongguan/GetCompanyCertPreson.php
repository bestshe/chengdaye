<?php

namespace App\Jobs\Dongguan;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use QL\QueryList;
use Cache,DB,Log;

class GetCompanyCertPreson implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $page;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
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
}
