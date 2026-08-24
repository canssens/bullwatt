<?php

declare(strict_types=1);

use Bullwatt\Mcp\Application;
use Bullwatt\Mcp\ServerFactory;
use Mcp\Exception\ResourceNotFoundException;

require dirname(__DIR__) . '/bootstrap.php';

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $test): void
    {
        try {
            $test();
            ++$this->passed;
            echo "PASS {$name}\n";
        } catch (Throwable $exception) {
            ++$this->failed;
            echo "FAIL {$name}: {$exception->getMessage()}\n";
        }
    }

    public function finish(): never
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        exit($this->failed === 0 ? 0 : 1);
    }
}

function assertTrue(bool $condition, string $message = 'Expected condition to be true.'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
    }
}

function errorCodes(array $validation): array
{
    return array_column($validation['errors'], 'code');
}

/** @return array<string, mixed> */
function validTraining(string $id = 'test-threshold'): array
{
    return [
        'id' => $id,
        'training_name' => 'Test threshold',
        'description' => 'Deterministic 100-second test session.',
        'duration' => 100,
        'units' => ['time' => 'seconds', 'ftp_ratio' => 'ftp_ratio'],
        'phases' => [
            ['start' => 0, 'ftp_ratio' => 0.5, 'notes' => 'Warm-up'],
            ['start' => 50, 'ftp_ratio' => 1.0, 'notes' => 'Work'],
            ['start' => 100, 'ftp_ratio' => 0.5, 'notes' => 'End marker'],
        ],
        'source' => 'mcp-tests',
    ];
}

$projectRoot = dirname(__DIR__, 2);
$temporaryDirectory = sys_get_temp_dir() . '/bullwatt-mcp-tests-' . bin2hex(random_bytes(6));
mkdir($temporaryDirectory, 0775, true);
$application = new Application($projectRoot, $temporaryDirectory);
$capabilities = $application->capabilities;
$runner = new TestRunner();

$runner->test('resource: format documentation', static function () use ($capabilities): void {
    $format = $capabilities->trainingFormat();
    assertTrue(str_contains($format, 'JSON Schema'));
    assertTrue(str_contains($format, 'ftp_ratio'));
});

$runner->test('resource: catalog list', static function () use ($capabilities): void {
    $catalog = $capabilities->trainings();
    assertTrue(count($catalog) >= 71);
    assertTrue(isset($catalog[0]['phase_count'], $catalog[0]['weighted_average_ftp_ratio']));
});

$runner->test('resource: existing complete training', static function () use ($capabilities): void {
    $training = $capabilities->training('alpha_30min');
    assertSameValue('Alpha 30min', $training['training_name']);
    assertSameValue(15, count($training['phases']));
});

$runner->test('resource: unknown id error', static function () use ($capabilities): void {
    try {
        $capabilities->training('does-not-exist');
    } catch (ResourceNotFoundException $exception) {
        assertTrue(str_contains($exception->getMessage(), 'does-not-exist'));
        return;
    }
    throw new RuntimeException('Expected ResourceNotFoundException.');
});

$runner->test('search: keyword', static function () use ($capabilities): void {
    $result = $capabilities->searchTrainings(query: 'threshold');
    assertTrue(count($result['trainings']) > 0);
    assertTrue(str_contains(strtolower(json_encode($result['trainings'], JSON_THROW_ON_ERROR)), 'threshold'));
});

$runner->test('search: duration filter', static function () use ($capabilities): void {
    $result = $capabilities->searchTrainings(duration_min: 1800, duration_max: 1800, max_results: 50);
    assertTrue(count($result['trainings']) > 0);
    foreach ($result['trainings'] as $training) {
        assertSameValue(1800, $training['duration']);
    }
});

$runner->test('search: intensity filter', static function () use ($capabilities): void {
    $result = $capabilities->searchTrainings(max_intensity: 0.8, max_results: 50);
    assertTrue(count($result['trainings']) > 0);
    foreach ($result['trainings'] as $training) {
        assertTrue($training['maximum_ftp_ratio'] <= 0.8);
    }
});

$runner->test('search: result limit', static function () use ($capabilities): void {
    assertSameValue(2, count($capabilities->searchTrainings(max_results: 2)['trainings']));
});

$runner->test('search: no result', static function () use ($capabilities): void {
    assertSameValue([], $capabilities->searchTrainings(query: 'no_such_training_phrase_8675309')['trainings']);
});

$runner->test('validation: valid training and metrics', static function () use ($capabilities): void {
    $result = $capabilities->validateTraining(validTraining());
    assertTrue($result['valid']);
    assertSameValue(3, $result['metrics']['phase_count']);
    assertSameValue(0.5, $result['metrics']['minimum_ftp_ratio']);
    assertSameValue(1, $result['metrics']['maximum_ftp_ratio']);
    assertSameValue(0.75, $result['metrics']['weighted_average_ftp_ratio']);
});

