<?php

declare(strict_types=1);

namespace App\Invitation;

use App\Http\RequestContext;
use App\ServiceExceptions\InvalidArgumentException;
use App\ServiceExceptions\ResourceNotFoundException;
use App\User\UserRepository;
use App\ServiceExceptions\UnauthorizedAccessException;
use App\ServiceExceptions\ForbiddenAccessException;
use App\Support\Transaction;
use PDO;
use App\Organization\OrganizationMemberRepository;
use App\Organization\OrganizationMember;

final class InvitationsService
{
    public function __construct(
        private InvitationsRepository $invitationsRepository,
        private UserRepository $userRepository,
        private OrganizationMemberRepository $organizationMemberRepository,
        private InvitationQueryService $invitationQueryService,
        private Transaction $transactionService
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

        // if there is an existing invitation to the user and
        // it is possible to reinvite the user, will update the
        // invitation to be pending. otherwise, just return the
        // existing invitation.
        $existingInvitation = $this->invitationQueryService->findByIntendedUserEmail($target_email, $organization_id);
        if ($existingInvitation !== null) {
            switch ($existingInvitation->getStatus()) {
                case Invitation::statusAccepted:
                    return $existingInvitation;
                case Invitation::statusPending:
                    return $existingInvitation;
                default:
                    break;
            }

            $this->invitationsRepository->updateStatus($existingInvitation->getId(), Invitation::statusPending, null);
            $updatedInvitation = $this->invitationsRepository->findById($existingInvitation->getId());
            if ($updatedInvitation === null) {
                throw new \Exception('Failed to update invitation');
            }

            return $updatedInvitation;
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

    public function searchOrganizationMemberInvitation(RequestContext $ctx, int $organization_id, array $search_params): array
    {
        $requester = $ctx->getAuthenticatedUser();
        if ($requester === null || $requester->getId() === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        if (!$requester->isAdminOfOrganization($organization_id)) {
            throw new ForbiddenAccessException('Organization member invitation can only be fetched by an admin of the organization');   
        }

        $limit = $search_params['limit'] ?? 100;
        $offset = $search_params['offset'] ?? 0;
        $statuses = $search_params['statuses'] ?? Invitation::getAllValidStatuses();

        if (!Invitation::isAllValidStatus($statuses)) {
            $statuses = Invitation::getAllValidStatuses();
        }

        $target_user_id = null;
        if (!empty($search_params['target_email'])) {
            $target_user_id = $this->userRepository->findByEmail($search_params['target_email']);
            if ($target_user_id === null) {
                throw new ResourceNotFoundException('Target user with email ' . $search_params['target_email'] . ' not found');
            }

            $target_user_id = $target_user_id->getId();
        }

        $searchInvitationParams = [
            'limit' => $limit,
            'offset' => $offset,
            'statuses' => $statuses,
            'organization_id' => $organization_id,
            'intended_for' => $target_user_id,
        ];

        $invitations = $this->invitationsRepository->search($searchInvitationParams);
        if ($invitations === null) {
            throw new ResourceNotFoundException('No invitations found');
        }

        return $invitations;
    }

    public function acceptOrganizationMembershipInvitation(RequestContext $ctx, int $invitation_id): OrganizationMember
    {
        $requester = $ctx->getAuthenticatedUser();
        if ($requester === null || $requester->getId() === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }

        $invitation = $this->invitationsRepository->findById($invitation_id);
        if ($invitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        $userId = $requester->getId();
        if ($userId !== $invitation->getIntendedForId()) {
            throw new ForbiddenAccessException('You are not authorized to accept this invitation');
        }

        $operation = Invitation::operationAcceptInvitation;

        if (!Invitation::isValidAcceptanceOperation($operation)) {
            throw new InvalidArgumentException('Invalid operation');
        }

        if (!$invitation->isAcceptanceOperationValid($operation)) {
            throw new ForbiddenAccessException('unable to perform ' . $operation . ' on a ' . $invitation->getStatus() . ' invitation');
        }

        if ($invitation->isExpired()) {
            throw new ForbiddenAccessException('Invitation has expired');
        }

        $organizationMember = $this->transactionService->executeInTransaction(function(PDO $pdo) use ($invitation) {
            $this->invitationsRepository->updateStatus(
                $invitation->getId(),
                Invitation::statusAccepted,
                $pdo,
            );

            $organizationMember = $this->organizationMemberRepository->create(
                $invitation->getOrganizationId(),
                $invitation->getIntendedForId(),
                $pdo,
            );

            if ($organizationMember === null) {
                throw new \Exception('Failed to create organization member');
            }

            return $organizationMember;
        });

        // safe guard against null
        if ($organizationMember === null) {
            throw new \Exception('Failed to create organization member');
        }

        return $organizationMember;
    }

    public function cancelOrganizationMembershipInvitation(RequestContext $ctx, int $invitation_id): Invitation
    {
        $requester = $ctx->getAuthenticatedUser();
        if ($requester === null || $requester->getId() === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }
        
        $invitation = $this->invitationsRepository->findById($invitation_id);
        if ($invitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        if (!$requester->isAdminOfOrganization($invitation->getOrganizationId())) {
            throw new ForbiddenAccessException('You are not authorized to cancel this invitation');
        }

        if ($invitation->getStatus() === Invitation::statusCancelled) {
            return $invitation;
        }

        $operation = Invitation::operationCancelInvitation;
        if (!$invitation->isAcceptanceOperationValid($operation)) {
            throw new ForbiddenAccessException('unable to perform ' . $operation . ' on a ' . $invitation->getStatus() . ' invitation');
        }

        $this->invitationsRepository->updateStatus($invitation->getId(), Invitation::statusCancelled, null);

        $invitation = $this->invitationsRepository->findById($invitation_id);
        if ($invitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        return $invitation;
    }

    public function rejectOrganizationMembershipInvitation(RequestContext $ctx, int $invitation_id): Invitation
    {
        $requester = $ctx->getAuthenticatedUser();
        if ($requester === null || $requester->getId() === null) {
            throw new UnauthorizedAccessException('This action requires an authenticated user');
        }
        
        $invitation = $this->invitationsRepository->findById($invitation_id);
        if ($invitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        $userId = $requester->getId();
        if ($invitation->getIntendedForId() !== $userId) {
            throw new ForbiddenAccessException('You are not authorized to reject this invitation');
        }

        if ($invitation->getStatus() === Invitation::statusRejected) {
            return $invitation;
        }

        $operation = Invitation::operationRejectInvitation;
        if (!$invitation->isAcceptanceOperationValid($operation)) {
            throw new ForbiddenAccessException('unable to perform ' . $operation . ' on a ' . $invitation->getStatus() . ' invitation');
        }

        $this->invitationsRepository->updateStatus($invitation->getId(), Invitation::statusRejected, null);

        $rejectedInvitation = $this->invitationsRepository->findById($invitation_id);
        if ($rejectedInvitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        if ($rejectedInvitation === null) {
            throw new ResourceNotFoundException('Invitation not found');
        }

        return $rejectedInvitation;
    }
}