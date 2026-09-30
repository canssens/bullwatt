<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Schema\ToolAnnotations;

final class ServerFactory
{
    public static function create(Application $application, bool $persistentSessions = false): Server
    {
        $capabilities = $application->capabilities;
        $builder = Server::builder()
            ->setServerInfo(
                'Bullwatt MCP Server',
                '1.0.0',
                'AI integration for Bullwatt, a browser-based indoor cycling app for structured FTP-based workouts on Bluetooth FTMS trainers. Provides workout documentation and examples, search, deterministic JSON validation, and safe storage of generated sessions for launch in Bullwatt.',
            )
            ->setInstructions('Read the Indoor bike training Bullwatt format and generation guidelines before generating JSON. Always validate before saving; save in order to propose to the user a link to practice.')
            ->addResource(
                static fn (): string => $capabilities->trainingFormat(),
                'bullwatt://training-format',
                'training-format',
                'Bullwatt training format',
                'Complete format documentation and canonical JSON Schema.',
                'text/markdown',
            )
            ->addResource(
                static fn (): array => $capabilities->trainings(),
                'bullwatt://trainings',
                'trainings',
                'Bullwatt training catalog',
                'Summaries and calculated metrics for all available sessions.',
                'application/json',
            )
            ->addResource(
                static fn (): string => $capabilities->generationGuidelines(),
                'bullwatt://generation-guidelines',
                'generation-guidelines',
                'Bullwatt generation guidelines',
                'Rules an AI assistant must follow when generating a session.',
                'text/markdown',
            )
            ->addResourceTemplate(
                static fn (string $id): array => $capabilities->training($id),
                'bullwatt://trainings/{id}',
                'training',
                'Complete Bullwatt training',
                'Returns one complete training by id.',
                'application/json',
            )
            ->addTool(
                static fn (
                    ?string $query = null,
                    ?int $duration_min = null,
                    ?int $duration_max = null,
                    ?float $min_intensity = null,
                    ?float $max_intensity = null,
                    ?int $phase_count = null,
                    int $max_results = 10,
                ): array => $capabilities->searchTrainings($query, $duration_min, $duration_max, $min_intensity, $max_intensity, $phase_count, $max_results),
                'search_trainings',
                'Search Bullwatt trainings',
                'Lexical search and deterministic filters over names, descriptions, notes, duration, intensity, and phase count.',
                annotations: new ToolAnnotations(
                    readOnlyHint: true,
                    openWorldHint: false,
                    destructiveHint: false,
                ),
                inputSchema: self::searchSchema(),
                outputSchema: self::searchOutputSchema(),
            )
            ->addTool(
                static fn (array $training): array => $capabilities->validateTraining($training),
                'validate_training',
                'Validate a Bullwatt training',
                'Deterministically validates a generated session and returns blocking errors, warnings, and metrics.',
                annotations: new ToolAnnotations(
                    readOnlyHint: true,
                    openWorldHint: false,
                    destructiveHint: false,
                ),
                inputSchema: self::trainingToolSchema(false),
                outputSchema: self::validationOutputSchema(),
            )
            ->addTool(
                static fn (array $training): array => $capabilities->saveTraining($training),
                'save_training',
                'Save a Bullwatt training',
                'Revalidates and atomically saves a valid session in order to get an URL to launch the session.',
                annotations: new ToolAnnotations(
                    readOnlyHint: false,
                    destructiveHint: false,
                    idempotentHint: false,
                    openWorldHint: true,
                ),
                inputSchema: self::trainingToolSchema(true),
                outputSchema: self::saveOutputSchema(),
            )
            ->addPrompt(
                static fn (?string $request = null): array => $capabilities->generatePrompt($request),
                'generate_bullwatt_training',
                'Generate a Bullwatt training',
                'Guides the client model through context retrieval, generation, validation, presentation, saving and finally practice an indor bike session.',
            );

        if ($persistentSessions) {
            $builder->setSession(new FileSessionStore(__DIR__ . '/../var/sessions'));
        }

        return $builder->build();
    }

    /** @return array<string, mixed> */
    private static function searchSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Words that must occur in the name, id, description, source, or notes.'],
                'duration_min' => ['type' => 'integer', 'minimum' => 0],
                'duration_max' => ['type' => 'integer', 'minimum' => 0],
                'min_intensity' => ['type' => 'number', 'minimum' => 0, 'maximum' => 10],
                'max_intensity' => ['type' => 'number', 'minimum' => 0, 'maximum' => 10],
                'phase_count' => ['type' => 'integer', 'minimum' => 1],
                'max_results' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function trainingToolSchema(bool $save): array
    {
        $schemaContent = file_get_contents(__DIR__ . '/../schema/training.schema.json');
        if ($schemaContent === false) {
            throw new \RuntimeException('Training schema is unavailable.');
        }
        $trainingSchema = json_decode($schemaContent, true, 512, JSON_THROW_ON_ERROR);
        unset($trainingSchema['$schema'], $trainingSchema['$id']);
        self::rewriteReferences($trainingSchema);

        $properties = ['training' => ['$ref' => '#/$defs/training']];
        if ($save) {
            $properties['overwrite'] = ['type' => 'boolean', 'default' => false];
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['training'],
            'properties' => $properties,
            '$defs' => ['training' => $trainingSchema],
        ];
    }

