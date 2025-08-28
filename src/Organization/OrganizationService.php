<?php

declare(strict_types=1);

namespace App\Organization;

use App\Http\RequestContext;
use App\Organization\UnauthorizedAccessException;

final class OrganizationService
{
    public function __construct(
        private OrganizationsRepository $organizationsRepository
    ) {}

    public function registerNewOrganization(RequestContext $ctx, string $name, string $description): Organization
    {
        $requester = $ctx->getAuthenticatedUser();
        $requesterId = $requester->getId();
        if ($requester === null || $requesterId === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        if (empty($name) || empty($description)) {
            throw new \InvalidArgumentException('Name and description are required');
        }

        return $this->organizationsRepository->create($name, $description, $requesterId);
    }
}