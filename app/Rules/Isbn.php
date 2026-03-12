<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Isbn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid ISBN-10 or ISBN-13.');

            return;
        }

        $stripped = str_replace('-', '', $value);

        if ($this->isValidIsbn13($stripped)) {
            return;
        }

        if ($this->isValidIsbn10($stripped)) {
            return;
        }

        $fail('The :attribute must be a valid ISBN-10 or ISBN-13.');
    }

    private function isValidIsbn13(string $isbn): bool
    {
        if (strlen($isbn) !== 13 || ! ctype_digit($isbn)) {
            return false;
        }

        if (! str_starts_with($isbn, '978') && ! str_starts_with($isbn, '979')) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $isbn[12];
    }

    private function isValidIsbn10(string $isbn): bool
    {
        if (strlen($isbn) !== 10) {
            return false;
        }

        if (! ctype_digit(substr($isbn, 0, 9))) {
            return false;
        }

        $lastChar = strtoupper($isbn[9]);
        if (! ctype_digit($lastChar) && $lastChar !== 'X') {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }

        $sum += $lastChar === 'X' ? 10 : (int) $lastChar;

        return $sum % 11 === 0;
    }
}
