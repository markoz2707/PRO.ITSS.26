<?php

namespace ITSS\Services;

use ITSS\Core\Database;
use ITSS\Core\Logger;
use ITSS\Models\BonusScheme;
use ITSS\Models\CalculatedBonus;
use ITSS\Models\Project;
use ITSS\Models\WorkHour;

class BonusCalculationService
{
    private Database $db;
    private BonusScheme $bonusSchemeModel;
    private CalculatedBonus $calculatedBonusModel;
    private Project $projectModel;
    private WorkHour $workHourModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->bonusSchemeModel = new BonusScheme();
        $this->calculatedBonusModel = new CalculatedBonus();
        $this->projectModel = new Project();
        $this->workHourModel = new WorkHour();
    }

    public function calculateBonusForUser(
        int $userId,
        string $periodStart,
        string $periodEnd,
        ?int $projectId = null
    ): array {
        Logger::info('Calculating bonus', [
            'user_id' => $userId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'project_id' => $projectId
        ]);

        $schemes = $this->bonusSchemeModel->getActive($userId, $projectId, $periodEnd);
        $results = [];

        foreach ($schemes as $scheme) {
            try {
                $bonus = $this->calculateSingleBonus($scheme, $userId, $periodStart, $periodEnd);
                if ($bonus) {
                    $results[] = $bonus;
                }
            } catch (\Exception $e) {
                Logger::error('Failed to calculate bonus for scheme', [
                    'scheme_id' => $scheme['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    private function calculateSingleBonus(
        array $scheme,
        int $userId,
        string $periodStart,
        string $periodEnd
    ): ?array {
        $projectId = $scheme['project_id'];

        switch ($scheme['bonus_type']) {
            case 'margin_1':
                return $this->calculateMarginBonus($scheme, $userId, $periodStart, $periodEnd, 1);

            case 'margin_2':
                return $this->calculateMarginBonus($scheme, $userId, $periodStart, $periodEnd, 2);

            case 'hourly_rate':
                return $this->calculateHourlyRateBonus($scheme, $userId, $periodStart, $periodEnd);

            case 'tickets_fixed':
            case 'tickets_percent':
                return $this->calculateTicketsBonus($scheme, $userId, $periodStart, $periodEnd);

            default:
                Logger::warning('Unknown bonus type', ['bonus_type' => $scheme['bonus_type']]);
                return null;
        }
    }

    private function calculateMarginBonus(
        array $scheme,
        int $userId,
        string $periodStart,
        string $periodEnd,
        int $marginType
    ): ?array {
        if (!$scheme['project_id']) {
            Logger::warning('Margin bonus requires project_id', ['scheme_id' => $scheme['id']]);
            return null;
        }

        $financials = $this->projectModel->getProjectFinancials($scheme['project_id']);

        $margin = $marginType === 1 ? $financials['margin_1'] : $financials['margin_2'];

        if ($margin <= 0) {
            Logger::info('No margin for bonus calculation', [
                'project_id' => $scheme['project_id'],
                'margin_type' => $marginType,
                'margin' => $margin
            ]);
            return null;
        }

        $bonusAmount = ($margin * $scheme['percentage']) / 100;

        $details = [
            'margin_type' => $marginType,
            'total_revenue' => $financials['total_revenue'],
            'total_costs' => $financials['total_costs'],
            'margin' => $margin,
            'percentage' => $scheme['percentage'],
            'calculation' => "({$margin} * {$scheme['percentage']}%) = {$bonusAmount}"
        ];

        $bonusId = $this->calculatedBonusModel->create([
            'user_id' => $userId,
            'project_id' => $scheme['project_id'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'bonus_scheme_id' => $scheme['id'],
            'calculation_base' => $margin,
            'bonus_amount' => $bonusAmount,
            'status' => 'draft',
            'calculation_details' => $details
        ]);

        return array_merge(['id' => $bonusId], $details, ['bonus_amount' => $bonusAmount]);
    }

    private function calculateHourlyRateBonus(
        array $scheme,
        int $userId,
        string $periodStart,
        string $periodEnd
    ): ?array {
        $sql = '
            SELECT SUM(hours) as total_hours
            FROM work_hours
            WHERE user_id = :user_id
            AND work_date BETWEEN :start_date AND :end_date
            AND work_type IN ("implementation", "presales")
        ';

        $params = [
            'user_id' => $userId,
            'start_date' => $periodStart,
            'end_date' => $periodEnd
        ];

        if ($scheme['project_id']) {
            $sql .= ' AND project_id = :project_id';
            $params['project_id'] = $scheme['project_id'];
        }

        $result = $this->db->fetchOne($sql, $params);
        $totalHours = floatval($result['total_hours'] ?? 0);

        if ($totalHours <= 0) {
            Logger::info('No hours for bonus calculation', [
                'user_id' => $userId,
                'project_id' => $scheme['project_id']
            ]);
            return null;
        }

        $bonusAmount = $totalHours * $scheme['hourly_rate'];

        $details = [
            'total_hours' => $totalHours,
            'hourly_rate' => $scheme['hourly_rate'],
            'calculation' => "({$totalHours} hours * {$scheme['hourly_rate']}) = {$bonusAmount}"
        ];

        $bonusId = $this->calculatedBonusModel->create([
            'user_id' => $userId,
            'project_id' => $scheme['project_id'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'bonus_scheme_id' => $scheme['id'],
            'calculation_base' => $totalHours,
            'bonus_amount' => $bonusAmount,
            'status' => 'draft',
            'calculation_details' => $details
        ]);

        return array_merge(['id' => $bonusId], $details, ['bonus_amount' => $bonusAmount]);
    }

    private function calculateTicketsBonus(
        array $scheme,
        int $userId,
        string $periodStart,
        string $periodEnd
    ): ?array {
        $sql = '
            SELECT COUNT(*) as ticket_count
            FROM helpdesk_tickets
            WHERE user_id = :user_id
            AND resolved_date BETWEEN :start_date AND :end_date
            AND ticket_status IN ("resolved", "closed")
        ';

        $params = [
            'user_id' => $userId,
            'start_date' => $periodStart,
            'end_date' => $periodEnd
        ];

        if ($scheme['project_id']) {
            $sql .= ' AND project_id = :project_id';
            $params['project_id'] = $scheme['project_id'];
        }

        $result = $this->db->fetchOne($sql, $params);
        $ticketCount = intval($result['ticket_count'] ?? 0);

        if ($ticketCount <= 0) {
            Logger::info('No tickets for bonus calculation', [
                'user_id' => $userId,
                'project_id' => $scheme['project_id']
            ]);
            return null;
        }

        if ($scheme['bonus_type'] === 'tickets_fixed') {
            $bonusAmount = $ticketCount * $scheme['fixed_amount'];
            $calculation = "({$ticketCount} tickets * {$scheme['fixed_amount']}) = {$bonusAmount}";
        } else {
            $bonusAmount = ($scheme['tickets_pool'] * $scheme['percentage']) / 100;
            $calculation = "({$scheme['tickets_pool']} * {$scheme['percentage']}%) = {$bonusAmount}";
        }

        $details = [
            'ticket_count' => $ticketCount,
            'bonus_type' => $scheme['bonus_type'],
            'calculation' => $calculation
        ];

        if ($scheme['bonus_type'] === 'tickets_percent') {
            $details['tickets_pool'] = $scheme['tickets_pool'];
            $details['percentage'] = $scheme['percentage'];
        } else {
            $details['fixed_amount'] = $scheme['fixed_amount'];
        }

        $bonusId = $this->calculatedBonusModel->create([
            'user_id' => $userId,
            'project_id' => $scheme['project_id'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'bonus_scheme_id' => $scheme['id'],
            'calculation_base' => $ticketCount,
            'bonus_amount' => $bonusAmount,
            'status' => 'draft',
            'calculation_details' => $details
        ]);

        return array_merge(['id' => $bonusId], $details, ['bonus_amount' => $bonusAmount]);
    }

    public function calculateBonusesForPeriod(string $periodStart, string $periodEnd): array
    {
        Logger::info('Calculating bonuses for period', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd
        ]);

        $sql = '
            SELECT DISTINCT user_id
            FROM bonus_schemes
            WHERE is_active = 1
            AND valid_from <= :end_date
            AND (valid_to IS NULL OR valid_to >= :start_date)
        ';

        $users = $this->db->fetchAll($sql, [
            'start_date' => $periodStart,
            'end_date' => $periodEnd
        ]);

        $allResults = [];

        foreach ($users as $user) {
            $results = $this->calculateBonusForUser(
                $user['user_id'],
                $periodStart,
                $periodEnd
            );
            $allResults = array_merge($allResults, $results);
        }

        Logger::info('Period bonus calculation completed', [
            'total_bonuses' => count($allResults)
        ]);

        return $allResults;
    }
}
