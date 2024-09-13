<?php

namespace App\Services;

use App\Services\Generators\{
    ModelGenerator,
    ControllerGenerator,
    RepositoryGenerator,
    InterfaceGenerator,
    ResourceGenerator,
    RequestGenerator,
    FactoryGenerator,
    TestGenerator,
    RouteGenerator,
    MigrationGenerator
};

class APIBuilder
{
    private $generators;

    public function __construct(
        ModelGenerator $modelGenerator,
        ControllerGenerator $controllerGenerator,
        RepositoryGenerator $repositoryGenerator,
        InterfaceGenerator $interfaceGenerator,
        ResourceGenerator $resourceGenerator,
        RequestGenerator $requestGenerator,
        FactoryGenerator $factoryGenerator,
        TestGenerator $testGenerator,
        RouteGenerator $routeGenerator,
        MigrationGenerator $migrationGenerator
    ) {
        $this->generators = [
            $modelGenerator,
            $controllerGenerator,
            $repositoryGenerator,
            $interfaceGenerator,
            $resourceGenerator,
            $requestGenerator,
            $factoryGenerator,
            $testGenerator,
            $routeGenerator,
            $migrationGenerator // Include MigrationGenerator
        ];
    }

    public function generate(string $moduleName, array $moduleConfig, $command): void
    {
        foreach ($moduleConfig['classes'] as $className => $classConfig) {
            foreach ($this->generators as $generator) {
                $generator->generate($moduleName, $className, $classConfig, $command);
            }
        }
    }
}