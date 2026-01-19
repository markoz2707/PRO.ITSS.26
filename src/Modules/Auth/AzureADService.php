<?php

namespace ITSS\Modules\Auth;

use ITSS\Core\Logger;

class AzureADService
{
    private array $config;
    private string $state;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->state = bin2hex(random_bytes(16));
    }

    public function getAuthorizationUrl(): string
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->config['redirect_uri'],
            'response_mode' => 'query',
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $this->state
        ];

        $authorizeUrl = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
            $this->config['tenant_id']
        );

        return $authorizeUrl . '?' . http_build_query($params);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getAccessToken(string $code): string
    {
        $tokenUrl = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            $this->config['tenant_id']
        );

        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Logger::error('Failed to get access token', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \RuntimeException('Failed to get access token');
        }

        $data = json_decode($response, true);
        return $data['access_token'];
    }

    public function getUserInfo(string $accessToken): array
    {
        $ch = curl_init('https://graph.microsoft.com/v1.0/me');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Logger::error('Failed to get user info', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \RuntimeException('Failed to get user info');
        }

        $userData = json_decode($response, true);

        return [
            'm365_id' => $userData['id'],
            'email' => $userData['mail'] ?? $userData['userPrincipalName'],
            'first_name' => $userData['givenName'] ?? '',
            'last_name' => $userData['surname'] ?? ''
        ];
    }

    public function getUserManager(string $accessToken): ?array
    {
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/manager');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $managerData = json_decode($response, true);

        return [
            'm365_id' => $managerData['id'],
            'email' => $managerData['mail'] ?? $managerData['userPrincipalName'],
            'first_name' => $managerData['givenName'] ?? '',
            'last_name' => $managerData['surname'] ?? ''
        ];
    }
}
