<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class ModelGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $modelName = Str::studly($className);
        $namespace = "App\\Models\\$moduleName";
        $path = app_path("Models/$moduleName/$modelName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class $modelName extends Model
{
    use HasFactory;

    protected \$fillable = [
        // Add fillable attributes here
    ];
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Model for $moduleName -  $className added to $path", $command);
    }
}