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
        $limit = 100;
        $offset = 0;

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit = max(0, (int) $_GET['limit']);
        }

        if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
            $offset = max(0, (int) $_GET['offset']);
        }

        $this->safeExecute(function(RequestContext $ctx) use ($limit, $offset) {
            $invitations = $this->invitationService->getInvitationIntendedToUser(
                $ctx,
                $limit,
                $offset,
            );

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

        // organization_id (required, int > 0)
        if (!isset($_GET['organization_id']) || !is_numeric($_GET['organization_id'])) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'organization_id is required and must be a number',
            ]);
            return;
        }
        $organization_id = (int) $_GET['organization_id'];
        if ($organization_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'organization_id must be greater than 0',
            ]);
            return;
        }

        // limit (optional, int >= 0)
        $limit = 100;
        if (isset($_GET['limit'])) {
            if (!is_numeric($_GET['limit'])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'limit must be a number',
                ]);
                return;
            }
            $limit = (int) $_GET['limit'];
            if ($limit < 0) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'limit must be greater than or equal to 0',
                ]);
                return;
            }
        }

        // offset (optional, int >= 0)
        $offset = 0;
        if (isset($_GET['offset'])) {
            if (!is_numeric($_GET['offset'])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'offset must be a number',
                ]);
                return;
            }
            $offset = (int) $_GET['offset'];
            if ($offset < 0) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'offset must be greater than or equal to 0',
                ]);
                return;
            }
        }

        // statuses (optional, array of strings via statuses[]=.. or comma-separated string)
        $statuses = null;
        if (isset($_GET['statuses'])) {
            if (is_array($_GET['statuses'])) {
                $statuses = array_map(static function($v) { return (string) $v; }, $_GET['statuses']);
            } elseif (is_string($_GET['statuses'])) {
                $statuses = array_filter(array_map('trim', explode(',', $_GET['statuses'])), static function($v) { return $v !== ''; });
            } else {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'statuses must be an array or comma-separated string',
                ]);
                return;
            }
        }

        // target_email (optional, string, must be valid email if provided)
        $target_email = '';
        if (isset($_GET['target_email'])) {
            if (!is_string($_GET['target_email'])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'target_email must be a string',
                ]);
                return;
            }
            $target_email = trim($_GET['target_email']);
            if ($target_email !== '' && filter_var($target_email, FILTER_VALIDATE_EMAIL) === false) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Bad request',
                    'message' => 'target_email must be a valid email address',
                ]);
                return;
            }
        }

        $search_params = [
            'limit' => $limit,
            'offset' => $offset,
            'statuses' => $statuses,
            'target_email' => $target_email,
        ];

        $this->safeExecute(function(RequestContext $ctx) use ($organization_id, $search_params) {
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

        if ($invitation_id === null || $invitation_id <= 0) {
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

    public function handleCancelOrganizationMembershipInvitation(RequestContext $ctx): void
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

        if ($invitation_id === null || $invitation_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'invitation_id is required and must be greater than 0',
            ]);
            return;
        }

        $this->safeExecute(function(RequestContext $ctx) use ($invitation_id) {
            $invitation = $this->invitationService->cancelOrganizationMembershipInvitation($ctx, $invitation_id);

            http_response_code(200);
            echo json_encode($invitation);
        }, $ctx);
    }

    public function handleRejectOrganizationMembershipInvitation(RequestContext $ctx): void
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

        if ($invitation_id === null || $invitation_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Bad request',
                'message' => 'invitation_id is required and must be greater than 0',
            ]);
            return;
        }
        
        $this->safeExecute(function(RequestContext $ctx) use ($invitation_id) {
            $invitation = $this->invitationService->rejectOrganizationMembershipInvitation($ctx, $invitation_id);

            http_response_code(200);
            echo json_encode($invitation);
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