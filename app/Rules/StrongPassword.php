<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces a configurable password policy: minimum length, at least one
 * letter and one number.
 */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $min = (int) config('jiwa.password_min_length', 10);

        if (! is_string($value) || mb_strlen($value) < $min) {
            $fail("The password must be at least {$min} characters.");
        }

        if (is_string($value)) {
            if (! preg_match('/[a-zA-Z]/', $value)) {
                $fail('The password must contain at least one letter.');
            }

            if (! preg_match('/[0-9]/', $value)) {
                $fail('The password must contain at least one number.');
            }
        }
    }
}
