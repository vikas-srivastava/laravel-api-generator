<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteCrudForTable extends Command
{
    protected $signature = 'delete:crud {table}';
    protected $description = 'Delete the model, controller, resource, requests, repository, interfaces, test, related routes, and factory for a given table.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $table = $this->argument('table');
        $model = $this->getModelName($table);
        $controller = "{$model}Controller";
        $resource = "{$model}Resource";
        $test = "{$controller}Test";
        $factory = "{$model}Factory";
        $repository = "{$model}Repository";
        $interface = "{$model}RepositoryInterface";
        $storeRequest = "Store{$model}Request";
        $updateRequest = "Update{$model}Request";
        $routeName = Str::snake(Str::pluralStudly($model));

        // Paths to delete
        $paths = [
            app_path("Models/{$model}.php"),
            app_path("Http/Controllers/{$controller}.php"),
            app_path("Http/Resources/{$resource}.php"),
            app_path("Http/Requests/{$storeRequest}.php"),
            app_path("Http/Requests/{$updateRequest}.php"),
            app_path("Repositories/{$repository}.php"),
            app_path("Interfaces/{$interface}.php"),
            base_path("tests/Feature/{$test}.php"),
            base_path("database/factories/{$factory}.php"),
            base_path("database/factories/{$model}Factory.php"), // Ensure deletion of factory class
        ];

        // Iterate over the paths and delete the files if they exist
        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
                $this->info("Deleted: $path");
            } else {
                $this->warn("File not found: $path");
            }
        }

        // Optionally, delete the resource collection class if it was generated
        $resourceCollection = "{$model}ResourceCollection";
        $resourceCollectionPath = app_path("Http/Resources/{$resourceCollection}.php");
        if (File::exists($resourceCollectionPath)) {
            File::delete($resourceCollectionPath);
            $this->info("Deleted: $resourceCollectionPath");
        }

        // Remove routes from api.php
        $this->removeRoutesFromApi($routeName);

        $this->info('CRUD deletion process completed.');
        return 0;
    }

    protected function getModelName($table)
    {
        // Handle special cases where singularization is not correct
        $specialCases = [
            'media' => 'Media',
            // Add more special cases if needed
        ];

        return $specialCases[$table] ?? Str::studly(Str::singular($table));
    }

    protected function removeRoutesFromApi($routeName)
    {
        $routePath = base_path('routes/api.php');

        if (File::exists($routePath)) {
            $routesContent = file_get_contents($routePath);

            // Regex pattern to match the routes defined for the given controller
            $pattern = "/Route::(get|post|put|delete)\('\/{$routeName}.*?;\n/";

            // Remove the matched routes
            $newRoutesContent = preg_replace($pattern, '', $routesContent);

            // Write the updated content back to api.php
            file_put_contents($routePath, $newRoutesContent);

            $this->info("Routes for {$routeName} removed from api.php.");
        } else {
            $this->warn("File not found: {$routePath}");
        }
    }
}