<?php

namespace ApplicationTest\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\InsertOneResult;

class AuthLogCollectionTest extends MockeryTestCase
{
    /**
     * @var AuthLogCollection
     */
    private $authLogCollection;

    /**
     * @var MockInterface|Collection
     */
    private $mongoCollection;

    protected function setUp()
    {
        $this->mongoCollection = Mockery::mock(Collection::class);

        $this->authLogCollection = new AuthLogCollection($this->mongoCollection);
    }

    public function testAddLogFalse()
    {
        $log = [
            'message' => 'Unit test',
            'date' => new DateTime()
        ];

        $dbResult = Mockery::mock(InsertOneResult::class);
        $dbResult->shouldReceive('getInsertedCount')->once()->andReturn(0);

        $this->mongoCollection->shouldReceive('insertOne')->withArgs(function ($details) {
            $date = $details['date'];
            return $details['message'] === 'Unit test' && $date instanceof UTCDateTime
                && $date->toDateTime() >= new DateTime('-1 second');
        })->once()->andReturn($dbResult);

        $result = $this->authLogCollection->addLog($log);

        $this->assertEquals(false, $result);
    }

    public function testAddLogTrue()
    {
        $log = [
            'message' => 'Unit test'
        ];

        $dbResult = Mockery::mock(InsertOneResult::class);
        $dbResult->shouldReceive('getInsertedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('insertOne')->withArgs([$log])->once()
            ->andReturn($dbResult);

        $result = $this->authLogCollection->addLog($log);

        $this->assertEquals(true, $result);
    }

    public function testGetLogByIdentityHashNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['identity_hash' => 'unit-test']])->once()
            ->andReturn(null);

        $result = $this->authLogCollection->getLogByIdentityHash('unit-test');

        $this->assertEquals(null, $result);
    }

    public function testGetLogByIdentityHash()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['identity_hash' => 'unit-test']])->once()
            ->andReturn(['message' => 'Unit test']);

        $result = $this->authLogCollection->getLogByIdentityHash('unit-test');

        $this->assertEquals(['message' => 'Unit test'], $result);
    }
}