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

    public function setUser(User $user): void
    {
        $this->set('user', $user);
    }

    public function getUser(): ?User
    {
        return $this->get('user');
    }

    public function clear(): void
    {
        $this->data = [];
    }
}