<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class TrainingValidator
{
    private const REQUIRED_FIELDS = ['id', 'training_name', 'description', 'duration', 'units', 'phases'];
    private const ALLOWED_FIELDS = ['id', 'training_name', 'description', 'duration', 'units', 'phases', 'creation_date', 'source'];
    private const ALLOWED_PHASE_FIELDS = ['start', 'ftp_ratio', 'notes'];

    /** @return array{valid: bool, errors: list<array<string, string>>, warnings: list<array<string, string>>, metrics: array<string, mixed>} */
    public function validate(mixed $training): array
    {
        $errors = [];
        $warnings = [];

        if (!is_array($training) || array_is_list($training)) {
            $this->error($errors, '', 'TRAINING_TYPE_INVALID', 'The training must be a JSON object.');
            return $this->result([], $errors, $warnings);
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $training)) {
                $this->error($errors, $field, 'REQUIRED_FIELD_MISSING', "Required field '{$field}' is missing.");
            }
        }

        foreach (array_keys($training) as $field) {
            if (!in_array($field, self::ALLOWED_FIELDS, true)) {
                $this->error($errors, $field, 'FIELD_NOT_ALLOWED', "Field '{$field}' is not part of the Bullwatt format.");
            }
        }

        $this->validateText($training, 'id', $errors);
        if (isset($training['id']) && is_string($training['id']) && preg_match('/^[a-z0-9][a-z0-9_-]{0,99}$/D', $training['id']) !== 1) {
            $this->error($errors, 'id', 'ID_INVALID', 'The id must be a lowercase slug containing only letters, digits, underscores, or hyphens.');
        }
        $this->validateText($training, 'training_name', $errors);
        $this->validateText($training, 'description', $errors);

        $duration = $training['duration'] ?? null;
        if (!is_int($duration)) {
            $this->error($errors, 'duration', 'DURATION_TYPE_INVALID', 'Duration must be an integer number of seconds.');
        } elseif ($duration <= 0 || $duration > 86400) {
            $this->error($errors, 'duration', 'DURATION_INVALID', 'Duration must be between 1 and 86400 seconds.');
        }

        $this->validateUnits($training['units'] ?? null, $errors);
        $this->validateOptionalFields($training, $errors);
        $this->validatePhases($training['phases'] ?? null, is_int($duration) ? $duration : null, $errors, $warnings);

        return $this->result($training, $errors, $warnings);
    }

    /** @param list<array<string, string>> $errors */
    private function validateText(array $training, string $field, array &$errors): void
    {
        if (array_key_exists($field, $training) && (!is_string($training[$field]) || trim($training[$field]) === '')) {
            $this->error($errors, $field, strtoupper($field) . '_INVALID', "Field '{$field}' must be a non-empty string.");
        }
    }

    /** @param list<array<string, string>> $errors */
    private function validateUnits(mixed $units, array &$errors): void
    {
        if (!is_array($units) || array_is_list($units)) {
            $this->error($errors, 'units', 'UNITS_TYPE_INVALID', 'Units must be an object.');
            return;
        }

        foreach (array_keys($units) as $field) {
            if (!in_array($field, ['time', 'ftp_ratio'], true)) {
                $this->error($errors, 'units.' . $field, 'FIELD_NOT_ALLOWED', "Unit field '{$field}' is not allowed.");
            }
        }

        if (($units['time'] ?? null) !== 'seconds') {
            $this->error($errors, 'units.time', 'TIME_UNIT_UNKNOWN', "The only supported time unit is 'seconds'.");
        }
        if (!in_array($units['ftp_ratio'] ?? null, ['number', 'ftp_ratio'], true)) {
            $this->error($errors, 'units.ftp_ratio', 'VALUE_UNIT_UNKNOWN', "The FTP ratio unit must be 'number' or 'ftp_ratio'.");
        }
    }

    /** @param list<array<string, string>> $errors */
    private function validateOptionalFields(array $training, array &$errors): void
    {
        if (isset($training['creation_date'])) {
            if (!is_string($training['creation_date']) || strtotime($training['creation_date']) === false) {
                $this->error($errors, 'creation_date', 'CREATION_DATE_INVALID', 'creation_date must be an ISO 8601 date-time string.');
            }
        }
        if (isset($training['source']) && !is_string($training['source'])) {
            $this->error($errors, 'source', 'SOURCE_TYPE_INVALID', 'source must be a string.');
        }
    }

    /** @param list<array<string, string>> $errors @param list<array<string, string>> $warnings */
    private function validatePhases(mixed $phases, ?int $duration, array &$errors, array &$warnings): void
    {
        if (!is_array($phases) || !array_is_list($phases)) {
            $this->error($errors, 'phases', 'PHASES_TYPE_INVALID', 'Phases must be a JSON array.');
            return;
        }
        if ($phases === []) {
            $this->error($errors, 'phases', 'PHASES_EMPTY', 'At least one phase is required.');
            return;
        }

        $previousStart = null;
        foreach ($phases as $index => $phase) {
            $path = "phases[{$index}]";
            if (!is_array($phase) || array_is_list($phase)) {
                $this->error($errors, $path, 'PHASE_TYPE_INVALID', 'Each phase must be an object.');
                continue;
            }
            foreach (array_keys($phase) as $field) {
                if (!in_array($field, self::ALLOWED_PHASE_FIELDS, true)) {
                    $this->error($errors, $path . '.' . $field, 'FIELD_NOT_ALLOWED', "Phase field '{$field}' is not allowed.");
                }
            }
            if (!array_key_exists('start', $phase)) {
                $this->error($errors, $path . '.start', 'REQUIRED_FIELD_MISSING', 'Phase start is required.');
            }
            if (!array_key_exists('ftp_ratio', $phase)) {
                $this->error($errors, $path . '.ftp_ratio', 'REQUIRED_FIELD_MISSING', 'Phase ftp_ratio is required.');
            }

            $start = $phase['start'] ?? null;
            if (!is_int($start)) {
                $this->error($errors, $path . '.start', 'PHASE_START_TYPE_INVALID', 'Phase start must be an integer number of seconds.');
            } else {
                if ($start < 0) {
                    $this->error($errors, $path . '.start', 'NEGATIVE_PHASE_START', 'Phase start cannot be negative.');
                }
                if ($index === 0 && $start !== 0) {
                    $this->error($errors, $path . '.start', 'FIRST_PHASE_NOT_ZERO', 'The first phase must start at zero.');
                }
                if ($previousStart !== null && $start <= $previousStart) {
                    $this->error($errors, $path . '.start', 'PHASE_ORDER_INVALID', 'Phase starts must be strictly increasing.');
                }
                if ($duration !== null && ($start > $duration || ($start === $duration && $index !== array_key_last($phases)))) {
                    $this->error($errors, $path . '.start', 'PHASE_OUTSIDE_DURATION', 'Only the optional final end marker may start at the total duration.');
                }
                $previousStart = $start;
            }

            $ftp_ratio = $phase['ftp_ratio'] ?? null;
            if (!is_int($ftp_ratio) && !is_float($ftp_ratio)) {
                $this->error($errors, $path . '.ftp_ratio', 'INTENSITY_TYPE_INVALID', 'Phase intensity must be a JSON number.');
            } elseif ($ftp_ratio < 0 || $ftp_ratio > 10) {
                $this->error($errors, $path . '.ftp_ratio', 'INTENSITY_OUT_OF_RANGE', 'Phase intensity must be between 0 and 10 times FTP.');
            }
            if (array_key_exists('notes', $phase) && !is_string($phase['notes']) && $phase['notes'] !== null) {
                $this->error($errors, $path . '.notes', 'NOTES_TYPE_INVALID', 'Phase notes must be a string or null.');
            }
        }

        $lastPhase = $phases[array_key_last($phases)];
        if (is_array($lastPhase) && isset($lastPhase['ftp_ratio']) && is_numeric($lastPhase['ftp_ratio']) && (float) $lastPhase['ftp_ratio'] > 0.65) {
            $warnings[] = [
                'code' => 'NO_COOLDOWN_DETECTED',
                'message' => 'No obvious low-intensity cool-down was detected at the end of the session.',
            ];
        }
    }

    /** @param list<array<string, string>> $errors @param list<array<string, string>> $warnings */
    private function result(array $training, array $errors, array $warnings): array
    {
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'metrics' => TrainingMetrics::calculate($training),
        ];
    }

    /** @param list<array<string, string>> $errors */
    private function error(array &$errors, string $path, string $code, string $message): void
    {
        $errors[] = ['path' => $path, 'code' => $code, 'message' => $message];
    }
}
