<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Exceptions;

use RuntimeException;

class DynamicException extends RuntimeException
{
    public function getStatusCode() : int
    {
        return $this->code;
    }
}
