<?php
namespace Application\Library\Hal;

use RuntimeException;

use Application\Model\Rest\EntityInterface;

/**
 * Hal document representing a Application\Model\Rest\EntityInterface
 *
 * Class Entity
 * @package Application\Library\Hal
 */
class Entity extends Hal {

    protected $entity;

    private $linksSet = false;

    public function __construct( EntityInterface $entity ){
        $this->setEntity( $entity );
    }

    public function setEntity( EntityInterface $entity ){
        $this->linksSet = false;
        $this->entity = $entity;
        $this->setData( $entity->toArray() );
    }

    //---

    public function getLinks(){

        if( !$this->linksSet ){ throw new RuntimeException( 'Cannot return links until they have been set.' ); }

        return parent::getLinks();

    }

    //---

    /**
     * Apply the links using the passed route generator.
     *
     * @param callable $routeCallback
     */
    public function setLinks( callable $routeCallback ){

        if( $this->entity instanceof \Application\Model\Rest\Users\Entity ) {

            $this->setUri( call_user_func($routeCallback, 'api-v1', $this->entity) );

        } elseif( $this->entity instanceof \Application\Model\Rest\Applications\Entity ){

            $this->setUri( call_user_func($routeCallback, 'api-v1/level-1', $this->entity) );
            $this->addLink( 'user', call_user_func($routeCallback, 'api-v1', $this->entity) );

        } else {

            $this->setUri( call_user_func($routeCallback, 'api-v1/level-2', $this->entity) );
            $this->addLink( 'user', call_user_func($routeCallback, 'api-v1', $this->entity) );
            $this->addLink( 'application', call_user_func($routeCallback, 'api-v1/level-1', $this->entity) );

        }

        //---

        $this->linksSet = true;

    } // function

} // class