<?php
namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Opg\Lpa\DataModel\Validator\Constraints\DateTimeUTC;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends AbstractData {

    /**
     * @var \DateTime A date of birth. The time component of the DateTime object should be ignored.
     */
    protected $date;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        // As there is only 1 property, include NotBlank as there is no point this object existing without it.

        $metadata->addPropertyConstraints('date', [
            new Assert\NotBlank,
            new DateTimeUTC(),
            new Assert\LessThanOrEqual( [ 'value' => new \DateTime('today') ] ),
        ]);

    }

    //------------------------------------------------

    /**
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map( $property, $v ){

        switch( $property ){
            case 'date':
                return ($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime( $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function

} // class
