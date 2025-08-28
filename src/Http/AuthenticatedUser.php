<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Represents an authenticated user. Containing all the user information, such as
 * user's detail, organization information, etc. Created to ease the access to
 * determine the user's identity, organization management and organization membership.
 */
final class AuthenticatedUser
{
    private array $member_of_organizations = [];
    private array $admin_of_organizations = [];

    public function __construct(
        private int $id,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function setMemberOfOrganizations(array $organizationIds): void
    {
        $this->member_of_organizations = $organizationIds;
    }

    public function setAdminOfOrganizations(array $organizationIds): void
    {
        $this->admin_of_organizations = $organizationIds;
    }

    public function isMemberOfOrganization(int $organizationId): bool
    {
        return in_array($organizationId, $this->member_of_organizations);
    }

    public function isAdminOfOrganization(int $organizationId): bool
    {
        return in_array($organizationId, $this->admin_of_organizations);
    }
}