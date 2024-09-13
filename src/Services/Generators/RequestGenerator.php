<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class RequestGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $paths = '';
        $paths .= ' / ' . $this->generateStoreRequest($moduleName, $className);
        $paths .= ' / ' . $this->generateUpdateRequest($moduleName, $className);

        $this->logInfo("Requests for $moduleName -  $className added to $paths", $command);
    }

    private function generateStoreRequest(string $moduleName, string $className): string
    {
        $requestName = 'Store' . Str::studly($className) . 'Request';
        return $this->generateRequest($moduleName, $className, $requestName);
    }

    private function generateUpdateRequest(string $moduleName, string $className): string
    {
        $requestName = 'Update' . Str::studly($className) . 'Request';
        return $this->generateRequest($moduleName, $className, $requestName);
    }

    private function generateRequest(string $moduleName, string $className, string $requestName): string
    {
        $namespace = "App\\Http\\Requests\\$moduleName";
        $path = app_path("Http/Requests/$moduleName/$requestName.php");

        $this->createDirectory(dirname($path));

        $content = <<<EOT
namespace $namespace;

use Illuminate\Foundation\Http\FormRequest;

class $requestName extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Add validation rules here
        ];
    }
}
EOT;

        $this->writeToPHP($path, $content);

        return $path;
    }
}