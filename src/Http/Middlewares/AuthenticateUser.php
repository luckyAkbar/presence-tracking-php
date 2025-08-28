<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\RequestContext;
use App\Auth0\Auth0Service;
use App\User\UserRepository;
use App\Http\AuthenticatedUser;

final class AuthenticateUser
{
    public function __construct(
        private Auth0Service $auth0Service,
        private UserRepository $userRepository
    )
    {
    }

    /**
     * Invoke the next handler, passing RequestContext when it accepts a parameter.
     */
    private function invokeNext(callable $next, RequestContext $ctx)
    {
        $closure = \Closure::fromCallable($next);
        $ref = new \ReflectionFunction($closure);
        if ($ref->getNumberOfParameters() >= 1) {
            return $next($ctx);
        }
        return $next();
    }

    /**
     * Run the middleware. If the user is not authenticated, will
     * reject the request with a 401 status code.
     * 
     * @param callable $next
     * @return mixed
     */
    public function mustAuthenticate(callable $next)
    {
        $ctx = new RequestContext();

        $requester = $this->auth0Service->auth()->getUser();
        
        if (!$requester || !isset($requester['email'])) {
            http_response_code(401);
            header('Content-Type: application/json');

            echo  json_encode([
                'error' => 'Unauthorized',
                'message' => 'missing user credentials',
            ]);
            return;
        }

        $user = $this->userRepository->findByEmail($requester['email']);
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json');

            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'User not found',
            ]);
            return;
        }
        
        $user_id = $user->getId();
        $authenticatedUser = new AuthenticatedUser($user_id);

        $organizationIds = $this->userRepository->getUserOrganizationsMembership($user_id);
        if ($organizationIds !== null) {
            $authenticatedUser->setMemberOfOrganizations($organizationIds);
        }

        $adminOrganizationIds = $this->userRepository->getUserOrganizationsAdministrative($user_id);
        if ($adminOrganizationIds !== null) {
            $authenticatedUser->setAdminOfOrganizations($adminOrganizationIds);
        }

        $ctx->setAuthenticatedUser($authenticatedUser);
        return $this->invokeNext($next, $ctx);
    }
}