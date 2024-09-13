<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateCrudForTable extends Command
{
    protected $signature = 'generate:crud {table}';
    protected $description = 'Generate CRUD operations for a given table, including models, repositories, controllers, resources, requests, tests, and routes.';

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
        $repository = "{$model}Repository";
        $interface = "{$model}RepositoryInterface";
        $storeRequest = "Store{$model}Request";
        $updateRequest = "Update{$model}Request";

        $this->generateApiResponseClass();

        // Generate Model
        $this->generateModel($table, $model);

        // Generate Repository and Interface
        $this->generateRepository($model, $repository, $interface);

        // Generate Controller
        $this->generateController($model, $controller, $repository, $storeRequest, $updateRequest);

        // Generate Resource
        $this->generateResource($table, $model, $resource);

        // Generate Requests
        $this->generateRequest($storeRequest, $table);
        $this->generateRequest($updateRequest, $table);

        // Generate Factory
        $this->generateFactory($table, $model);

        // Generate Routes
        $this->generateRoutes($table, $controller);

         // Generate RepositoryServiceProvider
        $this->generateRepositoryServiceProvider($model, $repository, $interface);

        // Generate Test
        $this->generateTest($model, $controller);


        $this->info('CRUD operations generated successfully.');
    }

    protected function generateApiResponseClass()
    {
        Artisan::call('make:class', [
            'name' => '/Classes/ApiResponseClass' ,
        ]);

        $path = app_path("Classes/ApiResponseClass.php");

        if (File::exists($path)) {
            $content = <<<EOT
    <?php

    namespace App\Classes;

    use Illuminate\Support\Facades\DB;
    use Illuminate\Http\Exceptions\HttpResponseException;
    use Illuminate\Support\Facades\Log;

    class ApiResponseClass
    {
        public static function rollback(\$e, \$message = "Something went wrong! Process not completed")
        {
            DB::rollBack();
            self::throw(\$e, \$message);
        }

        public static function throw(\$e, \$message = "Something went wrong! Process not completed")
        {
            Log::info(\$e);
            throw new HttpResponseException(response()->json(["message" => \$message], 500));
        }

        public static function sendResponse(\$result, \$message, \$code = 200)
        {
            \$response = [
                'success' => true,
                'data'    => \$result
            ];
            if (!empty(\$message)) {
                \$response['message'] = \$message;
            }
            return response()->json(\$response, \$code);
        }
    }
    EOT;
          
            file_put_contents($path, $content);
            $this->info("ApiResponseClass created successfully.");
        } else {
            $this->warn("ApiResponseClass already exists.");
        }
    }

    protected function getModelName($table)
    {
        $specialCases = [
            'media' => 'Media',
        ];

        return $specialCases[$table] ?? Str::studly(Str::singular($table));
    }

    protected function generateModel($table, $model)
    {
        $columns = Schema::getColumnListing($table);
        $fillable = array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        Artisan::call('make:model', [
            'name' => $model,
            '--factory' => true,
        ]);

        $modelPath = app_path("Models/{$model}.php");
        if (File::exists($modelPath)) {
            $modelContent = file_get_contents($modelPath);
            $fillableArray = "protected \$fillable = ['" . implode("', '", $fillable) . "'];";

            $modelContent = str_replace("use HasFactory;", "use HasFactory;\n\n    {$fillableArray}", $modelContent);
            file_put_contents($modelPath, $modelContent);

            $this->info("Model $model created successfully with fillable properties.");
        }
    }

    protected function generateRepository($model, $repository, $interface)
    {
        // Ensure the directory exists
        if (!File::isDirectory(app_path('Repositories'))) {
            File::makeDirectory(app_path('Repositories'), 0755, true);
        }
    
        if (!File::isDirectory(app_path('Interfaces'))) {
            File::makeDirectory(app_path('Interfaces'), 0755, true);
        }
    
        // Create the Repository Interface
        $interfacePath = app_path("Interfaces/{$interface}.php");
        if (!File::exists($interfacePath)) {
            $interfaceContent = <<<EOT
    <?php
    
    namespace App\Interfaces;
    
    interface {$interface}
    {
        public function index();
        public function getById(\$id);
        public function store(array \$data);
        public function update(array \$data, \$id);
        public function delete(\$id);
    }
    EOT;
            File::put($interfacePath, $interfaceContent);
            $this->info("Interface {$interface} created successfully.");
        } else {
            $this->warn("Interface {$interface} already exists.");
        }
    
        // Create the Repository Class
        $repositoryPath = app_path("Repositories/{$repository}.php");
        if (!File::exists($repositoryPath)) {
            $repositoryContent = <<<EOT
    <?php
    
    namespace App\Repositories;
    
    use App\Models\\$model;
    use App\Interfaces\\{$interface};
    
    class {$repository} implements {$interface}
    {
        public function index()
        {
            return $model::all();
        }
    
        public function getById(\$id)
        {
            return $model::findOrFail(\$id);
        }
    
        public function store(array \$data)
        {
            return $model::create(\$data);
        }
    
        public function update(array \$data, \$id)
        {
            return $model::whereId(\$id)->update(\$data);
        }
    
        public function delete(\$id)
        {
            $model::destroy(\$id);
        }
    }
    EOT;
            File::put($repositoryPath, $repositoryContent);
            $this->info("Repository {$repository} created successfully.");
        } else {
            $this->warn("Repository {$repository} already exists.");
        }
    }

    protected function generateController($model, $controller, $repository, $storeRequest, $updateRequest)
    {
        Artisan::call('make:controller', [
            'name' => $controller,
            '--resource' => true,
            '--model' => $model,
        ]);

        $controllerPath = app_path("Http/Controllers/{$controller}.php");
        if (File::exists($controllerPath)) {
            $controllerContent = <<<EOT
<?php

namespace App\Http\Controllers;

use App\Repositories\\{$repository};
use App\Http\Requests\\{$storeRequest};
use App\Http\Requests\\{$updateRequest};
use App\Classes\ApiResponseClass;
use App\Http\Resources\\{$model}Resource;
use Illuminate\Support\Facades\DB;

class {$controller} extends Controller
{
    private \${$repository};

    public function __construct({$repository} \${$repository})
    {
        \$this->{$repository} = \${$repository};
    }

    public function index()
    {
        \$data = \$this->{$repository}->index();
        return ApiResponseClass::sendResponse({$model}Resource::collection(\$data), '', 200);
    }

    public function store({$storeRequest} \$request)
    {
        DB::beginTransaction();
        try {
            \$product = \$this->{$repository}->store(\$request->validated());
            DB::commit();
            return ApiResponseClass::sendResponse(new {$model}Resource(\$product), 'Product Create Successful', 201);
        } catch (\Exception \$ex) {
            return ApiResponseClass::rollback(\$ex);
        }
    }

    public function show(\$id)
    {
        \$product = \$this->{$repository}->getById(\$id);
        return ApiResponseClass::sendResponse(new {$model}Resource(\$product), '', 200);
    }

    public function update({$updateRequest} \$request, \$id)
    {
        DB::beginTransaction();
        try {
            \$product = \$this->{$repository}->update(\$request->validated(), \$id);
            DB::commit();
            return ApiResponseClass::sendResponse('Product Update Successful', '', 200);
        } catch (\Exception \$ex) {
            return ApiResponseClass::rollback(\$ex);
        }
    }

    public function destroy(\$id)
    {
        \$this->{$repository}->delete(\$id);
        return ApiResponseClass::sendResponse('Product Delete Successful', '', 204);
    }
}
EOT;

            file_put_contents($controllerPath, $controllerContent);
            $this->info("Controller $controller created and populated successfully.");
        }
    }

    protected function generateResource($table, $model, $resource)
    {
        // Ensure the directory exists
        if (!File::isDirectory(app_path('Http/Resources'))) {
            File::makeDirectory(app_path('Http/Resources'), 0755, true);
        }
    
        // Create the Resource Class
        $resourcePath = app_path("Http/Resources/{$resource}.php");
        if (!File::exists($resourcePath)) {
            Artisan::call('make:resource', ['name' => $resource]);
    
            $columns = Schema::getColumnListing($table);
            $fieldsArray = "";
            foreach ($columns as $column) {
                $fieldsArray .= "            '{$column}' => \$this->{$column},\n";
            }
    
            // Prepare the resource content with the `toArray` method filled with columns
            $resourceStub = <<<EOT
        public function toArray(\$request)
        {
            return [
    $fieldsArray
            ];
        }
    }
    EOT;
    
            // Read the generated file content
            $resourceContent = file_get_contents($resourcePath);
    
            // Replace the existing `toArray` method content with the new content
            $resourceContent = preg_replace('/public function toArray\(.*?\{.*?\}.*?}/s', $resourceStub, $resourceContent);
    
            // Write the updated content back to the file
            File::put($resourcePath, $resourceContent);
    
            $this->info("Resource {$resource} created successfully with all fields.");
        } else {
            $this->warn("Resource {$resource} already exists.");
        }
    }

    protected function generateRequest($request, $table)
    {
        // Ensure the directory exists
        if (!File::isDirectory(app_path('Http/Requests'))) {
            File::makeDirectory(app_path('Http/Requests'), 0755, true);
        }

        // Create the Request Class
        $requestPath = app_path("Http/Requests/{$request}.php");
        if (!File::exists($requestPath)) {
            Artisan::call('make:request', ['name' => $request]);

            // Get the columns for the table
            $columns = Schema::getColumnListing($table);
            $rules = [];

            foreach ($columns as $column) {
                if (!in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    // Customize rules based on the column name or type
                    $rules[$column] = 'required'; // Default rule, can be expanded based on column types
                }
            }

            // Build the rules array as a string
            $rulesArray = "return [\n";
            foreach ($rules as $column => $rule) {
                $rulesArray .= "            '{$column}' => '{$rule}',\n";
            }
            $rulesArray .= "        ];";

            // Get the current content of the request class
            $requestContent = file_get_contents($requestPath);

            // Replace the placeholder comment with the generated rules
            $requestContent = preg_replace(
                '/public function rules\(\): array\s*\{\s*return \[\];\s*\}/',
                "public function rules(): array\n    {\n        $rulesArray\n    }",
                $requestContent
            );

            // Set authorize to true, assuming the user should be authorized
            $requestContent = preg_replace(
                '/public function authorize\(\): bool\s*\{\s*return false;\s*\}/',
                'public function authorize(): bool { return true; }',
                $requestContent
            );

            // Write the updated content back to the file
            File::put($requestPath, $requestContent);

            $this->info("Request {$request} created successfully with validation rules.");
        } else {
            $this->warn("Request {$request} already exists.");
        }
    }

    protected function generateFactory($table, $model)
    {
        // Generate the factory using Artisan
        Artisan::call('make:factory', [
            'name' => "{$model}Factory",
            '--model' => $model,
        ]);

        $factoryPath = base_path("database/factories/{$model}Factory.php");

        if (File::exists($factoryPath)) {
            // Retrieve the columns from the table
            $columns = Schema::getColumnListing($table);
            $definitionArray = '';

            foreach ($columns as $column) {
                if (!in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    $definitionArray .= $this->getColumnDefinition($column);
                }
            }

            $factoryContent = <<<EOT
        public function definition()
        {
            return [
    $definitionArray
            ];
        }
    EOT;

            // Insert the definition method into the factory
            $factoryFileContent = file_get_contents($factoryPath);
            $factoryFileContent = preg_replace('/public function definition\(\).*\{.*?\}/s', $factoryContent, $factoryFileContent);
            file_put_contents($factoryPath, $factoryFileContent);

            $this->info("Factory for $model created successfully with field definitions.");
        } else {
            $this->warn("Factory for $model already exists.");
        }
    }

    protected function getColumnDefinition($column)
    {
        if (Str::contains($column, 'email')) {
            return "            '$column' => \$this->faker->email(),\n";
        } elseif (Str::contains($column, 'name')) {
            return "            '$column' => \$this->faker->name(),\n";
        } elseif (Str::contains($column, 'title')) {
            return "            '$column' => \$this->faker->sentence(),\n";
        } elseif (Str::contains($column, 'content') || Str::contains($column, 'description')) {
            return "            '$column' => \$this->faker->paragraph(),\n";
        } elseif (Str::contains($column, 'date')) {
            return "            '$column' => \$this->faker->date(),\n";
        } elseif (Str::contains($column, 'url')) {
            return "            '$column' => \$this->faker->url(),\n";
        } elseif (Str::contains($column, 'price') || Str::contains($column, 'amount')) {
            return "            '$column' => \$this->faker->randomFloat(2, 10, 1000),\n";
        } elseif (Str::contains($column, 'phone')) {
            return "            '$column' => \$this->faker->phoneNumber(),\n";
        } elseif (Str::contains($column, 'slug')) {
            return "            '$column' => \$this->faker->slug(),\n";
        } elseif (Str::contains($column, 'uuid')) {
            return "            '$column' => \$this->faker->uuid(),\n";
        } elseif (Str::contains($column, 'ip')) {
            return "            '$column' => \$this->faker->ipv4(),\n";
        } elseif (Str::contains($column, 'password')) {
            return "            '$column' => bcrypt('password'),\n";
        } elseif (Str::contains($column, 'status')) {
            return "            '$column' => \$this->faker->randomElement(['active', 'inactive']),\n";
        } elseif (Str::contains($column, 'boolean') || Str::contains($column, 'is_') || Str::contains($column, '_flag')) {
            return "            '$column' => \$this->faker->boolean(),\n";
        } else {
            return "            '$column' => \$this->faker->word(),\n";
        }
    }

    protected function generateTest($model, $controller)
    {
        $testClass = "{$controller}Test";
        $testPath = base_path("tests/Feature/{$testClass}.php");

        if (!File::exists($testPath)) {
            File::put($testPath, $this->getTestStub($model, $controller));
            $this->info("Test $testClass created successfully.");
        } else {
            $this->warn("Test $testClass already exists.");
        }
    }

    protected function getTestStub($model, $controller)
    {
        $namespace = "Tests\\Feature";
        $modelVariable = Str::camel(Str::singular($model));
        $modelPlural = Str::snake(Str::plural($model));

        return <<<EOT
    <?php

    namespace $namespace;

    use App\Models\\$model;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class {$controller}Test extends TestCase
    {
        use RefreshDatabase;

        /** @test */
        public function it_can_list_all_{$modelPlural}()
        {
            \$response = \$this->getJson(route('{$modelPlural}.index'));

            \$response->assertStatus(200);
        }

        /** @test */
        public function it_can_create_a_{$modelVariable}()
        {
            \$data = $model::factory()->make()->toArray();

            \$response = \$this->postJson(route('{$modelPlural}.store'), \$data);

            \$response->assertStatus(201);
        }

        /** @test */
        public function it_can_show_a_{$modelVariable}()
        {
            \$model = $model::factory()->create();

            \$response = \$this->getJson(route('{$modelPlural}.show', \$model));

            \$response->assertStatus(200);
        }

        /** @test */
        public function it_can_update_a_{$modelVariable}()
        {
            \$model = $model::factory()->create();
            \$data = $model::factory()->make()->toArray();

            \$response = \$this->putJson(route('{$modelPlural}.update', \$model), \$data);

            \$response->assertStatus(200);
        }

        /** @test */
        public function it_can_delete_a_{$modelVariable}()
        {
            \$model = $model::factory()->create();

            \$response = \$this->deleteJson(route('{$modelPlural}.destroy', \$model));

            \$response->assertStatus(204);
        }
    }
    EOT;
    }

    protected function generateRoutes($table, $controller)
    {
        $routePath = base_path('routes/api.php');  // Use the API routes file as per best practices
        $modelNamePlural = Str::snake(Str::pluralStudly($this->getModelName($table)));

        $useStatement = "use App\\Http\\Controllers\\{$controller};\n";
        $routes = <<<EOT

    // Routes for $controller
    Route::apiResource('/$modelNamePlural', {$controller}::class);

    EOT;

        // Check if the use statement already exists in the file
        $routeFileContent = File::exists($routePath) ? File::get($routePath) : '';

        if (strpos($routeFileContent, "use App\\Http\\Controllers\\{$controller};") === false) {
            // Find the position to insert the use statement after the last use statement
            if (preg_match('/^(use .+;)/m', $routeFileContent, $matches, PREG_OFFSET_CAPTURE)) {
                $lastUseStatementPos = strrpos($routeFileContent, $matches[count($matches) - 1][0]) + strlen($matches[count($matches) - 1][0]);
                $updatedContent = substr_replace($routeFileContent, "\n$useStatement", $lastUseStatementPos, 0);
            } else {
                // If no use statements are found, place it at the top
                $updatedContent = "<?php\n\n$useStatement\n" . $routeFileContent;
            }

            // Write the updated content back to the file
            File::put($routePath, $updatedContent);
            $this->info("Use statement for $controller added to api.php successfully.");
        } else {
            $this->warn("Use statement for $controller already exists in api.php.");
        }

        // Add the routes if they don't already exist
        if (strpos($routeFileContent, "Route::apiResource('/$modelNamePlural'") === false) {
            File::append($routePath, $routes);
            $this->info("API routes for $controller added successfully.");
        } else {
            $this->warn("Routes for $controller already exist.");
        }
    }

    protected function generateRepositoryServiceProvider($model, $repository, $interface)
    {
        Artisan::call('make:provider', [
            'name' => 'RepositoryServiceProvider',
        ]);
    
        $providerPath = app_path("Providers/RepositoryServiceProvider.php");
    
        if (File::exists($providerPath)) {
            $binding = <<<EOT
            \$this->app->bind(
                \\App\\Interfaces\\{$interface}::class,
                \\App\\Repositories\\{$repository}::class
            );
    EOT;
    
            $providerContent = file_get_contents($providerPath);
    
            // Add the binding to the register method if it's not already there
            if (strpos($providerContent, "App\\Interfaces\\{$interface}::class") === false) {
                $providerContent = preg_replace('/(public function register\(\): void\s+\{)/', "$1\n$binding\n", $providerContent);
            }
    
            file_put_contents($providerPath, $providerContent);
    
            $this->info("RepositoryServiceProvider created and updated successfully.");
        } else {
            $this->warn("RepositoryServiceProvider not found.");
        }
    }
}