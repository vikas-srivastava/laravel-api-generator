<?php

namespace App\Services\Generators;

use Illuminate\Support\Str;

class TestGenerator extends Generator
{
    public function generate(string $moduleName, string $className, array $classConfig, $command = null): void
    {
        $testName = Str::studly($className) . 'Test';
        $namespace = "Tests\\Feature\\$moduleName";
        $path = base_path("tests/Feature/$moduleName/$testName.php");

        $this->createDirectory(dirname($path));

        $modelName = Str::studly($className);
        $variableName = Str::camel($className);
        $routeName = Str::kebab(Str::plural($className));

        $content = <<<EOT
<?php

namespace $namespace;

use App\\Models\\$moduleName\\$modelName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\\TestCase;

class $testName extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_{$routeName}()
    {
        \$response = \$this->getJson(route('$routeName.index'));
        \$response->assertStatus(200);
    }

    /** @test */
    public function it_can_create_a_{$variableName}()
    {
        \$data = $modelName::factory()->make()->toArray();
        \$response = \$this->postJson(route('$routeName.store'), \$data);
        \$response->assertStatus(201);
    }

    /** @test */
    public function it_can_show_a_{$variableName}()
    {
        \${$variableName} = $modelName::factory()->create();
        \$response = \$this->getJson(route('$routeName.show', \${$variableName}));
        \$response->assertStatus(200);
    }

    /** @test */
    public function it_can_update_a_{$variableName}()
    {
        \${$variableName} = $modelName::factory()->create();
        \$data = $modelName::factory()->make()->toArray();
        \$response = \$this->putJson(route('$routeName.update', \${$variableName}), \$data);
        \$response->assertStatus(200);
    }

    /** @test */
    public function it_can_delete_a_{$variableName}()
    {
        \${$variableName} = $modelName::factory()->create();
        \$response = \$this->deleteJson(route('$routeName.destroy', \${$variableName}));
        \$response->assertStatus(204);
    }
}
EOT;

        $this->writeToPHP($path, $content);

        $this->logInfo("Tests for $moduleName -  $className added t $path", $command);
    }
}