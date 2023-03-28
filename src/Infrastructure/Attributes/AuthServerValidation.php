<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD)]
class AuthServerValidation
{
    public function __construct(private array $scopes = [])
    {
    }

    public function getScopes() : array
    {
        return $this->scopes;
    }
}
