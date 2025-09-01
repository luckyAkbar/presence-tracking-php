<?php
declare(strict_types=1);

namespace App\Container;

use App\Http\Controllers\Auth0Controller;
use App\Auth0\Auth0Service;
use App\User\UserRepository;
use App\Http\Middlewares\AuthenticateUser;
use App\Http\Controllers\OrganizationController;
use App\Organization\OrganizationService;
use App\Organization\OrganizationsRepository;
use App\Http\Controllers\InvitationController;
use App\Invitation\InvitationsService;
use App\Invitation\InvitationsRepository;
use App\Support\Transaction;
use App\Organization\OrganizationMemberRepository;
use App\Invitation\InvitationQueryService;

/**
 * Container Helper - Provides IDE-friendly container access
 * This class provides typed methods for container resolution
 */
final class ContainerHelper
{
    public function __construct(
        private Container $container
    ) {}

    // Controllers
    public function auth0Controller(): Auth0Controller
    {
        return $this->container->make(Auth0Controller::class);
    }

    public function organizationController(): OrganizationController
    {
        return $this->container->make(OrganizationController::class);
    }

    public function invitationController(): InvitationController
    {
        return $this->container->make(InvitationController::class);
    }

    // Services
    public function organizationService(): OrganizationService
    {
        return $this->container->make(OrganizationService::class);
    }

    public function invitationService(): InvitationsService
    {
        return $this->container->make(InvitationsService::class);
    }

    // Repositories
    public function organizationRepository(): OrganizationsRepository
    {
        return $this->container->make(OrganizationsRepository::class);
    }

    public function invitationRepository(): InvitationsRepository
    {
        return $this->container->make(InvitationsRepository::class);
    }

    public function organizationMemberRepository(): OrganizationMemberRepository
    {
        return $this->container->make(OrganizationMemberRepository::class);
    }

    // Transaction
    public function transaction(): Transaction
    {
        return $this->container->make(Transaction::class);
    }

    // Query Services
    public function invitationQueryService(): InvitationQueryService
    {
        return $this->container->make(InvitationQueryService::class);
    }

    /**
     * Get Auth0Service instance
     */
    public function auth0Service(): Auth0Service
    {
        return $this->container->make(Auth0Service::class);
    }

    /**
     * Get UserRepository instance
     */
    public function userRepository(): UserRepository
    {
        return $this->container->make(UserRepository::class);
    }

    /**
     * Get AuthenticateUser instance
     */
    public function authenticateUser(): AuthenticateUser
    {
        return $this->container->make(AuthenticateUser::class);
    }

    /**
     * Get any service by class name (fallback method)
     * 
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }
}
