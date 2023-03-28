<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AuthServerConnection
{
    public function __construct()
    {
    }
}
