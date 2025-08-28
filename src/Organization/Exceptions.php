<?php

declare(strict_types=1);

namespace App\Organization;

/**
 * Exception that will be thrown when an unauthorized access is attempted.
 * 
 * @extends \Exception
 */
final class UnauthorizedAccessException extends \Exception
{
    public function __construct(string $message = 'Unauthorized access')
    {
        parent::__construct($message);
    }
}