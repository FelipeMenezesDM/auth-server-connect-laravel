<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel;

use Carbon\Carbon;

class AuthServerToken
{
    private static ?AuthServerToken $authServerToken = null;

    private array $authorizedCliente = [];

    private ?Carbon $expirationDateTime = null;

    public static function getInstance() : AuthServerToken
    {
        if(is_null(self::$authServerToken)) {
            self::$authServerToken = new static;
        }

        return self::$authServerToken;
    }

    public function setAuthorizedClient(array $autorizedClient) : void
    {
        $this->authorizedCliente = $autorizedClient;
    }

    public function setExpirationDateTime(Carbon $expirationDateTime) : void
    {
        $this->expirationDateTime = $expirationDateTime;
    }

    public function getAccessToken() : ?string
    {
        return $this->authorizedCliente ? $this->authorizedCliente['access_token'] : null;
    }

    public function getRefreshToken() : ?string
    {
        return $this->authorizedCliente ? $this->authorizedCliente['refresh_token'] : null;
    }

    public function getExpiresIn() : ?int
    {
        return $this->authorizedCliente ? $this->authorizedCliente['expires_in'] : null;
    }

    public function getExpirationDateTime() : ?Carbon
    {
        return $this->expirationDateTime;
    }
}