$validationCases = [
    'missing required field' => ['REQUIRED_FIELD_MISSING', static function (array $training): array { unset($training['training_name']); return $training; }],
    'invalid duration' => ['DURATION_INVALID', static function (array $training): array { $training['duration'] = 0; return $training; }],
    'first phase not zero' => ['FIRST_PHASE_NOT_ZERO', static function (array $training): array { $training['phases'][0]['start'] = 1; return $training; }],
    'unsorted phases' => ['PHASE_ORDER_INVALID', static function (array $training): array { $training['phases'][1]['start'] = 0; return $training; }],
    'invalid intensity' => ['INTENSITY_OUT_OF_RANGE', static function (array $training): array { $training['phases'][1]['ftp_ratio'] = 11.0; return $training; }],
    'phase after duration' => ['PHASE_OUTSIDE_DURATION', static function (array $training): array { $training['phases'][2]['start'] = 101; return $training; }],
    'empty phases' => ['PHASES_EMPTY', static function (array $training): array { $training['phases'] = []; return $training; }],
    'unknown unit' => ['VALUE_UNIT_UNKNOWN', static function (array $training): array { $training['units']['ftp_ratio'] = 'watts'; return $training; }],
    'legacy phase value field' => ['FIELD_NOT_ALLOWED', static function (array $training): array {
        $training['phases'][0]['value'] = $training['phases'][0]['ftp_ratio'];
        unset($training['phases'][0]['ftp_ratio']);
        return $training;
    }],
    'legacy unit value field' => ['FIELD_NOT_ALLOWED', static function (array $training): array {
        $training['units']['value'] = $training['units']['ftp_ratio'];
        unset($training['units']['ftp_ratio']);
        return $training;
    }],
];
foreach ($validationCases as $name => [$expectedCode, $mutator]) {
    $runner->test('validation: ' . $name, static function () use ($capabilities, $expectedCode, $mutator): void {
        $result = $capabilities->validateTraining($mutator(validTraining()));
        assertTrue(!$result['valid']);
        assertTrue(in_array($expectedCode, errorCodes($result), true), "Expected error code {$expectedCode}.");
    });
}

$runner->test('validation: unknown fields are rejected', static function () use ($capabilities): void {
    $training = validTraining();
    $training['made_up'] = true;
    assertTrue(in_array('FIELD_NOT_ALLOWED', errorCodes($capabilities->validateTraining($training)), true));
});

$runner->test('save: valid training uses random id and logical path', static function () use ($capabilities, $temporaryDirectory): void {
    $result = $capabilities->saveTraining(validTraining('new-session'));
    assertTrue($result['saved']);
    assertTrue(preg_match('/^generated-[a-f0-9]{32}$/D', $result['id']) === 1);
    assertSameValue('trainings/generated/' . $result['id'] . '.json', $result['path']);
    $storedPath = $temporaryDirectory . '/' . $result['id'] . '.json';
    assertTrue(is_file($storedPath));
    assertSameValue(0644, fileperms($storedPath) & 0777, 'Expected the stored training to be readable by the web server.');
});

$runner->test('save: invalid training refused', static function () use ($capabilities): void {
    $training = validTraining('invalid-save');
    $training['phases'] = [];
    $result = $capabilities->saveTraining($training);
    assertTrue(!$result['saved']);
    assertSameValue('TRAINING_INVALID', $result['error']['code']);
});

$runner->test('save: existing catalog id refused', static function () use ($capabilities): void {
    $result = $capabilities->saveTraining(validTraining('alpha_30min'));
    assertTrue(!$result['saved']);
    assertSameValue('TRAINING_ID_EXISTS', $result['error']['code']);
});

$runner->test('save: path traversal refused by validation', static function () use ($capabilities): void {
    $result = $capabilities->saveTraining(validTraining('../escape'));
    assertTrue(!$result['saved']);
    assertTrue(in_array('ID_INVALID', errorCodes($result['validation']), true));
});

$runner->test('save: overwrite mode refused', static function () use ($capabilities): void {
    $result = $capabilities->saveTraining(validTraining('overwrite-attempt'), true);
    assertTrue(!$result['saved']);
    assertSameValue('OVERWRITE_NOT_SUPPORTED', $result['error']['code']);
});

$runner->test('save: existing training remains unchanged', static function () use ($capabilities, $projectRoot): void {
    $path = $projectRoot . '/trainings/catalog/alpha_30min.json';
    $before = hash_file('sha256', $path);
    $capabilities->saveTraining(validTraining('preservation-check'));
    assertSameValue($before, hash_file('sha256', $path));
});

$runner->test('schema: valid JSON and server construction', static function () use ($application): void {
    $schema = json_decode(file_get_contents(dirname(__DIR__) . '/schema/training.schema.json'), true, 512, JSON_THROW_ON_ERROR);
    assertSameValue('Bullwatt training', $schema['title']);
    assertTrue(in_array('ftp_ratio', $schema['properties']['units']['required'], true));
    assertTrue(in_array('ftp_ratio', $schema['$defs']['phase']['required'], true));
    assertTrue(!isset($schema['properties']['units']['properties']['value']));
    assertTrue(!isset($schema['$defs']['phase']['properties']['value']));
    assertTrue(ServerFactory::create($application) instanceof Mcp\Server);
});

foreach (glob($temporaryDirectory . '/*') ?: [] as $file) {
    unlink($file);
}
rmdir($temporaryDirectory);
$runner->finish();
