<?php

namespace ITSS\Services;

use ITSS\Core\Logger;
use ITSS\Models\WorkHour;
use ITSS\Models\User;
use ITSS\Models\Project;
use ITSS\Models\ServiceDeskContract;
use ITSS\Models\ServiceDeskProject;

class ServiceDeskService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // =========================================================================
    // Moduł UMOWY (Contracts) - ServiceDesk Plus MSP
    // =========================================================================

    public function syncContracts(): int
    {
        Logger::info('Starting ServiceDesk contracts synchronization');

        try {
            $startIndex = 1;
            $rowCount = 100;
            $totalSynced = 0;
            $contractModel = new ServiceDeskContract();

            do {
                $params = [
                    'input_data' => json_encode([
                        'list_info' => [
                            'row_count' => $rowCount,
                            'start_index' => $startIndex,
                            'sort_field' => 'name',
                            'sort_order' => 'asc'
                        ]
                    ])
                ];

                $response = $this->apiRequest('contracts', $params);

                if (!isset($response['contracts']) || empty($response['contracts'])) {
                    break;
                }

                foreach ($response['contracts'] as $sdContract) {
                    try {
                        $contractData = $this->mapContractData($sdContract);
                        $contractModel->upsertFromSD($sdContract['id'], $contractData);
                        $totalSynced++;
                    } catch (\Exception $e) {
                        Logger::error('Failed to sync contract', [
                            'sd_contract_id' => $sdContract['id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $startIndex += $rowCount;
                $hasMore = isset($response['list_info']['has_more_rows'])
                    ? $response['list_info']['has_more_rows']
                    : count($response['contracts']) >= $rowCount;

            } while ($hasMore);

            Logger::info('ServiceDesk contracts synchronization completed', [
                'synced_count' => $totalSynced
            ]);

            return $totalSynced;
        } catch (\Exception $e) {
            Logger::error('ServiceDesk contracts synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getContractDetails(string $contractId): ?array
    {
        try {
            $response = $this->apiRequest("contracts/{$contractId}");
            return $response['contract'] ?? $response;
        } catch (\Exception $e) {
            Logger::error('Failed to get contract details from ServiceDesk', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function mapContractData(array $sdContract): array
    {
        return [
            'contract_name' => $sdContract['name'] ?? $sdContract['contract_name'] ?? 'Bez nazwy',
            'contract_number' => $sdContract['contract_number'] ?? null,
            'account_name' => $sdContract['account']['name']
                ?? $sdContract['account_name']
                ?? null,
            'contract_type' => $sdContract['contract_type']['name']
                ?? $sdContract['contract_type']
                ?? null,
            'status' => $sdContract['status']['name']
                ?? $sdContract['status']
                ?? null,
            'start_date' => $this->parseSdDate($sdContract['from_date'] ?? $sdContract['start_date'] ?? null),
            'end_date' => $this->parseSdDate($sdContract['to_date'] ?? $sdContract['end_date'] ?? null),
            'cost' => $sdContract['cost'] ?? $sdContract['contract_value'] ?? null,
            'currency' => $sdContract['currency'] ?? 'PLN',
            'description' => $sdContract['description'] ?? null,
            'vendor_name' => $sdContract['vendor']['name']
                ?? $sdContract['vendor_name']
                ?? null,
            'support_type' => $sdContract['support_type']['name']
                ?? $sdContract['support_type']
                ?? null,
            'sla_name' => $sdContract['sla']['name']
                ?? $sdContract['sla_name']
                ?? null,
            'notification_before_days' => $sdContract['notification_before_days']
                ?? $sdContract['alert_before']
                ?? null,
            'raw_data' => $sdContract
        ];
    }

    // =========================================================================
    // Moduł PROJEKTY (Projects) - ServiceDesk Plus MSP
    // =========================================================================

    public function syncSDProjects(): int
    {
        Logger::info('Starting ServiceDesk projects synchronization');

        try {
            $startIndex = 1;
            $rowCount = 100;
            $totalSynced = 0;
            $sdProjectModel = new ServiceDeskProject();

            do {
                $params = [
                    'input_data' => json_encode([
                        'list_info' => [
                            'row_count' => $rowCount,
                            'start_index' => $startIndex,
                            'sort_field' => 'title',
                            'sort_order' => 'asc'
                        ]
                    ])
                ];

                $response = $this->apiRequest('projects', $params);

                if (!isset($response['projects']) || empty($response['projects'])) {
                    break;
                }

                foreach ($response['projects'] as $sdProject) {
                    try {
                        $projectData = $this->mapSDProjectData($sdProject);
                        $sdProjectModel->upsertFromSD($sdProject['id'], $projectData);
                        $totalSynced++;
                    } catch (\Exception $e) {
                        Logger::error('Failed to sync SD project', [
                            'sd_project_id' => $sdProject['id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $startIndex += $rowCount;
                $hasMore = isset($response['list_info']['has_more_rows'])
                    ? $response['list_info']['has_more_rows']
                    : count($response['projects']) >= $rowCount;

            } while ($hasMore);

            Logger::info('ServiceDesk projects synchronization completed', [
                'synced_count' => $totalSynced
            ]);

            return $totalSynced;
        } catch (\Exception $e) {
            Logger::error('ServiceDesk projects synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getSDProjectDetails(string $projectId): ?array
    {
        try {
            $response = $this->apiRequest("projects/{$projectId}");
            return $response['project'] ?? $response;
        } catch (\Exception $e) {
            Logger::error('Failed to get project details from ServiceDesk', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function mapSDProjectData(array $sdProject): array
    {
        $ownerName = null;
        $ownerEmail = null;
        if (isset($sdProject['owner'])) {
            $ownerName = $sdProject['owner']['name'] ?? $sdProject['owner'] ?? null;
            $ownerEmail = $sdProject['owner']['email_id'] ?? null;
        }

        return [
            'project_name' => $sdProject['title'] ?? $sdProject['name'] ?? $sdProject['project_name'] ?? 'Bez nazwy',
            'project_code' => $sdProject['project_code'] ?? $sdProject['code'] ?? null,
            'owner_name' => $ownerName,
            'owner_email' => $ownerEmail,
            'status' => $sdProject['status']['name']
                ?? $sdProject['status']
                ?? null,
            'priority' => $sdProject['priority']['name']
                ?? $sdProject['priority']
                ?? null,
            'start_date' => $this->parseSdDate($sdProject['scheduled_start_date']
                ?? $sdProject['start_date'] ?? null),
            'end_date' => $this->parseSdDate($sdProject['scheduled_end_date']
                ?? $sdProject['end_date'] ?? null),
            'actual_start_date' => $this->parseSdDate($sdProject['actual_start_date'] ?? null),
            'actual_end_date' => $this->parseSdDate($sdProject['actual_end_date'] ?? null),
            'scheduled_hours' => $sdProject['scheduled_hours_of_work'] ?? $sdProject['scheduled_hours'] ?? null,
            'actual_hours' => $sdProject['actual_hours_of_work'] ?? $sdProject['actual_hours'] ?? null,
            'description' => $sdProject['description'] ?? null,
            'percentage_completion' => $sdProject['percentage_completion'] ?? $sdProject['percentage_of_completion'] ?? null,
            'raw_data' => $sdProject
        ];
    }

    // =========================================================================
    // Narzędzia wspólne
    // =========================================================================

    private function parseSdDate($dateValue): ?string
    {
        if ($dateValue === null) {
            return null;
        }

        if (is_array($dateValue) && isset($dateValue['value'])) {
            return date('Y-m-d', $dateValue['value'] / 1000);
        }

        if (is_numeric($dateValue)) {
            return date('Y-m-d', $dateValue / 1000);
        }

        if (is_string($dateValue)) {
            $ts = strtotime($dateValue);
            return $ts ? date('Y-m-d', $ts) : null;
        }

        return null;
    }

    private function apiRequest(string $endpoint, array $params = []): array
    {
        $url = sprintf(
            '%s/api/v3/%s',
            rtrim($this->config['url'], '/'),
            ltrim($endpoint, '/')
        );

        $params['TECHNICIAN_KEY'] = $this->config['technician_key'];

        $fullUrl = $url . '?' . http_build_query($params);

        $ch = curl_init($fullUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'authtoken: ' . $this->config['api_key']
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Logger::error('ServiceDesk API request failed', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \RuntimeException('ServiceDesk API request failed');
        }

        return json_decode($response, true) ?? [];
    }

    public function syncWorkHours(?string $startDate = null, ?string $endDate = null): int
    {
        Logger::info('Starting ServiceDesk work hours synchronization');

        try {
            $start = $startDate ?? date('Y-m-d', strtotime('-30 days'));
            $end = $endDate ?? date('Y-m-d');

            $params = [
                'input_data' => json_encode([
                    'list_info' => [
                        'row_count' => 500,
                        'start_index' => 1,
                        'search_criteria' => [
                            [
                                'field' => 'timespent_date',
                                'condition' => 'between',
                                'value' => [$start, $end]
                            ]
                        ]
                    ]
                ])
            ];

            $response = $this->apiRequest('requests/timespent', $params);

            if (!isset($response['timespent'])) {
                Logger::warning('No work hours found in ServiceDesk response');
                return 0;
            }

            $workHourModel = new WorkHour();
            $userModel = new User();
            $projectModel = new Project();
            $syncedCount = 0;

            foreach ($response['timespent'] as $timeEntry) {
                try {
                    $user = $this->findUserByEmail($userModel, $timeEntry['technician']['email_id'] ?? null);
                    if (!$user) {
                        Logger::warning('User not found for time entry', [
                            'email' => $timeEntry['technician']['email_id'] ?? 'unknown'
                        ]);
                        continue;
                    }

                    $project = $this->findProjectFromRequest($projectModel, $timeEntry['request'] ?? []);
                    if (!$project) {
                        Logger::warning('Project not found for time entry', [
                            'request_id' => $timeEntry['request']['id'] ?? 'unknown'
                        ]);
                        continue;
                    }

                    $hours = ($timeEntry['time_spent'] ?? 0) / 3600000;

                    $workType = $this->determineWorkType($timeEntry);

                    $workData = [
                        'project_id' => $project['id'],
                        'user_id' => $user['id'],
                        'work_type' => $workType,
                        'hours' => $hours,
                        'work_date' => date('Y-m-d', $timeEntry['timespent_date'] / 1000),
                        'description' => $timeEntry['description'] ?? null,
                        'servicedesk_ticket_id' => $timeEntry['request']['id'] ?? null
                    ];

                    $workHourModel->updateFromServiceDesk($workData);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Logger::error('Failed to sync individual work hour', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Logger::info('ServiceDesk work hours synchronization completed', [
                'synced_count' => $syncedCount
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Logger::error('ServiceDesk synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncHelpdeskTickets(?string $startDate = null, ?string $endDate = null): int
    {
        Logger::info('Starting ServiceDesk helpdesk tickets synchronization');

        try {
            $start = $startDate ?? date('Y-m-d', strtotime('-30 days'));
            $end = $endDate ?? date('Y-m-d');

            $params = [
                'input_data' => json_encode([
                    'list_info' => [
                        'row_count' => 500,
                        'start_index' => 1,
                        'search_criteria' => [
                            [
                                'field' => 'resolved_time',
                                'condition' => 'between',
                                'value' => [$start, $end]
                            ],
                            [
                                'field' => 'status.name',
                                'condition' => 'in',
                                'value' => ['Resolved', 'Closed']
                            ]
                        ]
                    ]
                ])
            ];

            $response = $this->apiRequest('requests', $params);

            if (!isset($response['requests'])) {
                Logger::warning('No tickets found in ServiceDesk response');
                return 0;
            }

            $db = \ITSS\Core\Database::getInstance();
            $userModel = new User();
            $projectModel = new Project();
            $syncedCount = 0;

            foreach ($response['requests'] as $ticket) {
                try {
                    $user = $this->findUserByEmail($userModel, $ticket['technician']['email_id'] ?? null);
                    if (!$user) {
                        continue;
                    }

                    $project = $this->findProjectFromRequest($projectModel, $ticket);

                    $existing = $db->fetchOne(
                        'SELECT id FROM helpdesk_tickets WHERE servicedesk_id = :servicedesk_id',
                        ['servicedesk_id' => $ticket['id']]
                    );

                    $ticketData = [
                        'ticket_id' => $ticket['id'],
                        'user_id' => $user['id'],
                        'project_id' => $project['id'] ?? null,
                        'resolved_date' => date('Y-m-d', $ticket['resolved_time']['value'] / 1000),
                        'ticket_status' => strtolower($ticket['status']['name']),
                        'servicedesk_id' => $ticket['id'],
                        'last_sync_at' => date('Y-m-d H:i:s')
                    ];

                    if ($existing) {
                        $db->update('helpdesk_tickets', $ticketData, 'id = :id', ['id' => $existing['id']]);
                    } else {
                        $db->insert('helpdesk_tickets', $ticketData);
                    }

                    $syncedCount++;
                } catch (\Exception $e) {
                    Logger::error('Failed to sync individual ticket', [
                        'ticket_id' => $ticket['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Logger::info('ServiceDesk helpdesk tickets synchronization completed', [
                'synced_count' => $syncedCount
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Logger::error('ServiceDesk tickets synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function findUserByEmail(User $userModel, ?string $email): ?array
    {
        if (!$email) {
            return null;
        }

        return $userModel->findByEmail($email);
    }

    private function findProjectFromRequest(Project $projectModel, array $request): ?array
    {
        if (isset($request['project']['name'])) {
            $project = $projectModel->findByProjectNumber($request['project']['name']);
            if ($project) {
                return $project;
            }
        }

        if (isset($request['udf_fields'])) {
            foreach ($request['udf_fields'] as $field) {
                if ($field['label'] === 'Project Number' && isset($field['value'])) {
                    return $projectModel->findByProjectNumber($field['value']);
                }
            }
        }

        return null;
    }

    private function determineWorkType(array $timeEntry): string
    {
        $description = strtolower($timeEntry['description'] ?? '');

        if (str_contains($description, 'presales') || str_contains($description, 'pre-sales')) {
            return 'presales';
        }

        if (str_contains($description, 'support') || str_contains($description, 'helpdesk')) {
            return 'support';
        }

        return 'implementation';
    }
}
