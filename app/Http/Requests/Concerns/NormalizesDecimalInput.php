<?php

namespace App\Http\Requests\Concerns;

/**
 * A native `<input type="number" step="0.1">` displays and — critically —
 * *submits* its value using the browser/OS locale's decimal separator. On a
 * Spanish-locale machine (the norm for this app's audience) that's a comma:
 * "0,6" instead of "0.6". Laravel's `numeric`/`decimal` rules only accept a
 * period, so every such field would silently fail validation for exactly
 * the users this app is built for. Normalize before validating rather than
 * trusting the client to send a period.
 */
trait NormalizesDecimalInput
{
    protected function normalizeDecimals(array $keys): void
    {
        $normalized = [];

        foreach ($keys as $key) {
            $value = $this->input($key);

            if (is_string($value) && $value !== '') {
                $normalized[$key] = str_replace(',', '.', $value);
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
