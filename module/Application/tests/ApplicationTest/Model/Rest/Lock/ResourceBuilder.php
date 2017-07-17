<?php

namespace ApplicationTest\Model\Rest\Lock;

use Application\Model\Rest\Lock\Resource as LockResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return LockResource
     */
    public function build()
    {
        $resource = new LockResource();
        parent::buildMocks($resource);
        return $resource;
    }
}