<?php

namespace App\Http;

final class UnauthorizedException extends \Exception
{
    public function __construct(string $message = 'Unauthorized', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}

final class ForbiddenException extends \Exception
{
    public function __construct(string $message = 'Forbidden', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}