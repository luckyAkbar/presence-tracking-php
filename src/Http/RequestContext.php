<?php

declare(strict_types=1);

namespace App\Http;

use App\User\User;

final class RequestContext
{
    private array $data = [];

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
    
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function setAuthenticatedUser(AuthenticatedUser $authenticatedUser): void
    {
        $this->set('authenticated_user', $authenticatedUser);
    }

    public function getAuthenticatedUser(): ?AuthenticatedUser
    {
        return $this->get('authenticated_user');
    }

    public function clear(): void
    {
        $this->data = [];
    }
}