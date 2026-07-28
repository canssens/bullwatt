<?php

declare(strict_types=1);

namespace Bullwatt\Mcp;

final class Application
{
    public readonly CatalogRepository $repository;
    public readonly TrainingSearch $search;
    public readonly TrainingValidator $validator;
    public readonly TrainingStorage $storage;
    public readonly Capabilities $capabilities;

    public function __construct(?string $projectRoot = null, ?string $generatedDirectory = null)
    {
        $root = $projectRoot ?? dirname(__DIR__, 2);
        $generated = $generatedDirectory ?? $root . '/trainings/generated';
        $this->repository = new CatalogRepository($root . '/trainings/catalog', $generated);
        $this->validator = new TrainingValidator();
        $this->search = new TrainingSearch($this->repository);
        $this->storage = new TrainingStorage($generated, $this->repository, $this->validator);
        $this->capabilities = new Capabilities(
            $this->repository,
            $this->search,
            $this->validator,
            $this->storage,
            $root . '/trainings/trainings_documentation.md',
            __DIR__ . '/../schema/training.schema.json',
        );
    }
}
