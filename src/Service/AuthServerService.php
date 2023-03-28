<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Service;

use Carbon\Carbon;
use FelipeMenezesDM\AuthServerConnectLaravel\AuthServerToken;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Constants\General;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Exceptions\DynamicException;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Props\AuthServerProps;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class AuthServerService
{
    private static ?AuthServerService $authServerService = null;

    private AuthServerToken $authServerToken;

    private CredentialsService $credentialsService;

    private AuthServerProps $authServerProps;

    public function __construct()
    {
        $this->authServerToken = AuthServerToken::getInstance();
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

        try {
            if (!$this->authServerToken->getExpirationDateTime() || $dateTime->isAfter($this->authServerToken->getExpirationDateTime())) {
                $credentials = $this->credentialsService->getCredentials();
                $response = json_decode(((new Client())->post($this->authServerProps->getAuthServerTokenUri(), [
                    'json' => [
                        'client_id' => $credentials[$this->authServerProps->getAuthServerClientIdKey()],
                        'client_secret' => $credentials[$this->authServerProps->getAuthServerClientSecretKey()],
                        'grant_type' => $this->authServerProps->getAuthServerGrantType(),
                        'scope' => implode(',', $this->authServerProps->getAuthServerScopes() ?? ''),
                        'redirect_uri' => $this->authServerProps->getAuthServerRedirectUri(),
                    ]
                ]))->getBody()->getContents(), true);

                $dateTime->addSeconds($response['expires_in']);
                $this->authServerToken->setAuthorizedClient($response);
                $this->authServerToken->setExpirationDateTime($dateTime);
            }

            return $this->authServerToken;
        }catch(ClientException $e) {
            throw new DynamicException($e->getMessage(), $e->getResponse()->getStatusCode());
        } catch (GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }

    public function validate(string $token, string|null $correlationId, array|null $scopes) : void
    {
        try {
            if ($this->authServerProps->getAuthServerEnabled()) {
                (new Client())->get($this->authServerProps->getAuthServerAssetUri() . '?scopes=' . implode(',', $scopes ?? []), [
                    'headers' => [
                        General::STR_AUTHORIZATION => $token,
                        General::STR_CORRELATION_ID => $correlationId ?? Uuid::uuid4()->toString(),
                    ]
                ]);
            }
        }catch(ClientException $e) {
            throw new DynamicException($e->getMessage(), $e->getResponse()->getStatusCode());
        } catch (GuzzleException $e) {
            throw new DynamicException($e->getMessage(), 403);
        }
    }
}
