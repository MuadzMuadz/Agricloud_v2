<?php

use Illuminate\Support\Carbon;

if (!function_exists('formatDate')) {
    /**
     * Format a date to a human-readable string.
     *
     * @param  string|\DateTimeInterface|null  $date
     * @param  string  $format
     * @return string|null
     */
    function formatDate($date)
    {
        $d = Carbon::parse($date);

        return [
            'full' => $d->translatedFormat('l, d F Y'),
            'short' => $d->translatedFormat('d M Y'),
            'iso' => $d->toDateString(),
        ];
    }
}