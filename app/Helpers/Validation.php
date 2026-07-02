<?php
declare(strict_types=1);

namespace App\Helpers;

class Validation
{
    private array $errors = [];

    public function required(string $field, $value): self
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = "Trường '$field' là bắt buộc.";
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Trường '$field' phải là email hợp lệ.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min): self
    {
        if (strlen($value) < $min) {
            $this->errors[$field][] = "Trường '$field' phải có ít nhất $min ký tự.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max): self
    {
        if (strlen($value) > $max) {
            $this->errors[$field][] = "Trường '$field' không được vượt quá $max ký tự.";
        }
        return $this;
    }

    public function numeric(string $field, $value): self
    {
        if (!is_numeric($value)) {
            $this->errors[$field][] = "Trường '$field' phải là số.";
        }
        return $this;
    }

    public function inArray(string $field, $value, array $allowed): self
    {
        if (!in_array($value, $allowed)) {
            $this->errors[$field][] = "Trường '$field' không hợp lệ.";
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}