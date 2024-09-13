<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GenerateFrontend extends Command
{
  protected $signature = 'generate:frontend {model : The name of the model}';
  protected $description = 'Generate admin and frontend views and routes for a given model';

  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $modelName = $this->argument('model');

    if (!class_exists("App\\Models\\{$modelName}")) {
      $this->error("Model {$modelName} does not exist.");
      return;
    }

    $modelNameLower = Str::lower($modelName);
    $modelNamePlural = Str::plural($modelNameLower);
    $modelNameCapitalPlural = Str::plural(ucfirst($modelName));

    $this->updateController($modelName, $modelNameCapitalPlural);
    $this->createRoutes($modelName, $modelNameLower, $modelNamePlural, $modelNameCapitalPlural);
    $this->createAdminViews($modelName, $modelNameLower, $modelNameCapitalPlural);
    $this->createFrontendView($modelName, $modelNameLower, $modelNameCapitalPlural);

    $this->info("Admin and frontend views and routes generated successfully for {$modelName}!");
  }

  private function updateController($modelName, $modelNameCapitalPlural)
  {
    $controllerPath = app_path("Http/Controllers/{$modelName}Controller.php");

    if (!File::exists($controllerPath)) {
      $this->error("Controller for {$modelName} not found at {$controllerPath}");
      return;
    }

    $controllerContent = File::get($controllerPath);

    $resourceName = ucfirst($modelName) . 'Resource';
    $repositoryName = ucfirst($modelName) . 'Repository';

    if (strpos($controllerContent, 'use Inertia\\Inertia;') === false) {
      $controllerContent = $this->addInertiaImport($controllerContent);
    }

    $newMethods = <<<EOD

        public function adminIndex()
        {
            \$data = \$this->{$repositoryName}->index();
            return Inertia::render('Admin/{$modelNameCapitalPlural}/Index', [
                'items' => {$resourceName}::collection(\$data)
            ]);
        }

        public function adminCreate()
        {
            return Inertia::render('Admin/{$modelNameCapitalPlural}/Create');
        }

        public function adminEdit(\$id)
        {
            \$item = \$this->{$repositoryName}->getById(\$id);
            return Inertia::render('Admin/{$modelNameCapitalPlural}/Edit', [
                'item' => new {$resourceName}(\$item)
            ]);
        }

        public function frontendShow(\$id)
        {
            \$item = \$this->{$repositoryName}->getById(\$id);
            return Inertia::render('Frontend/{$modelNameCapitalPlural}/Show', [
                'item' => new {$resourceName}(\$item)
            ]);
        }
    EOD;

    $lastClosingBracePos = strrpos($controllerContent, '}');

    if ($lastClosingBracePos !== false) {
      $updatedContent = substr_replace($controllerContent, $newMethods . "\n}", $lastClosingBracePos, 1);
      File::put($controllerPath, $updatedContent);
    }

    $this->logOperation("Controller updated for {$modelName}");
  }

  private function addInertiaImport($controllerContent)
  {
    $lastUsePos = strrpos($controllerContent, 'use ');

    if ($lastUsePos !== false) {
      $endOfLastUseLine = strpos($controllerContent, ';', $lastUsePos) + 1;
      $controllerContent = substr_replace(
        $controllerContent,
        "\nuse Inertia\\Inertia;",
        $endOfLastUseLine,
        0
      );
    } else {
      $controllerContent = preg_replace('/<\?php\s+namespace [^;]+;/', "$0\n\nuse Inertia\\Inertia;", $controllerContent, 1);
    }

    return $controllerContent;
  }

  private function createRoutes($modelName, $modelNameLower, $modelNamePlural, $modelNameCapitalPlural)
  {
    $routesPath = base_path('routes/web.php');
    $newRoutes = <<<EOD

Route::prefix('admin/{$modelNamePlural}')->group(function () {
    Route::get('/', [{$modelName}Controller::class, 'adminIndex'])->name('admin.{$modelNamePlural}.index');
    Route::get('/create', [{$modelName}Controller::class, 'adminCreate'])->name('admin.{$modelNamePlural}.create');
    Route::get('/{id}/edit', [{$modelName}Controller::class, 'adminEdit'])->name('admin.{$modelNamePlural}.edit');
});

Route::get('/{$modelNamePlural}/{id}', [{$modelName}Controller::class, 'frontendShow'])->name('frontend.{$modelNamePlural}.show');

EOD;

    $this->appendUseStatement($routesPath, "App\Http\Controllers\\{$modelName}Controller");
    File::append($routesPath, $newRoutes);

    $this->logOperation("Routes created for {$modelName}");
  }

  private function appendUseStatement($filePath, $useStatement)
  {
    $fileContent = File::get($filePath);
    $lastUsePos = strrpos($fileContent, 'use ');

    if ($lastUsePos !== false) {
      $endOfLastUseLine = strpos($fileContent, ';', $lastUsePos) + 1;
      $fileContent = substr_replace($fileContent, "\nuse {$useStatement};", $endOfLastUseLine, 0);
      File::put($filePath, $fileContent);
    } else {
      $fileContent = preg_replace('/<\?php\s+namespace [^;]+;/', "$0\n\nuse {$useStatement};", $fileContent, 1);
      File::put($filePath, $fileContent);
    }
  }

  private function createAdminViews($modelName, $modelNameLower, $modelNameCapitalPlural)
  {
    $viewsPath = resource_path("js/Pages/Admin/{$modelNameCapitalPlural}");
    File::makeDirectory($viewsPath, 0755, true, true);

    $indexContent = $this->getAdminIndexViewContent($modelName, $modelNameLower,  $modelNameCapitalPlural);
    $createContent = $this->getAdminCreateViewContent($modelName, $modelNameLower,  $modelNameCapitalPlural);
    $editContent = $this->getAdminEditViewContent($modelName, $modelNameLower,  $modelNameCapitalPlural);

    File::put("{$viewsPath}/Index.vue", $indexContent);
    File::put("{$viewsPath}/Create.vue", $createContent);
    File::put("{$viewsPath}/Edit.vue", $editContent);

    $this->logOperation("Admin views created for {$modelName}");
  }

  private function createFrontendView($modelName, $modelNameLower,  $modelNameCapitalPlural)
  {
    $viewsPath = resource_path("js/Pages/Frontend/{$modelNameCapitalPlural}");
    File::makeDirectory($viewsPath, 0755, true, true);

    $showContent = $this->getFrontendShowViewContent($modelName, $modelNameLower, $modelNameCapitalPlural);

    File::put("{$viewsPath}/Show.vue", $showContent);

    $this->logOperation("Frontend view created for {$modelName}");
  }

  private function getAdminIndexViewContent($modelName, $modelNameLower, $modelNameCapitalPlural)
  {
    return <<<EOD
<template>
  <AdminLayout>
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h1>{$modelName} Index</h1>
        </v-col>
        <v-col cols="12">
          <v-btn color="primary" :href="route('admin.{$modelNameLower}.create')">
            Create New {$modelName}
          </v-btn>
        </v-col>
        <v-col cols="12">
          <v-simple-table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ item.title }}</td>
                <td>
                  <v-btn color="primary" :href="route('admin.{$modelNameLower}.edit', item.id)">
                    Edit
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-simple-table>
        </v-col>
      </v-row>
    </v-container>
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  components: {
    AdminLayout,
  },
  props: ['items'],
}
</script>
EOD;
  }

  private function getAdminCreateViewContent($modelName, $modelNameLower,  $modelNameCapitalPlural)
  {
    return <<<EOD
<template>
  <AdminLayout>
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h1>Create {$modelName}</h1>
        </v-col>
        <v-col cols="12">
          <v-form @submit.prevent="submit">
            <v-text-field label="Title" v-model="form.title" required></v-text-field>
            <v-textarea label="Content" v-model="form.content" required></v-textarea>
            <v-btn type="submit" color="primary">Create</v-btn>
          </v-form>
        </v-col>
      </v-row>
    </v-container>
  </AdminLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  components: {
    AdminLayout,
  },
  setup() {
    const form = useForm({
      title: '',
      content: '',
    });

    function submit() {
      form.post(route('api.{$modelNameLower}.store'));
    }

    return { form, submit };
  },
}
</script>
EOD;
  }

  private function getAdminEditViewContent($modelName, $modelNameLower,  $modelNameCapitalPlural)
  {
    return <<<EOD
<template>
  <AdminLayout>
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <h1>Edit {$modelName}</h1>
        </v-col>
        <v-col cols="12">
          <v-form @submit.prevent="submit">
            <v-text-field label="Title" v-model="form.title" required></v-text-field>
            <v-textarea label="Content" v-model="form.content" required></v-textarea>
            <v-btn type="submit" color="primary">Update</v-btn>
          </v-form>
        </v-col>
      </v-row>
    </v-container>
  </AdminLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
  components: {
    AdminLayout,
  },
  props: ['item'],
  setup(props) {
    const form = useForm({
      title: props.item.title,
      content: props.item.content,
    });

    function submit() {
      form.put(route('api.{$modelNameLower}.update', props.item.id));
    }

    return { form, submit };
  },
}
</script>
EOD;
  }

  private function getFrontendShowViewContent($modelName, $modelNameCapitalPlural)
  {
    return <<<EOD
<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <h1>{{ item.title }}</h1>
        <div v-html="item.content"></div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  props: ['item'],
}
</script>
EOD;
  }

  private function logOperation($message)
  {
    Log::info("[GenerateFrontend] " . $message);
  }
}
