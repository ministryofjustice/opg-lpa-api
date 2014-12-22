<?php
namespace Opg\Lpa\DataModel;

interface ValidatableInterface {

    /**
     * Validate the LPA.
     *
     * if $property is:
     *  - null: Validate all properties.
     *  - Array: Validate all properties listed in the array.
     *  - string: Validate the single named property.
     *
     * In all cases if a property's value implements Lpa\ValidatableInterface,
     * the validation request should be propagated.
     *
     * @param string|Array|null $property
     * @return \Opg\Lpa\DataModel\Validator\ValidatorResponse
     */
    public function validate($property);

} // interface
