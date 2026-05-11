<?php
declare(strict_types=1);

namespace App\Helpers;

class ValidationHelper
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->errors[$field] = "O campo {$label} é obrigatório.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "O campo {$label} deve ser um e-mail válido.";
            }
        }
        return $this;
    }

    public function min(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) < $min) {
            $this->errors[$field] = "O campo {$label} deve ter no mínimo {$min} caracteres.";
        }
        return $this;
    }

    public function max(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) > $max) {
            $this->errors[$field] = "O campo {$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    public function confirmed(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        $confirmField = $field . '_confirmation';
        if (isset($this->data[$field]) && ($this->data[$field] !== ($this->data[$confirmField] ?? ''))) {
            $this->errors[$field] = "O campo {$label} e sua confirmação não coincidem.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field] = "O campo {$label} deve ser numérico.";
            }
        }
        return $this;
    }

    public function date(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $formats = ['Y-m-d', 'd/m/Y'];
            $valid = false;
            foreach ($formats as $format) {
                $d = \DateTime::createFromFormat($format, $this->data[$field]);
                if ($d && $d->format($format) === $this->data[$field]) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                $this->errors[$field] = "O campo {$label} deve ser uma data válida.";
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }
}