    /** @return array<string, mixed> */
    private static function searchOutputSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['trainings'],
            'properties' => [
                'trainings' => [
                    'type' => 'array',
                    'description' => 'Matching trainings, ordered by lexical relevance and then by id.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'id',
                            'training_name',
                            'duration',
                            'description',
                            'phase_count',
                            'minimum_ftp_ratio',
                            'maximum_ftp_ratio',
                            'weighted_average_ftp_ratio',
                        ],
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'training_name' => ['type' => 'string'],
                            'duration' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Total duration in seconds.'],
                            'description' => ['type' => 'string'],
                            'phase_count' => ['type' => 'integer', 'minimum' => 0],
                            'minimum_ftp_ratio' => self::nullableRatioSchema(),
                            'maximum_ftp_ratio' => self::nullableRatioSchema(),
                            'weighted_average_ftp_ratio' => self::nullableRatioSchema(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function validationOutputSchema(): array
    {
        $schema = self::validationSchema();
        $schema['$defs'] = self::validationDefinitions();

        return $schema;
    }

    /** @return array<string, mixed> */
    private static function saveOutputSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'A successful save result or a structured failure result.',
            'oneOf' => [
                [
                    'additionalProperties' => false,
                    'required' => ['saved', 'id', 'path', 'url', 'validation'],
                    'properties' => [
                        'saved' => ['const' => true],
                        'id' => ['type' => 'string', 'pattern' => '^generated-[a-f0-9]{32}$'],
                        'path' => ['type' => 'string', 'description' => 'Logical path of the generated training JSON file.'],
                        'url' => ['type' => 'string', 'format' => 'uri', 'description' => 'URL used to launch the saved training in Bullwatt.'],
                        'validation' => ['$ref' => '#/$defs/validation'],
                    ],
                ],
                [
                    'additionalProperties' => false,
                    'required' => ['saved', 'error'],
                    'properties' => [
                        'saved' => ['const' => false],
                        'error' => ['$ref' => '#/$defs/saveError'],
                        'validation' => ['$ref' => '#/$defs/validation'],
                    ],
                ],
            ],
            '$defs' => array_merge(self::validationDefinitions(), [
                'validation' => self::validationSchema(),
                'saveError' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['code', 'message'],
                    'properties' => [
                        'code' => ['type' => 'string'],
                        'message' => ['type' => 'string'],
                    ],
                ],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private static function validationSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['valid', 'errors', 'warnings', 'metrics'],
            'properties' => [
                'valid' => ['type' => 'boolean', 'description' => 'Whether the training has no blocking validation errors.'],
                'errors' => [
                    'type' => 'array',
                    'description' => 'Blocking validation issues that must be corrected.',
                    'items' => ['$ref' => '#/$defs/validationError'],
                ],
                'warnings' => [
                    'type' => 'array',
                    'description' => 'Non-blocking advice about the training.',
                    'items' => ['$ref' => '#/$defs/validationWarning'],
                ],
                'metrics' => ['$ref' => '#/$defs/metrics'],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private static function validationDefinitions(): array
    {
        return [
            'validationError' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['path', 'code', 'message'],
                'properties' => [
                    'path' => ['type' => 'string', 'description' => 'Path to the invalid field; empty for a root-level issue.'],
                    'code' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                ],
            ],
            'validationWarning' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['code', 'message'],
                'properties' => [
                    'code' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                ],
            ],
            'metrics' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'duration',
                    'phase_count',
                    'minimum_ftp_ratio',
                    'maximum_ftp_ratio',
                    'weighted_average_ftp_ratio',
                ],
                'properties' => [
                    'duration' => ['type' => 'number', 'minimum' => 0, 'description' => 'Total duration in seconds.'],
                    'phase_count' => ['type' => 'integer', 'minimum' => 0],
                    'minimum_ftp_ratio' => self::nullableRatioSchema(),
                    'maximum_ftp_ratio' => self::nullableRatioSchema(),
                    'weighted_average_ftp_ratio' => self::nullableRatioSchema(),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function nullableRatioSchema(): array
    {
        return [
            'type' => ['number', 'null'],
            'minimum' => 0,
            'description' => 'FTP ratio, where 1.0 means 100% FTP; null when it cannot be calculated.',
        ];
    }

    /** @param array<string, mixed> $node */
    private static function rewriteReferences(array &$node): void
    {
        foreach ($node as $key => &$value) {
            if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/')) {
                $value = '#/$defs/training/' . substr($value, 2);
            } elseif (is_array($value)) {
                self::rewriteReferences($value);
            }
        }
    }
}
