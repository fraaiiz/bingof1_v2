<?php

namespace App\Service;

use InvalidArgumentException;

final class RaceTime
{
    public static function parseMilliseconds(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('Format de temps invalide.');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $parts = explode(':', $value);
        if (count($parts) !== 2 && count($parts) !== 3) {
            throw new InvalidArgumentException('Utilise le format mm:ss.mmm ou hh:mm:ss.mmm.');
        }

        $lastPart = array_pop($parts);
        if (!preg_match('/^(\d{1,2})(?:\.(\d{1,3}))?$/', $lastPart, $secondsMatch)) {
            throw new InvalidArgumentException('Utilise le format mm:ss.mmm ou hh:mm:ss.mmm.');
        }

        $seconds = (int) $secondsMatch[1];
        if ($seconds > 59) {
            throw new InvalidArgumentException('Les secondes doivent être comprises entre 00 et 59.');
        }

        $fraction = str_pad($secondsMatch[2] ?? '', 3, '0', STR_PAD_RIGHT);
        $milliseconds = (int) $fraction;

        if (count($parts) === 1) {
            if (!preg_match('/^\d{1,6}$/', $parts[0])) {
                throw new InvalidArgumentException('Le nombre de minutes est invalide.');
            }

            $totalSeconds = ((int) $parts[0] * 60) + $seconds;
        } else {
            if (!preg_match('/^\d{1,6}$/', $parts[0])
                || !preg_match('/^\d{1,2}$/', $parts[1])
                || (int) $parts[1] > 59) {
                throw new InvalidArgumentException('Le format heures, minutes ou secondes est invalide.');
            }

            $totalSeconds = (((int) $parts[0] * 60) + (int) $parts[1]) * 60 + $seconds;
        }

        if ($totalSeconds > intdiv(2147483647 - $milliseconds, 1000)) {
            throw new InvalidArgumentException('Le temps dépasse la valeur maximale autorisée.');
        }

        return ($totalSeconds * 1000) + $milliseconds;
    }

    public static function formatMilliseconds(?int $milliseconds): string
    {
        if ($milliseconds === null) {
            return '';
        }

        $totalSeconds = intdiv($milliseconds, 1000);
        $fraction = $milliseconds % 1000;
        $seconds = $totalSeconds % 60;
        $totalMinutes = intdiv($totalSeconds, 60);

        if ($totalMinutes >= 60) {
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;

            return sprintf('%d:%02d:%02d.%03d', $hours, $minutes, $seconds, $fraction);
        }

        return sprintf('%02d:%02d.%03d', $totalMinutes, $seconds, $fraction);
    }
}