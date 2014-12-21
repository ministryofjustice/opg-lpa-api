<?php
namespace Application\Model\Rest\NotifiedPeople;

use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;

use Application\Model\Rest\AbstractResource;

use Zend\Paginator\Adapter\Null as PaginatorNull;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'resourceId'; }
    public function getName(){ return 'notified-people'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }

    /**
     * Create a new Attorney.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        $person = new NotifiedPerson( $data );

        /**
         * If the client has not passed an id, set it to max(current ids) + 1.
         * The array is seeded with 0, meaning if this is the first attorney the id will be 1.
         */
        if( is_null($person->id) ){

            $ids = array( 0 );
            foreach( $lpa->document->peopleToNotify as $a ){ $ids[] = $a->id; }
            $person->id = (int)max( $ids ) + 1;

        } // if

        //---


        $lpa->document->peopleToNotify[] = $person;

        $this->updateLpa( $lpa );

        return new Entity( $person, $lpa );

    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch($id){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        foreach( $lpa->document->peopleToNotify as $person ){
            if( $person->id == (int)$id ){
                return new Entity( $person, $lpa );
            }
        }

        return new ApiProblem( 404, 'Document not found' );

    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return Collection
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetchAll($params = array()){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        $count = count($lpa->document->peopleToNotify);

        // If there are no records, just return an empty paginator...
        if( $count == 0 ){
            return new Collection( new PaginatorNull, $lpa );
        }

        //---

        $collection = new Collection( new PaginatorArrayAdapter( $lpa->document->peopleToNotify ), $lpa );

        // Always return all attorneys on one page.
        $collection->setItemCountPerPage($count);

        //---

        return $collection;


    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|Entity
     */
    public function update($data, $id){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();
        $document = $lpa->document;

        foreach( $document->peopleToNotify as $key=>$person ) {

            if ($person->id == (int)$id) {

                $person = new NotifiedPerson( $data );

                $person->id = (int)$id;

                $document->peopleToNotify[$key] = $person;

                $this->updateLpa( $lpa );

                return new Entity( $person, $lpa );

            } // if

        } // foreach

        return new ApiProblem( 404, 'Document not found' );

    } // function

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|bool
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function delete($id){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();
        $document = $lpa->document;

        foreach( $document->peopleToNotify as $key=>$person ){

            if( $person->id == (int)$id ){

                // Remove the entry...
                unset( $document->peopleToNotify[$key] );

                //---

                $validation = $document->validate();

                if( $validation->hasErrors() ){
                    return new ValidationApiProblem( $validation );
                }

                //---

                if( $lpa->validate()->hasErrors() ){

                    /*
                     * This is not based on user input (we already validated the Document above),
                     * thus if we have errors here it is our fault!
                     */
                    throw new RuntimeException('A malformed LPA object');

                }

                $this->updateLpa( $lpa );

                return true;

            } // if

        } // foreach

        return new ApiProblem( 404, 'Document not found' );

    } // function

} // class