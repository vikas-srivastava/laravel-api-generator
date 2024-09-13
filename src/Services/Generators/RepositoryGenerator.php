<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class RepositoryGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $repositoryName = Str::studly($className) . 'Repository';
        $namespace = "App\\Repositories\\$moduleName";
        $path = app_path("Repositories/$moduleName/$repositoryName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use App\Interfaces\\$moduleName\\{$className}RepositoryInterface;
use App\Models\\$moduleName\\$className;

class $repositoryName implements {$className}RepositoryInterface
{
    {$this->generateFeatureMethods($classConfig['features'],$className)}
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Repository for $moduleName -  $className added to $path", $command);
    }

    private function generateFeatureMethods(array $features, string $className): string
    {
        $methods = '';
        foreach ($features as $feature) {
            $methods .= $this->getFeatureMethod($feature, $className);
        }
        return $methods;
    }

    private function getFeatureMethod(string $feature, string $className): string
    {
        switch ($feature) {
            case 'index':
                return <<<EOT
    public function getAll()
    {
        return $className::all();
    }

EOT;
            case 'show':
                return <<<EOT
    public function getById(\$id)
    {
        return $className::findOrFail(\$id);
    }

EOT;
            case 'store':
                return <<<EOT
    public function create(array \$data)
    {
        return $className::create(\$data);
    }

EOT;
            case 'update':
                return <<<EOT
    public function update(\$id, array \$data)
    {
        \$item = $className::findOrFail(\$id);
        \$item->update(\$data);
        return \$item;
    }

EOT;
            case 'delete':
                return <<<EOT
    public function delete(\$id)
    {
        return $className::destroy(\$id);
    }

EOT;
            default:
                return '';
        }
    }
}