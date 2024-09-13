<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $factoryName = Str::studly($className) . 'Factory';
        $namespace = "Database\\Factories\\$moduleName";
        $path = database_path("factories/$moduleName/$factoryName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use App\\Models\\$moduleName\\$className;
use Illuminate\Database\Eloquent\Factories\Factory;

class $factoryName extends Factory
{
    protected \$model = $className::class;

    public function definition()
    {
        return [
            // Add factory attributes here
        ];
    }
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Factory for $moduleName -  $className added to $path", $command);
    }
}