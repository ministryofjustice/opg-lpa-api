<?php
namespace Application\Model\Rest\AttorneyDecisionsPrimary;

use Application\Model\Rest\EntityInterface;

use Application\Model\Rest\Users\Entity as UsersEntity;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\AccessorInterface as LpaAccessorInterface;

use Application\Library\Hal\Hal;

class Entity implements EntityInterface {

    protected $lpa;
    protected $decisions;

    public function __construct( PrimaryAttorneyDecisions $decisions = null, Lpa $lpa ){

        $this->lpa = $lpa;
        $this->decisions = $decisions;

    }

    public function userId(){
        return $this->lpa->user;
    }

    public function lpaId(){
        return $this->lpa->id;
    }

    public function resourceId(){
        return null;
    }

    public function toArray(){

        if( $this->decisions instanceof LpaAccessorInterface ){
            return $this->decisions->toArray();
        } else {
            return array();
        }

    }

} // class