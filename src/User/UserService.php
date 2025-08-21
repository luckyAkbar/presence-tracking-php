<?php
declare(strict_types=1);

namespace App\User;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * A service that allow sign up via 3rd party providers. Each
     * caller must ensure that the authId is unique, regardless of
     * the provider. If the authId is already present, the user data
     * is considered to be already present and will be returned.
     *
     * @param string $authId
     * @param string $email
     * @param string $username
     * @param bool $emailVerified
     * 
     * @return User
     */
    public function signUpVia3rdParty(string $authId, string $email, string $username, bool $emailVerified): User
    {
        $user = $this->userRepository->findByAuthId($authId);

        if ($user !== null) {
            return $user;
        }

        $createdUser = $this->userRepository->createUser($authId, $email, $username, $emailVerified);

        return $createdUser;
    }
}