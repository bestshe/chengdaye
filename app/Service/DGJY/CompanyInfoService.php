<?php/** * Created by PhpStorm. * User: chayton * Date: 2017/1/30 * Time: 下午2:55 */namespace App\Service\DGJY;use App\Models\DGJY\Collect;use App\Models\DGJY\CompanyInfo;class CompanyInfoService{    private $companyInfo;    private $collect;    public function __construct(CompanyInfo $companyInfo,Collect $collect)    {        $this->companyInfo = $companyInfo;        $this->collect = $collect;    }    /*     * 插入公司信息     * */    public function insertInfo($table,Array $insertData=array())    {        $types = array('companyinfo','collect');        if(!in_array($table,$types)){            return false;        }        try{            $model = null;            switch($table){                case 'companyinfo':                    $model = $this->companyInfo;                    break;                case 'collect':                    $model = $this->collect;                    break;            }            $insertData['created_at'] = time();            $insertData['updated_at'] = time();            $obj = $model->create($insertData);            return $obj->id;        }catch (\Exception $e){            return false;        }    }    /*     * 更新公司信息     * */    public function UpdateByWhere($table,Array $where,Array $updateData=array())    {        $types = array('companyinfo','collect');        if(!in_array($table,$types)){            return false;        }        try{            $model = null;            switch($table){                case 'companyinfo':                    $model = $this->companyInfo;                    break;                case 'collect':                    $model = $this->collect;                    break;            }            foreach($where as $key=>$value){                $model = $model->where($key,$value);            }            $updateData['updated_at'] = time();            $model->update($updateData);            return true;        }catch (\Exception $e){            return false;        }    }    /**     * 根据条件获取指定对象的一个实例     * @author chayton     * @param String $table 对象类型：contract     * @param Array $where 条件数组 ['key'=>'value','in'=>['key',array()],'lt'=>['key','value'],'gt'=>['key','value']]     * @return bool     */    public function getByWhere($table,Array $where,$fields=array('*'),$result_type = 1){        $types = array('companyinfo','collect');        if(!in_array($table,$types)){            return false;        }        try {            $model = null;            switch($table){                case 'companyinfo':                    $model = $this->companyInfo;                    break;                case 'collect':                    $model = $this->collect;                    break;            }            $model = $model->select($fields);            foreach($where as $key=>$value){                $model = $model->where($key,$value);            }            if ( $result_type ){                return $model->orderBy('id','desc')->first();            }            return $model->orderBy('id','desc')->get();        }catch (\Exception $e){            return false;        }    }    /*     * 查询需要更新采集的企业     * */    public function collectEnt($Rtable,$Rwhere,$where,$fields)    {        return $this->collect->getRelate($Rtable,$Rwhere,$where,$fields);    }    /**     * 标记采集企业是否更新或新的企业信息，下一级采集标记符     * @param $get_id | int 本地入库get id     * @param $remote_id | string 远程信息ID     * @param $remote_id_type | int  远程信息分类 ent:1, cert:2, person:3     * @param $is_get | int 是否采集 1:是, 0:否     * @return     */    public function markCollect($get_id,$remote_id,$remote_id_type,$is_get = 1)    {        //查询是否存在记录        $where = ['get_id'=>$get_id,'remote_id'=>$remote_id,'remote_id_type'=>$remote_id_type];        $has_collect = $this->getByWhere('collect',$where,['id']);        if ( $has_collect === false ){            return false;        }        //没有就插入新的记录        if ( !$has_collect ){            $insertData = ['get_id'=>$get_id,'remote_id'=>$remote_id,'remote_id_type'=>$remote_id_type,'isget'=>$is_get];            return $this->insertInfo('collect',$insertData);        }        return $this->UpdateByWhere('collect',['id'=>$has_collect->id],['isget'=>$is_get]);    }}