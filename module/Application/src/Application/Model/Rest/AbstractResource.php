<?php
namespace Application\Model\Rest;

use RuntimeException;

use Application\Library\DateTime;

use Application\Model\Rest\Users\Entity as RouteUser;
use Opg\Lpa\DataModel\Lpa\Lpa;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use ZfcRbac\Exception\UnauthorizedException;
use ZfcRbac\Service\AuthorizationServiceAwareTrait;

abstract class AbstractResource implements ResourceInterface, ServiceLocatorAwareInterface {

    const TYPE_SINGULAR = 'singular';
    const TYPE_COLLECTION = 'collections';

    //------------------------------------------

    use ServiceLocatorAwareTrait;

    /**
     * Identity and authorization for the authenticated user. This could be Identity\Guest.
     */
    use AuthorizationServiceAwareTrait;

    //------------------------------------------

    protected $lpa = null;

    protected $routeUser = null;

    //------------------------------------------

    public function setRouteUser( RouteUser $user ){
        $this->routeUser = $user;
    }

    /**
     * @return RouteUser
     */
    public function getRouteUser(){
        if( !( $this->routeUser instanceof RouteUser ) ){
            throw new RuntimeException('Route User not set');
        }
       return $this->routeUser;
    }

    //--------------------------

    public function setLpa( Lpa $lpa ){
        $this->lpa = $lpa;
    }

    /**
     * @return Lpa
     */
    public function getLpa(){
        if( !( $this->lpa instanceof Lpa ) ){
            throw new RuntimeException('LPA not set');
        }
        return $this->lpa;
    }

    //--------------------------

    /**
     * @param $collection string Name of the requested collection.
     * @return \MongoCollection
     */
    public function getCollection( $collection ){
        return $this->getServiceLocator()->get( "MongoDB-Default-{$collection}" );
    }

    //--------------------------

    public function checkAccess( $userId = null ){

        if( is_null($userId) && $this->getRouteUser() != null ){
            $userId = $this->getRouteUser()->userId();
        }

        if (!$this->getAuthorizationService()->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this resource');
        }

        if ( !$this->getAuthorizationService()->isGranted('isAuthorizedToManageUser', $userId) ) {
            throw new UnauthorizedException('You do not have permission to access this resource');
        }

    } // function

    //------------------------------------------

    /**
     * Helper method for saving an updated LPA.
     *
     * @param Lpa $lpa
     */
    protected function updateLpa( Lpa $lpa ){

        // Should already have been checked, but no harm checking again.
        $this->checkAccess();

        //-----------------------------------------

        $collection = $this->getCollection('lpa');

        $lastUpdated = new \MongoDate( $lpa->updatedAt->getTimestamp(), (int)$lpa->updatedAt->format('u') );

        // Record the time we updated the document.
        $lpa->updatedAt = new DateTime();

        // updatedAt is included in the query so that data isn't overwritten
        // if the Document has changed since this process loaded it.
        $result = $collection->update(
            [ '_id'=>$lpa->id, 'updatedAt'=>$lastUpdated ],
            $lpa->toMongoArray(),
            [ 'upsert'=>false, 'multiple'=>false ]
        );

        // Ensure that one (and only one) document was updated.
        // If not, something when wrong.
        if( $result['nModified'] !== 1 ){
            throw new RuntimeException('Unable to update LPA. This might be because "updatedAt" has changed.');
        }

    } // function

    //------------------------------------------

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function create($data){}

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    #public function delete($id){}

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function deleteList($data){}

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    #public function fetch($id){}

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    #public function fetchAll($params = array()){}

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function patch($id, $data){}

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function replaceList($data){}

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    #public function update($id, $data){}

} // abstract class