<?php/** * Created by PhpStorm. * User: chayton * Date: 2017/1/30 * Time: 下午2:55 */namespace App\Service\DGJY;use App\Models\DGJY\Person;use App\Models\DGJY\PersonCert;use App\Models\Specialty;class PersonService{    private $person;    private $personCert;    private $specialty;    public function __construct(Person $person,PersonCert $personCert,Specialty $specialty)    {        $this->person = $person;        $this->personCert = $personCert;        $this->specialty = $specialty;    }    /**     * @return person/personCert     */    public function getOneByWhere($table,Array $where,$field = Array('*'))    {        $types = ['person','cert','specialty'];        if ( !in_array($table,$types) ){            return false;        }        try{            $model = null;            switch ($table){                case 'person':                    $model = $this->person;                    break;                case 'cert':                    $model = $this->personCert;                    break;                case 'specialty':                    $model = $this->specialty;                    break;            }            $model = $model->select($field);            foreach ($where as $key=>$value){                $model = $model->where($key,$value);            }            return $model->first();        }catch (\Exception $e){            return false;        }    }    /*     * 插入人才信息     * */    public function insertInfo($table,Array $insertData=array())    {        $types = array('person','cert','specialty');        if(!in_array($table,$types)){            return false;        }        try{            $model = null;            switch($table){                case 'person':                    $model = $this->person;                    break;                case 'cert':                    $model = $this->personCert;                    break;                case 'specialty':                    $model = $this->specialty;                    break;            }            $insertData['created_at'] = time();            $insertData['updated_at'] = time();            $obj = $model->create($insertData);            return $obj->id;        }catch (\Exception $e){            return false;        }    }    /*     * 更新人才信息     * */    public function UpdateByWhere($table,Array $where,Array $updateData=array())    {        $types = array('person','cert');        if(!in_array($table,$types)){            return false;        }        try{            $model = null;            switch($table){                case 'person':                    $model = $this->person;                    break;                case 'cert':                    $model = $this->personCert;                    break;            }            foreach($where as $key=>$value){                $model = $model->where($key,$value);            }            $updateData['updated_at'] = time();            $model->update($updateData);            return true;        }catch (\Exception $e){            return false;        }    }    //查询远程对应的本地专业ID    public function LocalSpecialtyId($cert_id,$specialty){        if ( !$specialty ){            return null;        }        if ($cert_id == 4 or $cert_id == 5 or $cert_id == 2 or $cert_id == 3 ){            $result = '';            //查询是否存在;            $specialty = explode(',',$specialty);            foreach ($specialty as $v){                $has = $this->getOneByWhere('specialty',['name'=>$v,'cert_id'=>$cert_id],['id']);                if ( !$has ){                    $id = $this->insertInfo('specialty',['name'=>$v,'cert_id'=>$cert_id]);                    $result .= ','.$id;                }else{                    $result .= ','.$has->id;                }            }            $result = substr_replace($result,'',0,1);            return $result;        }        return null;    }}