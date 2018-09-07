<?php
namespace Application\Model\DataAccess\Mongo\Collection;

use MongoDB\BSON\UTCDateTime as MongoDate;
use MongoDB\Collection as MongoCollection;
use Application\Model\DataAccess\Repository\User as UserRepository;

class AuthLogCollection extends AbstractCollection implements UserRepository\LogRepositoryInterface
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Add a document to the log collection.
     *
     * @param array $details
     * @return bool
     */
    public function addLog(array $details) : bool
    {
        // Map DateTimes to MongoDates
        $details = array_map(function ($v) {
            return ($v instanceof \DateTime) ? new MongoDate($v) : $v;
        }, $details);

        $result = $this->collection->insertOne($details);

        return ($result->getInsertedCount() == 1);
    }

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array
     */
    public function getLogByIdentityHash(string $identityHash) : ?array
    {
        $data = $this->collection->findOne([
            'identity_hash' => $identityHash
        ]);

        if (!is_array($data)) {
            return null;
        }

        // Map MongoDates to DateTimes
        $data = array_map(function ($v) {
            return ($v instanceof MongoDate) ? $v->toDateTime() : $v;
        }, $data);

        return $data;
    }
}
