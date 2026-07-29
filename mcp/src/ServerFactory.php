<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;

final class ServerFactory
{
    public static function create(Application $application, bool $persistentSessions = false): Server
    {
        $capabilities = $application->capabilities;
        $builder = Server::builder()
            ->setServerInfo('Bullwatt MCP Server', '1.0.0', 'Bullwatt context, deterministic validation, search, and safe generated-session storage.')
            ->setInstructions('Read the Bullwatt format and generation guidelines before generating JSON. Always validate before saving; save only after explicit user approval.')
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
                inputSchema: self::searchSchema(),
            )
            ->addTool(
                static fn (array $training): array => $capabilities->validateTraining($training),
                'validate_training',
                'Validate a Bullwatt training',
                'Deterministically validates a generated session and returns blocking errors, warnings, and metrics.',
                inputSchema: self::trainingToolSchema(false),
            )
            ->addTool(
                static fn (array $training, bool $overwrite = false): array => $capabilities->saveTraining($training, $overwrite),
                'save_training',
                'Save a Bullwatt training',
                'Revalidates and atomically saves a valid session in order to get an URL to launch the session.',
                inputSchema: self::trainingToolSchema(true),
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
