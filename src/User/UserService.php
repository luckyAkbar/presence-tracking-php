<?php
declare(strict_types=1);

namespace App\User;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * A service that allows sign up via 3rd party providers.
     * The email from the auth provider (Auth0, Twitter, etc.) is used as the unique identifier.
     * If a user with the same email already exists, their profile data is updated and returned.
     *
     * @param string $email
     * @param string $username
     * @param bool $emailVerified
     * 
     * @return User
     */
    public function signUpVia3rdParty(string $email, string $username, bool $emailVerified): User
    {
        $existingUser = $this->userRepository->findByEmail($email);

        if ($existingUser !== null) {
            return $existingUser;
        }

        // Create new user
        return $this->userRepository->createUser($email, $username, $emailVerified);
    }
    
    /**
     * Find a user by their email address (using encrypted lookup)
     *
     * @param string $email The email address to search for
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}