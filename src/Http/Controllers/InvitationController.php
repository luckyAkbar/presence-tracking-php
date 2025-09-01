<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\RequestContext;
use App\Invitation\InvitationsService;
use App\ServiceExceptions\UnauthorizedAccessException;
use App\ServiceExceptions\InvalidArgumentException;
use App\ServiceExceptions\ResourceNotFoundException;
use App\ServiceExceptions\ForbiddenAccessException;


final class InvitationController
{
    public function __construct(
        private InvitationsService $invitationService
    ) {}

    public function handleCreateInvitation(RequestContext $ctx)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $target_email = $data['target_email'];
        $organization_id = $data['organization_id'];

        if ($target_email === null || $organization_id === null) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid request',
                'message' => 'Target email and organization id are required',
            ]);
            return;
        }

        $this->safeExecute(function(RequestContext $ctx) use ($target_email, $organization_id) {
            $invitation = $this->invitationService->createNewInvitation(
                $ctx,
                $target_email,
                $organization_id,
            );

            http_response_code(201);
            echo json_encode($invitation);
        }, $ctx);
    }

    public function handleGetInvitationsForRequester(RequestContext $ctx): void
    {
        $this->safeExecute(function(RequestContext $ctx) {
            $invitations = $this->invitationService->getInvitationIntendedToUser($ctx);

            http_response_code(200);
            echo json_encode([
                'invitations' => $invitations,
                'count' => count($invitations),
            ]);
        }, $ctx);
    }

    public function handleSearchOrganizationMemberInvitation(RequestContext $ctx): void
    {
        header('Content-Type: application/json');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if ($data === null) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'missing required body requests',
            ]);
            return;
        }

        $organization_id = $data['organization_id'];
        $limit = $data['limit'] ?? 100;
        $offset = $data['offset'] ?? 0;
        $statuses = $data['statuses'] ?? null;
        $target_email = $data['target_email'] ?? null;

        if (filter_var($target_email, FILTER_VALIDATE_EMAIL) === false) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'target_email must be a valid email address',
            ]);
            return;
        }

        $search_params = [
            'limit' => $limit,
            'offset' => $offset,
            'statuses' => $statuses,
            'target_email' => $target_email,
        ];
        

        $this->safeExecute(function(RequestContext $ctx) use ($organization_id,$search_params) {
            $invitations = $this->invitationService->searchOrganizationMemberInvitation($ctx, $organization_id, $search_params);

            http_response_code(200);
            echo json_encode($invitations);
        }, $ctx);
    }

    public function handleAcceptOrganizationMembershipInvitation(RequestContext $ctx): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if ($data === null) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'missing required body requests',
            ]);
            return;
        }
        
        $invitation_id = $data['invitation_id'];

        if ($invitation_id === null or $invitation_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'invitation_id is required and must be greater than 0',
            ]);
            return;
        }
    
        $this->safeExecute(function(RequestContext $ctx) use ($invitation_id) {
            $organizationMember = $this->invitationService->acceptOrganizationMembershipInvitation($ctx, $invitation_id);

            http_response_code(201);
            echo json_encode($organizationMember);
        }, $ctx);
    }

    private function safeExecute(callable $handler, RequestContext $ctx): void
    {
        try {
            $handler($ctx);
        } catch (UnauthorizedAccessException $e) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => $e->getMessage(),
            ]);
        } catch (ResourceNotFoundException $e) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Not found',
                'message' => $e->getMessage(),
            ]);
        } catch (ForbiddenAccessException $e) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Forbidden',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}