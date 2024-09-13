<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        // Check if the class has fields to generate migration for
        if (!isset($classConfig['fields']) || empty($classConfig['fields'])) {
            return;
        }

        $tableName = Str::snake(Str::plural($className));
        $migrationName = "create_{$tableName}_table";

        // Generate the migration file using the Artisan command
        Artisan::call('make:migration', ['name' => $migrationName]);

        // Locate the most recent migration file created
        $migrationFiles = glob(database_path("/migrations/*_{$migrationName}.php"));
        $migrationFile = end($migrationFiles);

        // If migration file was found, populate it with fields
        if ($migrationFile) {
            $path = $this->populateMigration($migrationFile, $tableName, $classConfig['fields']);

            $this->logInfo("Migration for $moduleName - $className added to $path", $command);
        } else {
            $this->logError("Migration file not found for $moduleName - $className", $command);
        }
    }

    private function populateMigration(string $filePath, string $tableName, array $fields): string
    {
        $fieldDefinitions = array_map(function ($field) {
            // Basic field type definition; extend this to include different data types
            return "\$table->string('$field');";
        }, $fields);

        $fieldDefinitionsString = implode("\n            ", $fieldDefinitions);

        $migrationContent = <<<EOT
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();
            $fieldDefinitionsString
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('$tableName');
    }
};
EOT;

        // Write the updated content back to the file using writeToPHP
        $this->writeToPHP($filePath, $migrationContent);

        return $filePath;
    }
}