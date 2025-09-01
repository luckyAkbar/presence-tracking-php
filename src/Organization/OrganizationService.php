<?php

declare(strict_types=1);

namespace App\Organization;

use App\Http\RequestContext;
use App\Organization\UnauthorizedAccessException;
use App\Support\Transaction;
use PDO;

final class OrganizationService
{
    public function __construct(
        private OrganizationsRepository $organizationsRepository,
        private OrganizationMemberRepository $organizationMemberRepository,
        private Transaction $transaction
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

        $organization = $this->transaction->executeInTransaction(function(PDO $pdo) use ($name, $description, $requesterId) {
            $createdOrg = $this->organizationsRepository->create($name, $description, $requesterId, $pdo);

            if ($createdOrg === null) {
                throw new \Exception('Failed to create organization');
            }

            $this->organizationsRepository->createOrganizationAdmin($createdOrg->getId(), $requesterId, $pdo);
            $this->organizationMemberRepository->create($createdOrg->getId(), $requesterId, $pdo);

            return $createdOrg;
        });

        if ($organization === null) {
            throw new \Exception('Failed to create organization');
        }

        return $organization;
    }
}