<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class Capabilities
{
    public function __construct(
        private readonly CatalogRepository $repository,
        private readonly TrainingSearch $search,
        private readonly TrainingValidator $validator,
        private readonly TrainingStorage $storage,
        private readonly string $documentationFile,
        private readonly string $schemaFile,
    ) {
    }

    public function trainingFormat(): string
    {
        $existingDocumentation = file_get_contents($this->documentationFile);
        $schema = file_get_contents($this->schemaFile);
        if ($existingDocumentation === false || $schema === false) {
            throw new \RuntimeException('Bullwatt format documentation is unavailable.');
        }

        return <<<MARKDOWN
# Bullwatt training format

This resource combines the existing Bullwatt documentation (the human source of truth) with the formal schema used by MCP validation and tests.

{$existingDocumentation}

## Chronology and technical constraints

- `duration`, phase `start`, and all time calculations use seconds.
- `ftp_ratio` is a decimal FTP ratio: `1.0` means 100% FTP and `0.5` means 50% FTP.
- The first phase starts at `0`; starts are strictly increasing and non-negative.
- A phase applies from its `start` until the next phase, or until `duration` for the last active phase.
- Existing Bullwatt sessions conventionally include a zero-length final marker at `start == duration`. It is supported only as the last phase. A generated session may omit it because the final active phase implicitly runs to `duration`.
- Active phases must start before `duration`; no phase may start after it.
- Canonical generated JSON uses integer time values and JSON numbers for FTP ratios. Historical catalog files may contain numeric strings because the existing browser code converts them with `Number()`.
- The `units.ftp_ratio` field accepts `number` and `ftp_ratio`. Historical `text` labels remain readable from catalog resources but are not valid for newly generated sessions.
- Supported generated durations are 1 to 86400 seconds and FTP ratios are 0 to 2. The upper bound is technical, not sporting advice; the existing FTP ramp test reaches 6.4.
- Unknown fields are rejected. Optional fields are `creation_date`, `source`, and phase `notes`.

## Formal JSON Schema and complete valid example

```json
{$schema}
```
MARKDOWN;
    }

    /** @return list<array<string, mixed>> */
    public function trainings(): array
    {
        return $this->repository->summaries();
    }

    /** @return array<string, mixed> */
    public function training(string $id): array
    {
        return $this->repository->find($id);
    }

    public function generationGuidelines(): string
    {
        return <<<'MARKDOWN'
# Bullwatt generation guidelines

1. Read `bullwatt://training-format` and produce a JSON object that exactly follows it.
2. Do not invent undocumented fields. Use integer seconds and numeric FTP ratios.
3. Start the first phase at zero and order all phase starts strictly increasingly.
4. Make the final active phase cover the requested total duration. An optional final marker may start exactly at the total duration.
5. Keep every active phase inside the duration and respect the requested duration exactly.
6. Express target power as FTP ratios (`1.0` is 100% FTP).
7. Read `bullwatt://trainings`, use `search_trainings`, and consult relevant complete sessions as examples.
8. Always call `validate_training` and correct every blocking error before presenting or saving the session.
9. Treat warnings as advice: they do not make a technically compatible training invalid.
10. Call `save_training` to save the training and allow the user to practice with the url provided.
MARKDOWN;
    }

    /** @return array{trainings: list<array<string, mixed>>} */
    public function searchTrainings(
        ?string $query = null,
        ?int $duration_min = null,
        ?int $duration_max = null,
        ?float $min_intensity = null,
        ?float $max_intensity = null,
        ?int $phase_count = null,
        int $max_results = 10,
    ): array {
        return $this->search->search(
            $query,
            $duration_min,
            $duration_max,
            $min_intensity,
            $max_intensity,
            $phase_count,
            $max_results,
        );
    }

    /** @param array<string, mixed> $training */
    public function validateTraining(array $training): array
    {
        return $this->validator->validate($training);
    }

    /** @param array<string, mixed> $training */
    public function saveTraining(array $training): array
    {
        return $this->storage->save($training);
    }

    /** @return list<array{role: string, content: string}> */
    public function generatePrompt(?string $request = null): array
    {
        $requestText = trim((string) $request);
        $suffix = $requestText === '' ? '' : "\n\nUser request:\n{$requestText}";

        return [[
            'role' => 'user',
            'content' => "You are creating a Bullwatt indoor cycling session.\n"
                . "1. Read bullwatt://training-format.\n"
                . "2. Read bullwatt://generation-guidelines.\n"
                . "3. Use search_trainings and read the most relevant bullwatt://trainings/{id} resources.\n"
                . "4. Produce the Bullwatt JSON directly; do not ask the server to generate it.\n"
                . "5. Call validate_training, correct all errors, and repeat until valid.\n"
                . "6. Present the JSON and a concise readable summary.\n"
                . "7. Call save_training to save the training and allow the user to practice with the url provided."
                . $suffix,
        ]];
    }
}
