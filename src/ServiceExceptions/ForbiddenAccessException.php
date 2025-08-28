<?php

declare(strict_types=1);

namespace App\ServiceExceptions;

/**
 * Exception that will be thrown when an operation is attempted that is forbidden.
 * 
 * @extends \Exception
 */
final class ForbiddenAccessException extends \Exception
{
    public function __construct(string $message = 'Forbidden access')
    {
        parent::__construct($message);
    }
}