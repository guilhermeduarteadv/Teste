<?php
declare(strict_types=1);

namespace App\Helpers;

class FormatHelper
{
    public static function money(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public static function cpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    public static function cnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) return $cnpj;
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    public static function phone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        }
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        }
        return $phone;
    }

    public static function cep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $cep;
    }

    public static function fileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public static function truncate(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length) . '...';
    }

    public static function statusBadge(string $status): string
    {
        $badges = [
            'ativo'       => 'success',
            'active'      => 'success',
            'arquivado'   => 'secondary',
            'suspenso'    => 'warning',
            'encerrado'   => 'dark',
            'pendente'    => 'warning',
            'parcial'     => 'info',
            'pago'        => 'success',
            'vencido'     => 'danger',
            'cancelado'   => 'secondary',
            'em_andamento' => 'info',
            'concluida'   => 'success',
            'aguardando'  => 'warning',
            'inactive'    => 'secondary',
            'blocked'     => 'danger',
        ];
        $class = $badges[$status] ?? 'secondary';
        $labels = [
            'ativo'       => 'Ativo',
            'active'      => 'Ativo',
            'arquivado'   => 'Arquivado',
            'suspenso'    => 'Suspenso',
            'encerrado'   => 'Encerrado',
            'pendente'    => 'Pendente',
            'parcial'     => 'Parcial',
            'pago'        => 'Pago',
            'vencido'     => 'Vencido',
            'cancelado'   => 'Cancelado',
            'em_andamento' => 'Em Andamento',
            'concluida'   => 'Concluída',
            'aguardando'  => 'Aguardando',
            'inactive'    => 'Inativo',
            'blocked'     => 'Bloqueado',
        ];
        $label = $labels[$status] ?? ucfirst($status);
        return "<span class=\"badge bg-{$class}\">{$label}</span>";
    }

    public static function priorityBadge(string $priority): string
    {
        $map = [
            'baixa'   => ['color' => 'success', 'label' => 'Baixa'],
            'media'   => ['color' => 'warning', 'label' => 'Média'],
            'alta'    => ['color' => 'danger',  'label' => 'Alta'],
            'urgente' => ['color' => 'dark',    'label' => 'Urgente'],
        ];
        $d = $map[$priority] ?? ['color' => 'secondary', 'label' => ucfirst($priority)];
        return "<span class=\"badge bg-{$d['color']}\">{$d['label']}</span>";
    }

    public static function riskBadge(string $risk): string
    {
        $map = [
            'baixo'  => ['color' => 'success', 'label' => 'Baixo'],
            'medio'  => ['color' => 'warning', 'label' => 'Médio'],
            'alto'   => ['color' => 'danger',  'label' => 'Alto'],
            'critico' => ['color' => 'dark',   'label' => 'Crítico'],
        ];
        $d = $map[$risk] ?? ['color' => 'secondary', 'label' => ucfirst($risk)];
        return "<span class=\"badge bg-{$d['color']}\">{$d['label']}</span>";
    }
}
