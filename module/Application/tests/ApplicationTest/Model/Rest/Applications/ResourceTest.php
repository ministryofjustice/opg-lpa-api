<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Lock\LockedException;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use ZfcRbac\Service\AuthorizationService;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRouteUserException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Route User not set');
        $resource = new Resource();
        $resource->getRouteUser();
    }

    public function testSetLpa()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resource = new Resource();
        $resource->setLpa($pfLpa);
        $lpa = $resource->getLpa();
        $this->assertTrue($pfLpa === $lpa);
    }

    public function testGetLpaException()
    {
        $this->setExpectedException(\RuntimeException::class, 'LPA not set');
        $resource = new Resource();
        $resource->getLpa();
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals('collections', $resource->getType());
    }

    public function testFetchNotFound()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->build();

        $entity = $resource->fetch(-1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchHwLpa()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        
        $entity = $resource->fetch($lpa->id);
        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals($lpa, $entity->getLpa());

        $resourceBuilder->verify();
    }

    public function testFetchNotAuthenticated()
    {
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(false);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withAuthorizationService($authorizationServiceMock)
            ->build();

        $this->setExpectedException(UnauthorizedException::class, 'You need to be authenticated to access this resource');
        $resource->fetch(1);

        $resourceBuilder->verify();
    }

    public function testFetchMissingPermission()
    {
        $user = FixturesData::getUser();
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->with('isAuthorizedToManageUser', $user->id)
            ->andReturn(false);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser($user)
            ->withAuthorizationService($authorizationServiceMock)
            ->build();

        $this->setExpectedException(UnauthorizedException::class, 'You do not have permission to access this resource');
        $resource->fetch(1);

        $resourceBuilder->verify();
    }

    public function testCreateNullData()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withInsert(true)->build();

        /* @var Entity */
        $createdEntity = $resource->create(null);

        $this->assertNotNull($createdEntity);
        $this->assertGreaterThan(0, $createdEntity->lpaId());

        $resourceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $user = new User();
        $user->set('id', 3);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser($user)->build();

        //So we expect an exception and for no document to be inserted
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object was created');

        $resource->create(null);

        $resourceBuilder->verify();
    }

    public function testCreateFullLpa()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser($user)
            ->withLpa($lpa)
            ->withInsert(true)
            ->build();

        /* @var Entity */
        $createdEntity = $resource->create($lpa->toArray());

        $this->assertNotNull($createdEntity);
        //Id should be generated
        $this->assertNotEquals($lpa->id, $createdEntity->lpaId());
        $this->assertGreaterThan(0, $createdEntity->lpaId());
        //User should be reassigned to logged in user
        $this->assertEquals($user->id, $createdEntity->userId());

        $resourceBuilder->verify();
    }

    public function testCreateFilterIncomingData()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withInsert(true)
            ->build();

        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        /* @var Entity */
        $createdEntity = $resource->create($lpa->toArray());
        $createdLpa = $createdEntity->getLpa();

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $createdLpa->get('document'));
        $this->assertEquals($lpa->get('metadata'), $createdLpa->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $createdLpa->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $createdLpa->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $createdLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $createdLpa->get('updatedAt'));
        $this->assertNotEquals($lpa->get('startedAt'), $createdLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $createdLpa->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $createdLpa->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $createdLpa->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $createdLpa->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $createdLpa->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $createdLpa->get('seed'));

        $resourceBuilder->verify();
    }

    public function testPatchValidationError()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($pfLpa)
            ->build();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $validationError = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('document.type', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdateLpaValidationError()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($pfLpa)
            ->build();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $this->setExpectedException(\RuntimeException::class, 'LPA object is invalid');
        $resource->testUpdateLpa($lpa);

        $resourceBuilder->verify();
    }

    public function testPatchFullLpa()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity);
        //Id should be retained
        $this->assertEquals($lpa->id, $patchedEntity->lpaId());
        //User should not be reassigned to logged in user
        $this->assertEquals($lpa->user, $patchedEntity->userId());

        $resourceBuilder->verify();
    }

    public function testPatchLockedLpa()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withLocked(true)
            ->build();

        $this->setExpectedException(LockedException::class, 'LPA has already been locked.');
        $resource->patch($lpa->toArray(), $lpa->id);

        $resourceBuilder->verify();
    }

    public function testPatchSetCreatedDate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->createdAt = null;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $this->assertNull($lpa->createdAt);

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity->getLpa()->createdAt);

        $resourceBuilder->verify();
    }

    public function testPatchFilterIncomingData()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);
        $patchedLpa = $patchedEntity->getLpa();

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $patchedLpa->get('document'));
        $this->assertEquals($lpa->get('metadata'), $patchedLpa->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $patchedLpa->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $patchedLpa->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $patchedLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $patchedLpa->get('updatedAt'));
        $this->assertNotEquals($lpa->get('startedAt'), $patchedLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $patchedLpa->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $patchedLpa->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $patchedLpa->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $patchedLpa->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $patchedLpa->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $patchedLpa->get('seed'));

        $resourceBuilder->verify();
    }
}