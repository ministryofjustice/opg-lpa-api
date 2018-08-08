<?php

namespace AuthTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Password\Service as PasswordService;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Application\Model\DataAccess\Mongo\Collection\User;
use ApplicationTest\Model\Service\Password\ServiceBuilder;
use DateTime;
use Mockery;
use Mockery\MockInterface;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var MockInterface|AuthUserCollection
     */
    private $authUserCollection;

    /**
     * @var MockInterface|AuthenticationService
     */
    private $authenticationService;

    protected function setUp()
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->authUserCollection = Mockery::mock(AuthUserCollection::class);

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
    }

    public function testChangePasswordNullUser()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->changePassword(1, 'test', 'new');

        $this->assertEquals('user-not-found', $result);
    }

    public function testChangePasswordInvalidNewPassword()
    {
        $user = new User([]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->changePassword(1, 'test', 'invalid');

        $this->assertEquals('invalid-new-password', $result);
    }

    public function testChangePasswordInvalidUserCredentials()
    {
        $user = new User([
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT)
        ]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->changePassword(1, 'invalid', 'Password123');

        $this->assertEquals('invalid-user-credentials', $result);
    }

    public function testChangePasswordValid()
    {
        $user = new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT)
        ]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $this->authUserCollection->shouldReceive('setNewPassword')
            ->withArgs(function ($userId, $passwordHash) {
                return $userId === 1 && password_verify('Password123', $passwordHash);
            })->once();

        $this->authenticationService->shouldReceive('withPassword')
            ->withArgs(['test@test.com', 'Password123', true])->once()
            ->andReturn([]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->changePassword(1, 'valid', 'Password123');

        $this->assertEquals([], $result);
    }

    public function testGenerateTokenUserNotFound()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals('user-not-found', $result);
    }

    public function testGenerateTokenUserNotActivated()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            'active' => false,
            'activation_token' => 'unit_test_activation_token'
        ]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals(['activation_token' => 'unit_test_activation_token'], $result);
    }

    /**
     * Class value to use during verification below
     * @var string
     */
    private $tokenDetails;

    public function testGenerateTokenSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 1,
            'active' => true
        ]));

        $checkValue = false;

        $this->authUserCollection->shouldReceive('addPasswordResetToken')
            ->withArgs(function ($id, $token) use ($checkValue) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (PasswordService::TOKEN_TTL - 1) . ' seconds');

                return $id === 1 && strlen($token['token']) > 20
                    && $token['expiresIn'] === PasswordService::TOKEN_TTL
                    && $token['expiresAt'] > $expectedExpires;
            })->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testUpdatePasswordUsingTokenInvalidPassword()
    {
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->updatePasswordUsingToken('token', 'invalid');

        $this->assertEquals('invalid-password', $result);
    }

    public function testUpdatePasswordUsingTokenInvalidToken()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('invalid', null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->updatePasswordUsingToken('invalid', 'Password123');

        $this->assertEquals('invalid-token', $result);
    }

    public function testUpdatePasswordUsingTokenUpdateFailed()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User([]));

        $this->authUserCollection->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })
            ->once()
            ->andReturn(false);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(false, $result);
    }

    public function testUpdatePasswordUsingTokenUpdateSuccess()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User(['_id' => 1]));

        $this->authUserCollection->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(true);

        $this->authUserCollection->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserCollection($this->authUserCollection)
            ->withAuthenticationService($this->authenticationService)
            ->build();

        $result = $service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(true, $result);
    }

    /**
     * @param int $userId
     * @param User $user
     */
    private function setUserDataSourceGetByIdExpectation(int $userId, $user)
    {
        $this->authUserCollection->shouldReceive('getById')
            ->withArgs([$userId])->once()
            ->andReturn($user);
    }

    /**
     * @param string $username
     * @param User $user
     */
    private function setUserDataSourceGetByUsernameExpectation(string $username, $user)
    {
        $this->authUserCollection->shouldReceive('getByUsername')
            ->withArgs([$username])->once()
            ->andReturn($user);
    }

    /**
     * @param string $token
     * @param $user
     */
    private function setUserDataSourceGetByResetTokenExpectation(string $token, $user)
    {
        $this->authUserCollection->shouldReceive('getByResetToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }
}
