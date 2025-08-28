<?php

declare(strict_types=1);

namespace App\Support;

final class Helper
{
    /**
     * Convert string or DateTimeImmutable to DateTimeImmutable
     * 
     * @param string|\DateTimeImmutable|null $value
     * @return \DateTimeImmutable|null
     * @throws \DateMalformedStringException  if string cannot be parsed as date
     */
    public static function convertToDateTimeImmutable(string|\DateTimeImmutable|null $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        // Handle string input - convert to DateTimeImmutable
        return new \DateTimeImmutable($value);
    }
}