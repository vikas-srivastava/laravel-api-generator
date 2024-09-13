<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

abstract class Generator
{
    protected function createDirectory(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function writeToPHP(string $path, string $content): void
    {
        if (File::exists($path)) {
            File::delete($path);
        }
        File::put($path, '<?php' . PHP_EOL . PHP_EOL . $content);
    }

    protected function logInfo(string $message, $command = null): void
    {
        // If a command instance is passed, output to console
        if ($command) {
            $command->info($message);
        }

        // Log the message regardless
        Log::info($message);
    }

    protected function logError(string $message, $command = null): void
    {
        // If a command instance is passed, output to console
        if ($command) {
            $command->error($message);
        }

        // Log the message regardless
        Log::error($message);
    }

    abstract public function generate(string $moduleName, string $className, array $classConfig, $command = null): void;
}