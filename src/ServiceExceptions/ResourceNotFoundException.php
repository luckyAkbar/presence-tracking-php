<?php

namespace App\ServiceExceptions;

/**
 * Exception that will be thrown when an operation is attempted on a resource that does not exist.
 * 
 * @extends \Exception
 */
final class ResourceNotFoundException extends \Exception
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message);
    }
}