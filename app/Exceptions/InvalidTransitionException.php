<?php

namespace App\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $message = 'This status transition is not allowed.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}