<?php

namespace ITSS\Services;

use ITSS\Core\Logger;
use ITSS\Models\WorkHour;
use ITSS\Models\User;
use ITSS\Models\Project;

class ServiceDeskService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
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
