<?php

/**
 * Created by PhpStorm.
 * User: chayton
 * Date: 2017/2/7
 * Time: 10:01
 */
namespace App\Service;

use App\Models\Cert;
use App\Models\CertAgency;

class CertService
{

    private $cert;
    private $agency;

    public function __construct(Cert $cert,CertAgency $agency)
    {
        $this->cert = $cert;
        $this->agency = $agency;
    }

    /*
     * 插入信息
     * */

    public function insertInfo($table,Array $insertData=array())
    {
        $types = array('cert','agency');
        if(!in_array($table,$types)){
            return false;
        }
        try{
            $model = null;
            switch($table){
                case 'cert':
                    $model = $this->cert;
                    break;
                case 'agency':
                    $model = $this->agency;
                    break;
            }
            $obj = $model->create($insertData);
            return $obj->id;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * @return Cert/Agency
     */
    public function getOneByWhere($table,Array $where,$field = Array('*'))
    {
        $types = ['cert','agency'];
        if ( !in_array($table,$types) ){
            return false;
        }
        try{
            $model = null;
            switch ($table){
                case 'cert':
                    $model = $this->cert;
                    break;
                case 'agency':
                    $model = $this->agency;
                    break;
            }
            $model = $model->select($field);
            foreach ($where as $key=>$value){
                $model = $model->where($key,$value);
            }
            return $model->first();
        }catch (\Exception $e){
            return false;
        }
    }

    //查询远程对应的本地资质分类ID
    public function getCertId($data)
    {
        if ( !$data ){
            return null;
        }
        //查询是否存在;
        $has = $this->getOneByWhere('cert',['name'=>$data],['id']);
        if ( !$has ){
            return null;
        }
        return $has->id;
    }

    //查询远程对应的本地颁发机构ID
    public function getAgencyId($data)
    {
        if ( !$data ){
            return null;
        }
        //查询是否存在;
        $has = $this->getOneByWhere('agency',['name'=>$data],['id']);
        if ( !$has ){
            return $this->insertInfo('agency',['name'=>$data]);
        }
        return $has->id;
    }

}