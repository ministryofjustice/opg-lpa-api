<?php

namespace ApplicationTest\Model\Rest\AttorneyDecisionsPrimary;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\AttorneyDecisionsPrimary\Entity;
use Application\Model\Rest\AttorneyDecisionsPrimary\Resource as AttorneyDecisionsPrimaryResource;
use ApplicationTest\AbstractResourceTest;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var AttorneyDecisionsPrimaryResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new AttorneyDecisionsPrimaryResource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('lpaId', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('primary-attorney-decisions', $this->resource->getName());
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $this->resource->getType());
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetch();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        $primaryAttorneyDecisionsEntity = $resource->fetch();
        $this->assertEquals(new Entity($lpa->document->primaryAttorneyDecisions, $lpa), $primaryAttorneyDecisionsEntity);
        $resourceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->update(null, -1);
    }

    public function testUpdateValidationFailed()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure decisions are invalid
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $resource->update($decisions->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('how', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdateMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $resource->update(null, -1); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testUpdate()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $decisions = new PrimaryAttorneyDecisions();

        $primaryAttorneyDecisionsEntity = $resource->update($decisions->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($decisions, $lpa), $primaryAttorneyDecisionsEntity);

        $resourceBuilder->verify();
    }

    public function testPatchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->patch(null, -1);
    }

    public function testPatchValidationFailed()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getHwLpa())->build();

        //Make sure decisions are invalid
        $decisions = new PrimaryAttorneyDecisions();
        $decisions->set('how', 'invalid');

        $validationError = $resource->patch($decisions->toArray(), -1); //Id is ignored

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('how', $validation));

        $resourceBuilder->verify();
    }

    public function testPatchMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $decisions = new PrimaryAttorneyDecisions();
        $resource->patch($decisions->toArray(), -1); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testPatch()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $decisions = new PrimaryAttorneyDecisions();
        $decisions->canSustainLife = false;
        $decisions->when = 'no-capacity';
        $decisions->how = 'jointly-attorney-severally';
        $decisions->howDetails = 'test';

        $primaryAttorneyDecisionsEntity = $resource->patch($decisions->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($decisions, $lpa), $primaryAttorneyDecisionsEntity);

        $resourceBuilder->verify();
    }

    public function testPatchNullDecisionsOnLpa()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->document->primaryAttorneyDecisions = null;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $decisions = new PrimaryAttorneyDecisions();
        $decisions->canSustainLife = false;
        $decisions->when = 'no-capacity';
        $decisions->how = 'jointly-attorney-severally';
        $decisions->howDetails = 'test';

        $primaryAttorneyDecisionsEntity = $resource->patch($decisions->toArray(), -1); //Id is ignored

        $this->assertEquals(new Entity($decisions, $lpa), $primaryAttorneyDecisionsEntity);

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->delete();
    }

    public function testDeleteValidationFailed()
    {
        //LPA's document must be invalid
        $lpa = FixturesData::getHwLpa();
        $lpa->document->primaryAttorneys = [];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $validationError = $resource->delete();

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('whoIsRegistering', $validation));

        $resourceBuilder->verify();
    }

    public function testDeleteMalformedData()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        //So we expect an exception and for no document to be updated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object');

        $resource->delete(); //Id is ignored

        $resourceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $response = $resource->delete(); //Id is ignored

        $this->assertTrue($response);
        $this->assertNull($lpa->document->primaryAttorneyDecisions);

        $resourceBuilder->verify();
    }
}