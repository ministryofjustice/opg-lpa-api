<?php

namespace Application\Model\Service\Email;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollectionTrait;
use Application\Model\DataAccess\Mongo\Collection\User;
use Application\Model\Service\AbstractService;
use Zend\Math\BigInteger\BigInteger;
use DateTime;
use RuntimeException;

class Service extends AbstractService
{
    use AuthUserCollectionTrait;

    const TOKEN_TTL = 86400; // 24 hours

    //-------------

    public function generateToken($userId, $newEmail)
    {

        $validator = new \Zend\Validator\EmailAddress();

        if (!$validator->isValid($newEmail)) {
            return 'invalid-email';
        }

        $user = $this->authUserCollection->getById($userId);

        $userWithRequestedEmailAddress = $this->authUserCollection->getByUsername($newEmail);

        if ($userWithRequestedEmailAddress instanceof User) {
            if ($userWithRequestedEmailAddress->id() == $user->id()) {
                return 'username-same-as-current';
            } else {
                return 'username-already-exists';
            }
        }

        if (!$user instanceof User) {
            return 'user-not-found';
        }

        $token = openssl_random_pseudo_bytes(16, $strong);

        if ($strong !== true) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Unable to generate a strong token');
            // @codeCoverageIgnoreEnd
        }

        // Use base62 for shorter tokens
        $token = BigInteger::factory('bcmath')->baseConvert(bin2hex($token), 16, 62);

        $expires = new DateTime("+" . self::TOKEN_TTL . " seconds");

        $tokenDetails = [
            'token' => $token,
            'expiresIn' => self::TOKEN_TTL,
            'expiresAt' => $expires
        ];

        $this->authUserCollection->addEmailUpdateTokenAndNewEmail($user->id(), $tokenDetails, $newEmail);

        return $tokenDetails;
    }

    /**
     * @param $token
     * @return \Application\Model\DataAccess\Repository\Auth\UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken($token)
    {
        return $this->authUserCollection->updateEmailUsingToken($token);
    }
}