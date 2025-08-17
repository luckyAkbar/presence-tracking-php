<?php
declare(strict_types=1);

namespace App\Auth;

use Auth0\SDK\Auth0; 
use App\Config\Config;

final class AuthService
{
    private static ?Auth0 $instance = null;

    public static function auth(): Auth0
    {
        if (self::$instance instanceof Auth0) {
            return self::$instance;
        }

        $domain = Config::getString('auth0_domain');
        $clientId = Config::getString('auth0_client_id');
        $clientSecret = Config::getString('auth0_client_secret');
        $cookieSecret = Config::getString('auth0_cookie_secret', bin2hex(random_bytes(32)));
        // Prefer configured redirect URI; otherwise, derive from current request host
        $redirectUri = Config::getString('auth0_redirect_uri');
        if ($redirectUri === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080';
            $redirectUri = sprintf('%s://%s/callback', $scheme, $host);
        }

        $config = [
            'domain' => $domain,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'cookieSecret' => $cookieSecret,
            'redirectUri' => $redirectUri,
        ];

        self::$instance = new Auth0($config);

        return self::$instance;
    }
}


