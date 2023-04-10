<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Service;

use Carbon\Carbon;
use FelipeMenezesDM\AuthServerConnectLaravel\AuthServerToken;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Constants\General;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Exceptions\DynamicException;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Props\AuthServerProps;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class AuthServerService
{
    private static ?AuthServerService $authServerService = null;

    private AuthServerProps $authServerProps;

    private array $credentials;

    public function __construct()
    {
        $this->authServerProps = AuthServerProps::getInstance();
        $this->credentials = CredentialsService::getInstance()->getCredentials();
    }

    public static function getInstance() : AuthServerService
    {
        if(is_null(self::$authServerService)) {
            self::$authServerService = new static;
        }

        return self::$authServerService;
    }

    public function authorize() : AuthServerToken
    {
        $dateTime = Carbon::now();
        $authServerToken = $this->getToken();

        if(!$authServerToken || !$authServerToken->getExpirationDateTime() || $dateTime->isAfter($authServerToken->getExpirationDateTime())) {
            $authServerToken = $this->generateToken(($authServerToken->getRefreshToken() ? 'refresh_token' : 'client_credentials'), [
                'refresh_token' => $authServerToken->getRefreshToken(),
            ]);
        }

        return $authServerToken;
    }

    public function grantPassword(?string $username, ?string $password) : AuthServerToken
    {
        return $this->generateToken('password', [
            'username'  => $username,
            'password'  => $password,
        ]);
    }

    public function validate(string $token, ?string $correlationId, ?string $flowId, ?string $apiKey, ?array $scopes) : void
    {
        try {
            if ($this->authServerProps->getAuthServerEnabled()) {
                (new Client())->get($this->authServerProps->getAuthServerAssetUri() . '?scopes=' . implode(',', $scopes ?? []), [
                    'timeout'           => $this->authServerProps->getAuthServerTimeout(),
                    'connect_timeout'   => $this->authServerProps->getAuthServerTimeout(),
                    'headers'           => [
                        General::STR_AUTHORIZATION  => $token,
                        General::STR_CORRELATION_ID => $correlationId ?? Uuid::uuid4()->toString(),
                        General::STR_FLOW_ID        => $flowId,
                        General::STR_API_KEY        => $apiKey,
                    ]
                ]);
            }
        }catch(ConnectException $e) {
            throw new DynamicException($e->getMessage(), 504);
        }catch(GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }

    public function getToken() : ?AuthServerToken
    {
        return cache($this->getCacheHash()) ?? AuthServerToken::getInstance();
    }

    private function generateToken(string $grantType, array $opts = []) : AuthServerToken
    {
        try {
            $response = json_decode(((new Client())->post($this->authServerProps->getAuthServerTokenUri(), [
                'timeout'           => $this->authServerProps->getAuthServerTimeout(),
                'connect_timeout'   => $this->authServerProps->getAuthServerTimeout(),
                'json'              => [
                    'client_id'     => $this->credentials[$this->authServerProps->getAuthServerClientIdKey()],
                    'client_secret' => $this->credentials[$this->authServerProps->getAuthServerClientSecretKey()],
                    'grant_type'    => $grantType,
                    'redirect_uri'  => $this->authServerProps->getAuthServerRedirectUri(),
                    'scope'         => implode(',', $this->authServerProps->getAuthServerScopes() ?? ''),
                    ...$opts,
                ]
            ]))->getBody()->getContents(), true);

            $authServerToken = AuthServerToken::getInstance();
            $authServerToken->setAuthorizedClient($response);
            $authServerToken->setExpirationDateTime((Carbon::now())->addSeconds($response['expires_in']));

            cache([$this->getCacheHash() => $authServerToken], $authServerToken->getExpiresIn());

            return $authServerToken;
        }catch(ClientException $e) {
            throw new DynamicException($e->getMessage(), $e->getResponse()->getStatusCode());
        }catch(ConnectException $e) {
            throw new DynamicException($e->getMessage(), 504);
        }catch(GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }

    private function getCacheHash() : string
    {
        $clientId = $this->credentials[$this->authServerProps->getAuthServerClientIdKey()];
        $clientSecret = $this->credentials[$this->authServerProps->getAuthServerClientSecretKey()];

        return sprintf('credentials[%s]', hash('sha512', $clientId . '/' . $clientSecret));
    }
}
