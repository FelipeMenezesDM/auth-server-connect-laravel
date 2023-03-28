<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Props;

class AuthServerProps
{
    private static ?AuthServerProps $authServerProps = null;

    public static function getInstance() : AuthServerProps
    {
        if(is_null(self::$authServerProps)) {
            self::$authServerProps = new static;
        }

        return self::$authServerProps;
    }

    public function getAuthServerName() : string
    {
        return env('AUTH_SERVER_NAME', 'auth-server');
    }

    public function getAuthServerEnabled() : bool
    {
        return env('AUTH_SERVER_ENABLED', true);
    }

    public function getAuthServerTokenUri() : string
    {
        return env('AUTH_SERVER_URI', '') . '/token';
    }

    public function getAuthServerAssetUri() : string
    {
        return env('AUTH_SERVER_URI', '') . '/asset';
    }

    public function getAuthServerRedirectUri() : string
    {
        return env('AUTH_SERVER_REDIRECT_URI', '');
    }

    public function getAuthServerGrantType() : string
    {
        return env('AUTH_SERVER_GRANT_TYPE', 'client_credentials');
    }

    public function getAuthServerScopes() : array
    {
        return explode(',', env('AUTH_SERVER_SCOPES', ''));
    }

    public function getAuthServerProvider() : string
    {
        return env('AUTH_SERVER_PROVIDER', 'environment');
    }

    public function getAuthServerProjectId() : string
    {
        return env('AUTH_SERVER_PROJECT_ID', '');
    }

    public function getAuthServerRegion() : string
    {
        return env('AUTH_SERVER_REGION', 'us-east-1');
    }

    public function getAuthServerClientIdKey() : string
    {
        return env('AUTH_SERVER_CLIENT_ID_KEY', 'client_id');
    }

    public function getAuthServerClientSecretKey() : string
    {
        return env('AUTH_SERVER_CLIENT_SECRET_KEY', 'client_secret');
    }

    public function getAuthServerEndPoint() : string
    {
        return env('AUTH_SERVER_END_POINT', '');
    }

    public function getAuthServerSecretName() : string
    {
        return env('AUTH_SERVER_SECRET_NAME', '');
    }
}
