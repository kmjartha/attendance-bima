<?php

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Static factory: Validator::make($data, ['name'=>'required|max:120', ...])
     */
    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        foreach ($rules as $field => $ruleStr) {
            $rules2 = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            foreach ($rules2 as $r) {
                $param = null;
                if (str_contains($r, ':')) {
                    [$r, $param] = explode(':', $r, 2);
                }
                switch ($r) {
                    case 'required': $v->required($field); break;
                    case 'min':      $v->min($field, (int)$param); break;
                    case 'max':      $v->max($field, (int)$param); break;
                    case 'email':    $v->email($field); break;
                    case 'numeric':  $v->numeric($field); break;
                    case 'integer':  $v->numeric($field); break;
                }
            }
        }
        return $v;
    }

    public function addError(string $key, string $msg): self
    {
        $this->errors[$key] = $msg;
        return $this;
    }

    public function required(string $key, string $label = null): self
    {
        $val = trim((string)($this->data[$key] ?? ''));
        if ($val === '') {
            $this->errors[$key] = ($label ?: $key) . ' wajib diisi.';
        }
        return $this;
    }

    public function min(string $key, int $len, string $label = null): self
    {
        $val = (string)($this->data[$key] ?? '');
        if (strlen($val) < $len) {
            $this->errors[$key] = ($label ?: $key) . " minimal {$len} karakter.";
        }
        return $this;
    }

    public function max(string $key, int $len, string $label = null): self
    {
        $val = (string)($this->data[$key] ?? '');
        if (strlen($val) > $len) {
            $this->errors[$key] = ($label ?: $key) . " maksimal {$len} karakter.";
        }
        return $this;
    }

    public function email(string $key, string $label = null): self
    {
        $val = (string)($this->data[$key] ?? '');
        if ($val && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$key] = ($label ?: $key) . ' bukan email valid.';
        }
        return $this;
    }

    public function numeric(string $key, string $label = null): self
    {
        $val = (string)($this->data[$key] ?? '');
        if ($val !== '' && !is_numeric($val)) {
            $this->errors[$key] = ($label ?: $key) . ' harus angka.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }
}
