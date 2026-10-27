<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === [])) {
            $this->fail($field, $label . ' wajib diisi.');
        }
        return $this;
    }

    public function length(string $field, string $label, int $min, int $max): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || !is_scalar($value) || trim((string) $value) === '') {
            return $this;
        }
        $length = mb_strlen(trim((string) $value));
        if ($length < $min) {
            $this->fail($field, $label . ' minimal ' . $min . ' karakter.');
        } elseif ($length > $max) {
            $this->fail($field, $label . ' maksimal ' . $max . ' karakter.');
        }
        return $this;
    }

    public function minLength(string $field, string $label, int $min): self
    {
        $value = $this->data[$field] ?? null;
        if (is_scalar($value) && trim((string) $value) !== '' && mb_strlen(trim((string) $value)) < $min) {
            $this->fail($field, $label . ' minimal ' . $min . ' karakter.');
        }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        $value = $this->data[$field] ?? null;
        if (is_scalar($value) && mb_strlen(trim((string) $value)) > $max) {
            $this->fail($field, $label . ' maksimal ' . $max . ' karakter.');
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || !is_scalar($value) || trim((string) $value) === '') {
            return $this;
        }
        if (filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL) === false) {
            $this->fail($field, $label . ' tidak valid.');
        }
        return $this;
    }

    public function in(string $field, string $label, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->fail($field, $label . ' tidak valid.');
        }
        return $this;
    }

    public function same(string $field, string $label, string $otherField): self
    {
        $value = $this->data[$field] ?? null;
        $other = $this->data[$otherField] ?? null;
        if ($value !== $other) {
            $this->fail($field, $label . ' tidak cocok.');
        }
        return $this;
    }

    public function integer(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->fail($field, $label . ' harus berupa angka.');
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function fail(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}
