<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class ControllerGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $controllerName = Str::studly($className) . 'Controller';
        $namespace = "App\\Http\\Controllers\\$moduleName";
        $path = app_path("Http/Controllers/$moduleName/$controllerName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use App\Http\Controllers\Controller;
use App\Interfaces\\$moduleName\\{$className}RepositoryInterface;
use App\Http\Resources\\$moduleName\\{$className}Resource;
use App\Http\Requests\\$moduleName\\Store{$className}Request;
use App\Http\Requests\\$moduleName\\Update{$className}Request;

class $controllerName extends Controller
{
    private \${$className}Repository;

    public function __construct({$className}RepositoryInterface \${$className}Repository)
    {
        \$this->{$className}Repository = \${$className}Repository;
    }

    {$this->generateFeatureMethods($classConfig['features'],$className)}
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Controller for $moduleName -  $className added to $path", $command);
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
        $lowercaseClassName = Str::camel($className);
        switch ($feature) {
            case 'index':
                return <<<EOT
    public function index()
    {
        \$items = \$this->{$lowercaseClassName}Repository->getAll();
        return {$className}Resource::collection(\$items);
    }

EOT;
            case 'show':
                return <<<EOT
    public function show(\$id)
    {
        \$item = \$this->{$lowercaseClassName}Repository->getById(\$id);
        return new {$className}Resource(\$item);
    }

EOT;
            case 'store':
                return <<<EOT
    public function store(Store{$className}Request \$request)
    {
        \$item = \$this->{$lowercaseClassName}Repository->create(\$request->validated());
        return new {$className}Resource(\$item);
    }

EOT;
            case 'update':
                return <<<EOT
    public function update(Update{$className}Request \$request, \$id)
    {
        \$item = \$this->{$lowercaseClassName}Repository->update(\$id, \$request->validated());
        return new {$className}Resource(\$item);
    }

EOT;
            case 'delete':
                return <<<EOT
    public function destroy(\$id)
    {
        \$this->{$lowercaseClassName}Repository->delete(\$id);
        return response()->json(null, 204);
    }

EOT;
            default:
                return '';
        }
    }
}