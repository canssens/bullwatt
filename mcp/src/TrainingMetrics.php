<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class TrainingMetrics
{
    /** @param array<string, mixed> $training */
    public static function calculate(array $training): array
    {
        $phases = isset($training['phases']) && is_array($training['phases']) ? array_values($training['phases']) : [];
        $duration = self::number($training['duration'] ?? null) ?? 0.0;
        $ftpRatios = [];
        $weightedSum = 0.0;
        $weightedDuration = 0.0;

        foreach ($phases as $index => $phase) {
            if (!is_array($phase)) {
                continue;
            }

            $start = self::number($phase['start'] ?? null);
            $ftpRatio = self::number($phase['ftp_ratio'] ?? null);
            if ($start === null || $ftpRatio === null) {
                continue;
            }

            $nextStart = $duration;
            if (isset($phases[$index + 1]) && is_array($phases[$index + 1])) {
                $candidate = self::number($phases[$index + 1]['start'] ?? null);
                if ($candidate !== null) {
                    $nextStart = min($candidate, $duration);
                }
            }

            $segmentDuration = max(0.0, $nextStart - $start);
            if ($segmentDuration > 0) {
                $ftpRatios[] = $ftpRatio;
            }
            $weightedSum += $ftpRatio * $segmentDuration;
            $weightedDuration += $segmentDuration;
        }

        return [
            'duration' => self::normalNumber($duration),
            'phase_count' => count($phases),
            'minimum_ftp_ratio' => $ftpRatios === [] ? null : self::normalNumber(min($ftpRatios)),
            'maximum_ftp_ratio' => $ftpRatios === [] ? null : self::normalNumber(max($ftpRatios)),
            'weighted_average_ftp_ratio' => $weightedDuration <= 0
                ? null
                : round($weightedSum / $weightedDuration, 6),
        ];
    }

    public static function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/^-?(?:\d+\.?\d*|\.\d+)$/D', $value) === 1) {
            return (float) $value;
        }

        return null;
    }

    private static function normalNumber(float $value): int|float
    {
        return floor($value) === $value ? (int) $value : $value;
    }
}
