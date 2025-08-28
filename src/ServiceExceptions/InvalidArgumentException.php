<?php

namespace App\ServiceExceptions;

/**
 * Exception that will be thrown when an input argument is considered invalid by the underlying service.
 * 
 * @extends \Exception
 */
final class InvalidArgumentException extends \Exception
{
    public function __construct(string $message = 'Invalid argument')
    {
        parent::__construct($message);
    }
}