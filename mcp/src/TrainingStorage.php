<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class TrainingStorage
{
    private const MAX_JSON_SIZE_BYTES = 50 * 1024;

    public function __construct(
        private readonly string $generatedDirectory,
        private readonly CatalogRepository $repository,
        private readonly TrainingValidator $validator,
    ) {
    }

    /** @param array<string, mixed> $training @return array<string, mixed> */
    public function save(array $training): array
    {
        $validation = $this->validator->validate($training);
        if (!$validation['valid']) {
            return [
                'saved' => false,
                'error' => [
                    'code' => 'TRAINING_INVALID',
                    'message' => 'The training was not saved because validation failed.',
                ],
                'validation' => $validation,
            ];
        }

        /*
        // As a new ID is created no need to check if the id is uniq
        if ($overwrite) {
            return $this->failure('OVERWRITE_NOT_SUPPORTED', 'Bullwatt generated storage never overwrites an existing file.');
        }

        $requestedId = (string) $training['id'];
        if ($this->repository->exists($requestedId)) {
            return $this->failure('TRAINING_ID_EXISTS', "Training id '{$requestedId}' already exists and cannot be overwritten.");
        }
        */

        if (!is_dir($this->generatedDirectory) && !mkdir($this->generatedDirectory, 0775, true) && !is_dir($this->generatedDirectory)) {
            return $this->failure('STORAGE_UNAVAILABLE', 'The generated training directory could not be created.');
        }

        do {
            $id = 'generated-' . bin2hex(random_bytes(16));
            $filename = $id . '.json';
            $path = $this->generatedDirectory . DIRECTORY_SEPARATOR . $filename;
        } while (file_exists($path));

        $storedTraining = $training;
        $storedTraining['id'] = $id;
        $storedTraining['creation_date'] = gmdate(DATE_ATOM);
        $storedTraining['source'] = isset($training['source']) && trim((string) $training['source']) !== ''
            ? $training['source']
            : 'bullwatt-ai';

        try {
            $json = json_encode($storedTraining, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
        } catch (\JsonException $exception) {
            return $this->failure('JSON_ENCODING_FAILED', $exception->getMessage());
        }

        if (strlen($json) > self::MAX_JSON_SIZE_BYTES) {
            return $this->failure('TRAINING_JSON_TOO_LARGE', 'The training JSON exceeds the maximum size of 50 KiB.');
        }

        $temporary = tempnam($this->generatedDirectory, '.bullwatt-');
        if ($temporary === false || file_put_contents($temporary, $json, LOCK_EX) === false) {
            if (is_string($temporary) && file_exists($temporary)) {
                @unlink($temporary);
            }
            return $this->failure('WRITE_FAILED', 'The temporary training file could not be written.');
        }

        if (!chmod($temporary, 0644)) {
            @unlink($temporary);
            return $this->failure('PERMISSION_UPDATE_FAILED', 'The training file could not be made readable.');
        }

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            return $this->failure('ATOMIC_MOVE_FAILED', 'The training could not be moved atomically into generated storage.');
        }

        return [
            'saved' => true,
            'id' => $id,
            'path' => 'trainings/generated/' . $filename,
            'url' => 'https://www.bullwatt.com/star-bike.html?generated='. $filename,
            'validation' => $this->validator->validate($storedTraining),
        ];
    }

    /** @return array{saved: false, error: array{code: string, message: string}} */
    private function failure(string $code, string $message): array
    {
        return ['saved' => false, 'error' => ['code' => $code, 'message' => $message]];
    }
}
