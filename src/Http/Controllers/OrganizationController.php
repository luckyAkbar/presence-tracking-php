<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\RequestContext;
use App\Organization\OrganizationService;
use App\Organization\UnauthorizedAccessException;

final class OrganizationController
{
    public function __construct(
        private OrganizationService $organizationService
    ) {}

    public function registerNewOrganization(RequestContext $ctx): void
    {
        header('Content-Type: application/json');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $name = $data['name'];
        $description = $data['description'];

        if ($name === null || $description === null) {
            http_response_code(400);

            echo json_encode([
                'error' => 'Invalid request',
                'message' => 'Name and description are required',
            ]);

            return;
        }

        $this->safeExecute(function(RequestContext $ctx) use ($name, $description) {
            $organization = $this->organizationService->registerNewOrganization($ctx, $name, $description);

            http_response_code(201);
            echo json_encode($organization);
        }, $ctx);
    }

    /**
     * Safely execute a handler and handle exceptions.
     * 
     * @param callable $handler
     * @param RequestContext $ctx
     * @return void
     */
    private function safeExecute(callable $handler, RequestContext $ctx): void
    {
        try {
            $handler($ctx);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
            ]);
        } catch (UnauthorizedAccessException $e) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ]);
        }
    }
}