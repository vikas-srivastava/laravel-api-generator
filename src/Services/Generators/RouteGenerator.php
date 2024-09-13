<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class RouteGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $routeFile = base_path("routes/api.php");
        $controllerName = Str::studly($className) . 'Controller';
        $routeName = Str::kebab(Str::plural($className));

        $routeContent = "\nuse App\\Http\\Controllers\\$moduleName\\{$controllerName};\n";
        $routeContent .= "Route::apiResource('$routeName', {$controllerName}::class);\n";

        // Append the new routes to the existing api.php file
        file_put_contents($routeFile, $routeContent, FILE_APPEND);

        $this->logInfo("Routes for $moduleName -  $className added to api.php", $command);
    }
}