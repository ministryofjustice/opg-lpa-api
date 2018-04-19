<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\Stats\Resource;
use ZF\ApiProblem\ApiProblem;

class StatsController extends AbstractController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'type';

    /**
     * @param Resource $resource
     */
    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $result = $this->resource->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $resultData = $result->toArray();

            if (empty($resultData)) {
                return new NoContentResponse();
            }

            return new JsonResponse($resultData);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
