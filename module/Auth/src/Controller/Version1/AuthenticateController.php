<?php

namespace Auth\Controller\Version1;

use Opg\Lpa\Logger\LoggerTrait;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class AuthenticateController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * @return JsonModel|ApiProblemResponse
     */
    public function indexAction()
    {
        $params = $this->getRequest()->getPost();

        $updateToken = ( isset($params['Update']) && $params['Update'] === 'false' ) ? false : true;

        if (isset($params['Token'])) {
            return $this->withToken(trim($params['Token']), $updateToken);
        } elseif (isset($params['Username']) && isset($params['Password'])) {
            return $this->withPassword(trim($params['Username']), $params['Password'], $updateToken);
        } else {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Either Token or Username & Password must be passed')
            );
        }
    }

    /**
     * Deletes a token.
     */
    public function deleteAction()
    {
        $token = $this->params('token');

        if (!empty($token)) {
            $this->authenticationService->deleteToken($token);
        }

        // Return 204 - No Content
        $this->response->setStatusCode(204);
    }

    /**
     * Authenticate a user with a passed token.
     *
     * @param $token
     * @param $updateToken
     * @return JsonModel|ApiProblemResponse
     */
    private function withToken($token, $updateToken)
    {
        $result = $this->authenticationService->withToken($token, $updateToken);

        if (is_string($result)) {
            $this->getLogger()->notice("Failed authentication attempt with a token", [
                'token' => $token
            ]);

            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof \DateTime) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a token", [
            'tokenExtended' => (bool)$updateToken,
            'userId'=>$result['userId'],
            'expiresAt'=>$result['expiresAt'],
        ]);

        return new JsonModel($result);
    }

    /**
     * Authenticate a user with a passed usernamer (email address) and password.
     *
     * @param $username
     * @param $password
     * @param $updateToken
     * @return JsonModel|ApiProblemResponse
     */
    private function withPassword($username, $password, $updateToken)
    {
        $result = $this->authenticationService->withPassword($username, $password, $updateToken);

        if (is_string($result)) {
            $this->getLogger()->notice("Failed authentication attempt with a password", [
                'username' => $username
            ]);

            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a password", [
            'userId'=>$result['userId'],
            'last_login'=>$result['last_login'],
            'expiresAt'=>$result['expiresAt'],
        ]);

        return new JsonModel($result);
    }
}