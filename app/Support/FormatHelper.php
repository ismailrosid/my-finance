<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class FormatHelper
{
    /**
     * Format a number according to active locale.
     * English: 1,234.56
     * Indonesian: 1.234,56
     */
    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        $val = (float) ($value ?? 0);
        $locale = app()->getLocale();

        if ($locale === 'en') {
            return number_format($val, $decimals, '.', ',');
        }

        return number_format($val, $decimals, ',', '.');
    }

    /**
     * Format a currency value according to active locale while maintaining currency code.
     */
    public static function currency(float|int|string|null $value, string $prefix = 'Rp', int $decimals = 0): string
    {
        $formatted = self::number($value, $decimals);
        $cleanPrefix = trim($prefix);

        return $cleanPrefix ? "{$cleanPrefix} {$formatted}" : $formatted;
    }

    /**
     * Format date according to active locale.
     * English: Nov 14, 2026
     * Indonesian: 14 Nov 2026
     */
    public static function date(Carbon|DateTimeInterface|string|null $date, ?string $format = null): string
    {
        if (!$date) {
            return '—';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $locale = app()->getLocale();

        if ($format) {
            return $carbon->translatedFormat($format);
        }

        if ($locale === 'en') {
            return $carbon->translatedFormat('M j, Y');
        }

        return $carbon->translatedFormat('j M Y');
    }
}
