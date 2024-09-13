<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class ResourceGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $resourceName = Str::studly($className) . 'Resource';
        $namespace = "App\\Http\\Resources\\$moduleName";
        $path = app_path("Http/Resources/$moduleName/$resourceName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use Illuminate\Http\Resources\Json\JsonResource;

class $resourceName extends JsonResource
{
    public function toArray(\$request)
    {
        return [
            'id' => \$this->id,
            // Add other attributes here
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Resources for $moduleName -  $className added to $path", $command);
    }
}