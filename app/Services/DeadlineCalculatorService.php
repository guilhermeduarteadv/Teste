<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class DeadlineCalculatorService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Calcula a data final a partir de uma data inicial e número de dias.
     *
     * @param string $startDate  Data inicial no formato Y-m-d
     * @param int    $days       Quantidade de dias
     * @param bool   $businessDays Usar apenas dias úteis?
     * @param string $state      UF para feriados estaduais (ex: 'SP')
     * @return string Data final no formato Y-m-d
     */
    public function calculate(string $startDate, int $days, bool $businessDays = true, string $state = ''): string
    {
        if ($businessDays) {
            return $this->addBusinessDays($startDate, $days, $state);
        }

        $date = new \DateTime($startDate);
        $date->modify('+' . $days . ' days');
        return $date->format('Y-m-d');
    }

    /**
     * Retorna feriados no intervalo entre duas datas.
     *
     * @param string $startDate  Y-m-d
     * @param string $endDate    Y-m-d
     * @param string $state      UF opcional
     * @return array
     */
    public function getHolidays(string $startDate, string $endDate, string $state = ''): array
    {
        try {
            if ($state !== '') {
                $stmt = $this->db->prepare(
                    "SELECT * FROM holidays
                     WHERE date BETWEEN ? AND ?
                       AND (scope = 'nacional' OR (scope = 'estadual' AND state = ?))
                       AND active = 1
                       AND deleted_at IS NULL
                     ORDER BY date ASC"
                );
                $stmt->execute([$startDate, $endDate, $state]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT * FROM holidays
                     WHERE date BETWEEN ? AND ?
                       AND scope = 'nacional'
                       AND active = 1
                       AND deleted_at IS NULL
                     ORDER BY date ASC"
                );
                $stmt->execute([$startDate, $endDate]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Verifica se uma data está em um período de suspensão de prazos.
     *
     * @param string $date  Y-m-d
     * @param string $state UF opcional
     * @return bool
     */
    public function isSuspended(string $date, string $state = ''): bool
    {
        try {
            if ($state !== '') {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM deadline_suspensions
                     WHERE ? BETWEEN start_date AND end_date
                       AND (scope = 'nacional' OR (scope = 'estadual' AND state = ?))
                       AND active = 1"
                );
                $stmt->execute([$date, $state]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM deadline_suspensions
                     WHERE ? BETWEEN start_date AND end_date
                       AND scope = 'nacional'
                       AND active = 1"
                );
                $stmt->execute([$date]);
            }
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Adiciona dias úteis a uma data, pulando finais de semana, feriados e suspensões.
     *
     * @param string $startDate  Y-m-d
     * @param int    $days       Número de dias úteis a adicionar
     * @param string $state      UF opcional para feriados estaduais
     * @return string Data final Y-m-d
     */
    public function addBusinessDays(string $startDate, int $days, string $state = ''): string
    {
        $current = new \DateTime($startDate);
        $added = 0;
        $maxIterations = $days * 4 + 60; // segurança anti-loop infinito
        $iterations = 0;

        while ($added < $days && $iterations < $maxIterations) {
            $current->modify('+1 day');
            $iterations++;

            $dow = (int)$current->format('N'); // 6=sáb, 7=dom
            if ($dow >= 6) {
                continue;
            }

            $dateStr = $current->format('Y-m-d');

            if ($this->isHoliday($dateStr, $state)) {
                continue;
            }

            if ($this->isSuspended($dateStr, $state)) {
                continue;
            }

            $added++;
        }

        return $current->format('Y-m-d');
    }

    /**
     * Verifica se uma data é feriado.
     *
     * @param string $date  Y-m-d
     * @param string $state UF opcional
     * @return bool
     */
    private function isHoliday(string $date, string $state = ''): bool
    {
        try {
            if ($state !== '') {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM holidays
                     WHERE date = ?
                       AND (scope = 'nacional' OR (scope = 'estadual' AND state = ?))
                       AND active = 1
                       AND deleted_at IS NULL"
                );
                $stmt->execute([$date, $state]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM holidays
                     WHERE date = ?
                       AND scope = 'nacional'
                       AND active = 1
                       AND deleted_at IS NULL"
                );
                $stmt->execute([$date]);
            }
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
