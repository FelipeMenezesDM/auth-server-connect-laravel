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

    private CredentialsService $credentialsService;

    private AuthServerProps $authServerProps;

    public function __construct()
    {
        $this->credentialsService = CredentialsService::getInstance();
        $this->authServerProps = AuthServerProps::getInstance();
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
        $credentials = $this->credentialsService->getCredentials();
        $clientId = $credentials[$this->authServerProps->getAuthServerClientIdKey()];
        $clientSecret = $credentials[$this->authServerProps->getAuthServerClientSecretKey()];
        $hash = hash('sha512', $clientId . '/' . $clientSecret);
        $authServerToken = cache($hash);

        try {
            if(!$authServerToken || !$authServerToken->getExpirationDateTime() || $dateTime->isAfter($authServerToken->getExpirationDateTime())) {
                $response = json_decode(((new Client())->post($this->authServerProps->getAuthServerTokenUri(), [
                    'timeout'           => $this->authServerProps->getAuthServerTimeout(),
                    'connect_timeout'   => $this->authServerProps->getAuthServerTimeout(),
                    'json'              => [
                        'client_id'     => $clientId,
                        'client_secret' => $clientSecret,
                        'grant_type'    => $this->authServerProps->getAuthServerGrantType(),
                        'scope'         => implode(',', $this->authServerProps->getAuthServerScopes() ?? ''),
                        'redirect_uri'  => $this->authServerProps->getAuthServerRedirectUri(),
                    ]
                ]))->getBody()->getContents(), true);

                $dateTime->addSeconds($response['expires_in']);

                $authServerToken = AuthServerToken::getInstance();
                $authServerToken->setAuthorizedClient($response);
                $authServerToken->setExpirationDateTime($dateTime);

                cache([$hash => $authServerToken], $authServerToken->getExpiresIn());
            }

            return $authServerToken;
        }catch(ClientException $e) {
            throw new DynamicException($e->getMessage(), $e->getResponse()->getStatusCode());
        }catch(ConnectException $e) {
            throw new DynamicException($e->getMessage(), 504);
        }catch(GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }

    public function validate(string $token, string|null $correlationId, array|null $scopes) : void
    {
        try {
            if ($this->authServerProps->getAuthServerEnabled()) {
                (new Client())->get($this->authServerProps->getAuthServerAssetUri() . '?scopes=' . implode(',', $scopes ?? []), [
                    'timeout'           => $this->authServerProps->getAuthServerTimeout(),
                    'connect_timeout'   => $this->authServerProps->getAuthServerTimeout(),
                    'headers'           => [
                        General::STR_AUTHORIZATION  => $token,
                        General::STR_CORRELATION_ID => $correlationId ?? Uuid::uuid4()->toString(),
                    ]
                ]);
            }
        }catch(ConnectException $e) {
            throw new DynamicException($e->getMessage(), 504);
        }catch(GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }
}
