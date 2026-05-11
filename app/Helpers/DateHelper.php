<?php
declare(strict_types=1);

namespace App\Helpers;

use Core\Database;
use PDO;

class DateHelper
{
    private static array $holidays = [];

    public static function formatBr(string $date): string
    {
        if (empty($date)) return '-';
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : $date;
    }

    public static function formatBrDateTime(string $datetime): string
    {
        if (empty($datetime)) return '-';
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d ? $d->format('d/m/Y H:i') : $datetime;
    }

    public static function fromBr(string $date): ?string
    {
        if (empty($date)) return null;
        $d = \DateTime::createFromFormat('d/m/Y', $date);
        return $d ? $d->format('Y-m-d') : null;
    }

    public static function calculateDeadline(string $startDate, int $days, string $tipo = 'processual', string $state = ''): string
    {
        $date = new \DateTime($startDate);
        if ($tipo === 'processual') {
            return self::addBusinessDays($date, $days, $state);
        }
        $date->modify("+{$days} days");
        return $date->format('Y-m-d');
    }

    public static function addBusinessDays(\DateTime $date, int $days, string $state = ''): string
    {
        self::loadHolidays($state);
        $added = 0;
        $current = clone $date;
        $current->modify('+1 day');

        while ($added < $days) {
            $dow = (int)$current->format('N');
            $dateStr = $current->format('Y-m-d');
            $isWeekend = $dow >= 6;
            $isHoliday = in_array($dateStr, self::$holidays);

            if (!$isWeekend && !$isHoliday) {
                $added++;
            }

            if ($added < $days) {
                $current->modify('+1 day');
            }
        }

        return $current->format('Y-m-d');
    }

    private static function loadHolidays(string $state = ''): void
    {
        if (!empty(self::$holidays)) return;
        try {
            $db = Database::getInstance();
            $year = date('Y');
            $sql = "SELECT data FROM holidays WHERE (tipo = 'nacional' OR (tipo = 'estadual' AND estado = ?))
                    AND (YEAR(data) = ? OR recorrente = 1)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$state ?: '', $year]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $h) {
                // Handle recurring holidays (same month-day, any year)
                $monthDay = substr($h, 5);
                self::$holidays[] = $year . '-' . $monthDay;
            }
        } catch (\Exception $e) {
            self::$holidays = [];
        }
    }

    public static function daysUntil(string $date): int
    {
        $now  = new \DateTime('today');
        $target = new \DateTime($date);
        $diff = $now->diff($target);
        return (int)($diff->invert ? -$diff->days : $diff->days);
    }

    public static function isOverdue(string $date): bool
    {
        return self::daysUntil($date) < 0;
    }

    public static function isToday(string $date): bool
    {
        return $date === date('Y-m-d');
    }

    public static function humanDiff(string $date): string
    {
        $days = self::daysUntil($date);
        if ($days === 0) return 'Hoje';
        if ($days === 1) return 'Amanhã';
        if ($days === -1) return 'Ontem';
        if ($days > 0) return "Em {$days} dias";
        return abs($days) . ' dias atrás';
    }

    public static function monthName(int $month): string
    {
        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];
        return $months[$month] ?? '';
    }
}
