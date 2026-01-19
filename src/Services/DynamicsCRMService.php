<?php

namespace ITSS\Services;

use ITSS\Core\Logger;
use ITSS\Models\Project;
use ITSS\Models\User;

class DynamicsCRMService
{
    private array $config;
    private ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $tokenUrl = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/token',
            $this->config['tenant_id'] ?? 'common'
        );

        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'resource' => $this->config['resource'],
            'grant_type' => 'client_credentials'
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
            Logger::error('Failed to get Dynamics CRM access token', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \RuntimeException('Failed to get Dynamics CRM access token');
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    private function apiRequest(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        $url = sprintf(
            '%s/api/data/v%s/%s',
            rtrim($this->config['url'], '/'),
            $this->config['api_version'],
            ltrim($endpoint, '/')
        );

        $accessToken = $this->getAccessToken();

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'Content-Type: application/json',
            'OData-MaxVersion: 4.0',
            'OData-Version: 4.0'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Logger::error('Dynamics CRM API request failed', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \RuntimeException('Dynamics CRM API request failed');
        }

        return json_decode($response, true) ?? [];
    }

    public function syncProjects(): int
    {
        Logger::info('Starting Dynamics CRM projects synchronization');

        try {
            $entityName = $this->config['projects_entity'];
            $select = 'name,opportunityid,actualvalue,actualclosedate,statuscode,description';
            $endpoint = "{$entityName}?\$select={$select}";

            $response = $this->apiRequest($endpoint);

            if (!isset($response['value'])) {
                Logger::warning('No projects found in CRM response');
                return 0;
            }

            $projectModel = new Project();
            $userModel = new User();
            $syncedCount = 0;

            foreach ($response['value'] as $crmProject) {
                try {
                    $projectData = [
                        'project_number' => $this->extractProjectNumber($crmProject),
                        'project_name' => $crmProject['name'] ?? 'Unnamed Project',
                        'description' => $crmProject['description'] ?? null,
                        'status' => $this->mapCRMStatus($crmProject['statuscode'] ?? null)
                    ];

                    if (isset($crmProject['actualclosedate'])) {
                        $projectData['end_date'] = date('Y-m-d', strtotime($crmProject['actualclosedate']));
                    }

                    $projectModel->updateFromCRM($crmProject['opportunityid'], $projectData);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Logger::error('Failed to sync individual project', [
                        'crm_id' => $crmProject['opportunityid'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Logger::info('Dynamics CRM projects synchronization completed', [
                'synced_count' => $syncedCount
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Logger::error('Dynamics CRM synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function extractProjectNumber(array $crmProject): string
    {
        if (isset($crmProject['new_projectnumber'])) {
            return $crmProject['new_projectnumber'];
        }

        if (isset($crmProject['opportunityid'])) {
            return 'CRM-' . substr($crmProject['opportunityid'], 0, 8);
        }

        return 'PRJ-' . uniqid();
    }

    private function mapCRMStatus(?int $statusCode): string
    {
        if ($statusCode === null) {
            return 'planning';
        }

        return match($statusCode) {
            1 => 'active',
            2 => 'completed',
            3 => 'cancelled',
            default => 'planning'
        };
    }

    public function getProjectDetails(string $crmId): ?array
    {
        try {
            $entityName = $this->config['projects_entity'];
            $endpoint = "{$entityName}({$crmId})";

            return $this->apiRequest($endpoint);
        } catch (\Exception $e) {
            Logger::error('Failed to get project details from CRM', [
                'crm_id' => $crmId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
