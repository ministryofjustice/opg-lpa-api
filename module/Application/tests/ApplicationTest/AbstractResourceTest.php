<?php

namespace ApplicationTest;

use Application\Library\Authorization\UnauthorizedException;
use Mockery;
use OpgTest\Lpa\DataModel\FixturesData;
use ZfcRbac\Service\AuthorizationService;

abstract class AbstractResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param AbstractResourceBuilder $resourceBuilder
     * @return \Application\Model\Rest\AbstractResource
     */
    protected function setUpCheckAccessTest(AbstractResourceBuilder $resourceBuilder)
    {
        $authorizationService = Mockery::mock(AuthorizationService::class);

        $authorizationService->shouldReceive('isGranted')
                             ->andReturn(false)
                             ->once();

        $resource = $resourceBuilder->withUser(FixturesData::getUser())
                                    ->withAuthorizationService($authorizationService)
                                    ->build();

        //Should not be authorised for any Resource method
        $this->setExpectedException(UnauthorizedException::class);

        return $resource;
    }
}