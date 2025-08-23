<?php

declare(strict_types=1);

namespace App\Http;

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

    public function clear(): void
    {
        $this->data = [];
    }
}