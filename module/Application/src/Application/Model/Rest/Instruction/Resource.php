<?php

namespace Application\Model\Rest\Instruction;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use RuntimeException;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|Entity
     */
    public function update($data, $id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $lpa->document->instruction = (isset($data['instruction']) ? $data['instruction'] : null);

        $validation = $lpa->document->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if ($lpa->validate()->hasErrors()) {
            throw new RuntimeException('A malformed LPA object');
        }

        $this->updateLpa($lpa);

        return new Entity($lpa->document->instruction, $lpa);
    }
}
