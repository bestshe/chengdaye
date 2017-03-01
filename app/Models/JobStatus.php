<?php
/**
 * Created by PhpStorm.
 * User: Chayton
 * Date: 2017/2/16
 * Time: 15:44
 */
namespace App\Model;

use DB;
use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model{

    protected $table='job_status';

    protected $attribute = ['job_name','success','data','update_at'];

    /**
     * 保存Job执行状态
     * @author Chayton
     *
     * @param $jobName
     * @param $success
     * @param $data
     * @return bool
     */
    public function saveStatus($jobName,$success,$data){
        try{
            $data = array(
                'job_name'=>$jobName,
                'success'=>$success,
                'data'=>$data,
                'create_time'=>time()
            );
            DB::table($this->table)->insert($data);

            return true;
        }catch (\Exception $e){
            return false;
        }
    }
}