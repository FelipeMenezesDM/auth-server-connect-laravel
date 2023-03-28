<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Service;

use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Props\AuthServerProps;

class CredentialsService
{
    private static ?CredentialsService $credentialsService = null;

    private AuthServerProps $authServerProps;

    public function __construct()
    {
        $this->authServerProps = AuthServerProps::getInstance();
    }

    public static function getInstance() : CredentialsService
    {
        if(is_null(self::$credentialsService)) {
            self::$credentialsService = new static;
        }

        return self::$credentialsService;
    }

    public function getCredentials() : array
    {
        putenv('APP_SUITE=' . $this->authServerProps->getAuthServerProvider());
        putenv('AWS_ENDPOINT=' . $this->authServerProps->getAuthServerEndPoint());
        putenv('AWS_ACCOUNT_ID=' . $this->authServerProps->getAuthServerProjectId());
        putenv('AWS_DEFAULT_REGION=' . $this->authServerProps->getAuthServerRegion());
        putenv('GCP_PROJECT_ID=' . $this->authServerProps->getAuthServerProjectId());

        if(strtoupper($this->authServerProps->getAuthServerProvider()) === 'ENVIRONMENT') {
            return $this->getCredentialsFromEnvironmentVariables();
        }

        return json_decode(suite()->getSecretData($this->authServerProps->getAuthServerSecretName()), true);
    }

    private function getCredentialsFromEnvironmentVariables() : array
    {
        return [
            $this->authServerProps->getAuthServerClientIdKey() => getenv($this->authServerProps->getAuthServerClientIdKey()),
            $this->authServerProps->getAuthServerClientSecretKey() => getenv($this->authServerProps->getAuthServerClientSecretKey()),
        ];
    }
}
