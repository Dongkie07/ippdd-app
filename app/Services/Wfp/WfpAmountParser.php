<?php

namespace App\Services\Wfp;

class WfpAmountParser
{
    public function parse(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $cleaned = preg_replace('/[₱,\s\x{00A0}]/u', '', (string) $value);
        $cleaned = trim(explode("\n", $cleaned)[0]);
        $number = filter_var($cleaned, FILTER_VALIDATE_FLOAT);

        return $number !== false && $number >= 0 ? round((float) $number, 2) : 0.0;
    }
}
