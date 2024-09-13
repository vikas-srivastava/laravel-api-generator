<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class InterfaceGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $interfaceName = Str::studly($className) . 'RepositoryInterface';
        $namespace = "App\\Interfaces\\$moduleName";
        $path = app_path("Interfaces/$moduleName/$interfaceName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

interface $interfaceName
{
    {$this->generateFeatureMethods($classConfig['features'])}
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Interface for $moduleName -  $className added to $path", $command);
    }

    private function generateFeatureMethods(array $features): string
    {
        $methods = '';
        foreach ($features as $feature) {
            $methods .= $this->getFeatureMethod($feature);
        }
        return $methods;
    }

    private function getFeatureMethod(string $feature): string
    {
        switch ($feature) {
            case 'index':
                return "public function getAll();\n\n";
            case 'show':
                return "public function getById(\$id);\n\n";
            case 'store':
                return "public function create(array \$data);\n\n";
            case 'update':
                return "public function update(\$id, array \$data);\n\n";
            case 'delete':
                return "public function delete(\$id);\n\n";
            default:
                return '';
        }
    }
}