<?php

namespace ApplicationTest\Model\Service\AttorneyDecisionsPrimary;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\AttorneyDecisionsPrimary\Service;
use ApplicationTest\AbstractServiceTest;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var Service
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new Service(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->service->setLogger($this->logger);

        $this->service->setAuthorizationService($this->authorizationService);
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure decisions are invalid
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $service->update($decisions->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('how', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $service->update(null, -1); //Id is ignored

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $decisions = new PrimaryAttorneyDecisions();

        $primaryAttorneyDecisionsEntity = $service->update($decisions->toArray(), -1); //Id is ignored

        $this->assertEquals(new DataModelEntity($decisions), $primaryAttorneyDecisionsEntity);

        $serviceBuilder->verify();
    }
}