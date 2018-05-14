<?php

namespace Auth\Controller\Version1;

use Auth\Model\Service\AuthenticationService;
use Zend\Http\Request as HttpRequest;
use Zend\Stdlib\RequestInterface as Request;

abstract class AbstractAuthenticatedController extends AbstractController
{
    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    protected function authenticateUserToken(Request $request, $userId, bool $extendToken = false)
    {
        if (($request instanceof HttpRequest) === false) {
            return false;
        }

        /** @var HttpRequest $request */
        $token = $request->getHeader('Token');

        if ($token === false) {
            // Header was not passed.
            return false;
        }

        $result = $this->authenticationService->withToken($token->getFieldValue(), $extendToken);

        if (!is_array($result) || !isset($result['userId']) || $result['userId'] !== $userId) {
            //Token does not match userId
            return false;
        }

        return true;
    }
}