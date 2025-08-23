<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth0\Auth0Service;
use App\Config\Config;
use App\User\UserService;

/**
 * Auth Controller - Thin HTTP layer for authentication
 * Handles HTTP requests/responses and delegates business logic to services
 */
final class Auth0Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Handle login request - redirect to Auth0
     * 
     * Clears any existing Auth0 session and redirects user to Auth0 login page.
     * This initiates the OAuth2 authorization code flow.
     */
    public function login(): void
    {
        // Clear any existing sessions
        Auth0Service::auth()->clear();
        
        // Redirect to Auth0 login
        $loginUrl = Auth0Service::auth()->login();
        header("Location: $loginUrl");
        exit;
    }

    /**
     * Handle Auth0 callback - core sign up/login logic
     * 
     * Implements US2 (sign up) and US3 (login) user stories:
     * - US2: If user doesn't exist, creates new user account
     * - US3: If user exists, syncs profile data and logs them in
     * 
     * Processes the OAuth2 authorization code and manages user creation/authentication.
     * Redirects to success page on completion or shows error page on failure.
     */
    public function callback(): void
    {
        header('Content-Type: application/json');

        $hasAuthenticationFailure = isset($_GET['error']);
        if ($hasAuthenticationFailure) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Authentication failed',
                'message' => $_GET['error'],
            ]);

            return;
        }    

        $hasAuthenticated = isset($_GET['state']) && isset($_GET['code']);
        if (!$hasAuthenticated) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Authentication failed',
                'message' => 'No authentication state or code found',
            ]);

            return;
        }

        try {
            Auth0Service::auth()->exchange();

            $user = Auth0Service::auth()->getUser();

            // fail safe method to ensure that the user is created in the database
            // even if the user is already present
            $this->userService->signUpVia3rdParty(
                $user['email'],
                $user['name'],
                $user['email_verified'],
            );

            header('Location: ' . Config::getString('auth0_login_success_redirect_uri'));
        } catch (\Throwable $e) {
            http_response_code(400);

            echo json_encode([
                'error' => 'Auth callback failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle logout request
     * 
     * Clears both internal user session and Auth0 session.
     * Redirects user to Auth0 logout URL which then redirects to configured logout success page.
     */
    public function logout(): void
    {
        try {
            // Clear user session
           Auth0Service::auth()->clear();
            
            // Get Auth0 logout URL
            $logoutUri = Config::getString('auth0_logout_redirect_uri', '/logout-success');
            $logoutUrl = Auth0Service::auth()->logout($logoutUri);
            
            header("Location: $logoutUrl");
            exit;
            
        } catch (\Exception $e) {
            error_log("Logout failed: " . $e->getMessage());
            
            // Fallback: clear session and redirect anyway
            Auth0Service::auth()->clear();
            header("Location: /logout-success");
            exit;
        }
    }

    /**
     * Get current user API endpoint
     * 
     * Returns JSON representation of the currently authenticated user.
     * Returns 401 Unauthorized if no user is authenticated.
     * 
     */
    public function getCurrentUser(): void
    {
        header('Content-Type: application/json');
        
        try {
            $user = Auth0Service::auth()->getUser();
            
            if ($user === null) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            echo json_encode($user);
        } catch (\Exception $e) {
            error_log("Get current user failed: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}
