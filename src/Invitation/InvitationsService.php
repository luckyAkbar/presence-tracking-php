<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Http\RequestContext;
use App\ServiceExceptions\InvalidArgumentException;
use App\ServiceExceptions\ResourceNotFoundException;
use App\User\UserRepository;
use App\ServiceExceptions\UnauthorizedAccessException;
use App\ServiceExceptions\ForbiddenAccessException;

final class InvitationsService
{
    public function __construct(
        private InvitationsRepository $invitationsRepository,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Create an invitation for a user to join an organization. All invitation
     * statuses created from this method will be set to 'pending'.
     * 
     * @param RequestContext $ctx
     * @param string $target_email
     * @param int $organization_id
     * @return Invitation
     * @throws UnauthorizedAccessException
     * @throws \Exception
     */
    public function createNewInvitation(RequestContext $ctx, string $target_email, int $organization_id): Invitation
    {
        $requester = $ctx->getAuthenticatedUser();
        if ($requester === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        $requesterId = $requester->getId();
        if ($requesterId === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        if (!$requester->isAdminOfOrganization($organization_id)) {
            throw new ForbiddenAccessException('Organization member invitation can only be created by an admin of the organization');   
        }

        if (filter_var($target_email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address');
        }

        $targetUser = $this->userRepository->findByEmail($target_email);
        if ($targetUser === null) {
            throw new ResourceNotFoundException('Target user with email ' . $target_email . ' not found');
        }

        $invitation_expired_at = new \DateTime('+14 days');
        $invitation_status = 'pending';

        $invitation = $this->invitationsRepository->create(
            $targetUser->getId(),
            $organization_id,
            $requesterId,
            $invitation_status,
            $invitation_expired_at
        );
        if ($invitation === null) {
            throw new \Exception('Failed to create invitation');
        }

        return $invitation;
    }

    public function getInvitationIntendedToUser(RequestContext $ctx): array
    {
        $requester = $ctx->getAuthenticatedUser();
        $user_id = $requester->getId();
        if ($requester === null || $user_id === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        $invitations = $this->invitationsRepository->findByIntendedForId($user_id);
        if ($invitations === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }


        return $invitations;
    }
}