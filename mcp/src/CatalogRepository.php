<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

use Mcp\Exception\ResourceNotFoundException;

final class CatalogRepository
{
    public function __construct(
        private readonly string $catalogFile,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $trainings = $this->decodeCatalog();
        usort($trainings, static function (array $left, array $right): int {
            $durationComparison = ((int) ($left['duration'] ?? 0)) <=> ((int) ($right['duration'] ?? 0));
            return $durationComparison !== 0
                ? $durationComparison
                : strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? ''));
        });

        return $trainings;
    }

    /** @return list<array<string, mixed>> */
    public function summaries(): array
    {
        return array_map(static function (array $training): array {
            $metrics = TrainingMetrics::calculate($training);
            return [
                'id' => (string) ($training['id'] ?? ''),
                'training_name' => (string) ($training['training_name'] ?? ''),
                'duration' => (int) ($training['duration'] ?? 0),
                'description' => (string) ($training['description'] ?? ''),
                'phase_count' => $metrics['phase_count'],
                'minimum_ftp_ratio' => $metrics['minimum_ftp_ratio'],
                'maximum_ftp_ratio' => $metrics['maximum_ftp_ratio'],
                'weighted_average_ftp_ratio' => $metrics['weighted_average_ftp_ratio'],
            ];
        }, $this->all());
    }

    /** @return array<string, mixed> */
    public function find(string $id): array
    {
        foreach ($this->all() as $training) {
            if (($training['id'] ?? null) === $id) {
                return $training;
            }
        }

        throw new ResourceNotFoundException('bullwatt://trainings/' . rawurlencode($id));
    }

    public function exists(string $id): bool
    {
        foreach ($this->all() as $training) {
            if (($training['id'] ?? null) === $id) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string, mixed>> */
    private function decodeCatalog(): array
    {
        $content = file_get_contents($this->catalogFile);
        if ($content === false) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }
}
