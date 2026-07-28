<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class TrainingSearch
{
    public function __construct(private readonly CatalogRepository $repository)
    {
    }

    /** @return array{trainings: list<array<string, mixed>>} */
    public function search(
        ?string $query = null,
        ?int $durationMin = null,
        ?int $durationMax = null,
        ?float $minIntensity = null,
        ?float $maxIntensity = null,
        ?int $phaseCount = null,
        int $maxResults = 100,
    ): array {
        $query = trim((string) $query);
        $tokens = array_values(array_filter(preg_split('/[^a-z0-9]+/', self::normalize($query)) ?: [], static fn (string $token): bool => strlen($token) > 1));
        $matches = [];

        foreach ($this->repository->all() as $training) {
            $metrics = TrainingMetrics::calculate($training);
            $duration = (int) ($training['duration'] ?? 0);
            if ($durationMin !== null && $duration < $durationMin) {
                continue;
            }
            if ($durationMax !== null && $duration > $durationMax) {
                continue;
            }
            if ($minIntensity !== null && ($metrics['minimum_ftp_ratio'] === null || $metrics['minimum_ftp_ratio'] < $minIntensity)) {
                continue;
            }
            if ($maxIntensity !== null && ($metrics['maximum_ftp_ratio'] === null || $metrics['maximum_ftp_ratio'] > $maxIntensity)) {
                continue;
            }
            if ($phaseCount !== null && $metrics['phase_count'] !== $phaseCount) {
                continue;
            }

            $haystack = self::normalize($this->searchableText($training));
            $score = 0;
            foreach ($tokens as $token) {
                if (!str_contains($haystack, $token)) {
                    $score = -1;
                    break;
                }
                $score += substr_count($haystack, $token);
            }
            if ($score < 0) {
                continue;
            }

            $matches[] = [
                'score' => $score,
                'summary' => [
                    'id' => (string) ($training['id'] ?? ''),
                    'training_name' => (string) ($training['training_name'] ?? ''),
                    'duration' => $duration,
                    'description' => (string) ($training['description'] ?? ''),
                    'phase_count' => $metrics['phase_count'],
                    'minimum_ftp_ratio' => $metrics['minimum_ftp_ratio'],
                    'maximum_ftp_ratio' => $metrics['maximum_ftp_ratio'],
                    'weighted_average_ftp_ratio' => $metrics['weighted_average_ftp_ratio'],
                ],
            ];
        }

        usort($matches, static fn (array $left, array $right): int => ($right['score'] <=> $left['score']) ?: strcmp($left['summary']['id'], $right['summary']['id']));
        $maxResults = max(1, min(50, $maxResults));

        return ['trainings' => array_column(array_slice($matches, 0, $maxResults), 'summary')];
    }

    /** @param array<string, mixed> $training */
    private function searchableText(array $training): string
    {
        $parts = [
            (string) ($training['id'] ?? ''),
            (string) ($training['training_name'] ?? ''),
            (string) ($training['description'] ?? ''),
            (string) ($training['source'] ?? ''),
        ];
        foreach (($training['phases'] ?? []) as $phase) {
            if (is_array($phase) && is_string($phase['notes'] ?? null)) {
                $parts[] = $phase['notes'];
            }
        }
        return implode(' ', $parts);
    }

    private static function normalize(string $value): string
    {
        $value = strtolower($value);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return is_string($transliterated) ? strtolower($transliterated) : $value;
    }
}
