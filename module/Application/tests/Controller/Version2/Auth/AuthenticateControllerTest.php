<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\AuthenticateController;
use DateTime;
use Mockery;
use Zend\Http\Header\HeaderInterface;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;

class AuthenticateControllerTest extends AbstractAuthControllerTest
{
    public function testAuthenticateActionWithToken()
    {
        $tokenReturnData = [
            'tokenExtended' => true,
            'userId'        => 'abcde12345',
            'expiresAt'     => 67890,
        ];

        $this->authenticationService->shouldReceive('withToken')
            ->with('abcde12345', true)
            ->andReturn($tokenReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully authenticated with a authToken', $tokenReturnData);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'authToken' => 'abcde12345',
        ]);

        /** @var JsonModel $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($tokenReturnData, $result->getVariables());
    }

    public function testAuthenticateActionWithTokenNoUpdate()
    {
        $tokenReturnData = [
            'tokenExtended' => false,
            'userId'        => 'abcde12345',
            'expiresAt'     => 67890,
        ];

        $this->authenticationService->shouldReceive('withToken')
            ->with('abcde12345', false)
            ->andReturn($tokenReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully authenticated with a authToken', $tokenReturnData);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'authToken' => 'abcde12345',
            'Update'    => 'false',
        ]);

        /** @var JsonModel $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($tokenReturnData, $result->getVariables());
    }

    public function testAuthenticateActionWithTokenFailed()
    {
        $this->authenticationService->shouldReceive('withToken')
            ->with('abcde12345', true)
            ->andReturn('Big big failure')
            ->once();

        $this->logger->shouldReceive('notice')
            ->with('Failed authentication attempt with a authToken', [
                'authToken' => 'abcde12345',
            ]);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'authToken' => 'abcde12345',
        ]);

        /** @var ApiProblem $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(401, $data['status']);
        $this->assertEquals('Big big failure', $data['detail']);
    }

    public function testAuthenticateActionWithPassword()
    {
        $passwordReturnData = [
            'userId'     => 'abcde12345',
            'last_login' => 12345,
            'expiresAt'  => 67890,
        ];

        $this->authenticationService->shouldReceive('withPassword')
            ->with('Username', 'P@55word', true)
            ->andReturn($passwordReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully authenticated with a password', $passwordReturnData);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'username' => 'Username',
            'password' => 'P@55word',
        ]);

        /** @var JsonModel $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($passwordReturnData, $result->getVariables());
    }

    public function testAuthenticateActionWithPasswordNoUpdate()
    {
        $passwordReturnData = [
            'userId'     => 'abcde12345',
            'last_login' => 12345,
            'expiresAt'  => 67890,
        ];

        $this->authenticationService->shouldReceive('withPassword')
            ->with('Username', 'P@55word', false)
            ->andReturn($passwordReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully authenticated with a password', $passwordReturnData);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'username' => 'Username',
            'password' => 'P@55word',
            'Update'   => 'false',
        ]);

        /** @var JsonModel $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($passwordReturnData, $result->getVariables());
    }

    public function testAuthenticateActionWithPasswordFailed()
    {
        $this->authenticationService->shouldReceive('withPassword')
            ->with('Username', 'Wr0ngP@55word', true)
            ->andReturn('Big big failure')
            ->once();

        $this->logger->shouldReceive('notice')
            ->with('Failed authentication attempt with a password', [
                'username' => 'Username',
            ]);

        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class, [
            'username' => 'Username',
            'password' => 'Wr0ngP@55word',
        ]);

        /** @var ApiProblem $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(401, $data['status']);
        $this->assertEquals('Big big failure', $data['detail']);
    }

    public function testAuthenticateActionFailedNoData()
    {
        /** @var AuthenticateController $controller */
        $controller = $this->getController(AuthenticateController::class);

        /** @var ApiProblem $result */
        $result = $controller->authenticateAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Either token or username & password must be passed', $data['detail']);
    }

    public function testSessionExpiryAction()
    {
        $header = Mockery::mock(HeaderInterface::class);
        $header->shouldReceive('getFieldValue')->once()->andReturn('asdfgh123456');

        $this->request->shouldReceive('getHeader')->withArgs(['CheckedToken'])->once()->andReturn($header);

        $this->authenticationService->shouldReceive('withToken')
            ->once()
            ->withArgs(['asdfgh123456', false])
            ->andReturn(['expiresIn' => 123,  'expiresAt' => new DateTime('2018-2-1 00:00:00')]);

        $controller = $this->getController(AuthenticateController::class);

        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['valid' => true, 'remainingSeconds' => 123], $result->getVariables());
    }

    public function testSessionExpiryActionExpired()
    {
        $header = Mockery::mock(HeaderInterface::class);
        $header->shouldReceive('getFieldValue')->once()->andReturn('asdfgh123456');

        $this->request->shouldReceive('getHeader')->withArgs(['CheckedToken'])->once()->andReturn($header);

        $this->authenticationService->shouldReceive('withToken')
            ->once()
            ->withArgs(['asdfgh123456', false])
            ->andReturn('token-has-expired');

        $controller = $this->getController(AuthenticateController::class);

        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['valid' => false, 'problem' => 'token-has-expired'], $result->getVariables());
    }

    public function testSessionExpiryActionTokenInvalid()
    {
        $header = Mockery::mock(HeaderInterface::class);
        $header->shouldReceive('getFieldValue')->once()->andReturn('asdfgh123456');

        $this->request->shouldReceive('getHeader')->withArgs(['CheckedToken'])->once()->andReturn($header);

        $this->authenticationService->shouldReceive('withToken')
            ->once()
            ->withArgs(['asdfgh123456', false])
            ->andReturn('invalid-token');

        $controller = $this->getController(AuthenticateController::class);

        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(['valid' => false, 'problem' => 'invalid-token'], $result->getVariables());
    }

    public function testSessionExpiryActionNoToken()
    {
        $this->request->shouldReceive('getHeader')->withArgs(['CheckedToken'])->once()->andReturn(null);

        $controller = $this->getController(AuthenticateController::class);

        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals([
            'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title' => 'Bad Request',
            'status' => 400,
            'detail' => 'No CheckedToken was specified in the header'], $result->toArray());
    }

    public function testSessionExpiryActionAuthenticationServiceApiProblem()
    {
        $header = Mockery::mock(HeaderInterface::class);
        $header->shouldReceive('getFieldValue')->once()->andReturn('asdfgh123456');

        $this->request->shouldReceive('getHeader')->withArgs(['CheckedToken'])->once()->andReturn($header);

        $this->authenticationService->shouldReceive('withToken')
            ->once()
            ->withArgs(['asdfgh123456', false])
            ->andReturn(new ApiProblem(500, 'Test problem'));

        $controller = $this->getController(AuthenticateController::class);

        $result = $controller->sessionExpiryAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertEquals((new ApiProblem(500, 'Test problem'))->toArray(), $result->toArray());
    }
}
