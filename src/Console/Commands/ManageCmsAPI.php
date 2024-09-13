<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ManageCmsAPI extends Command
{
    protected $signature = 'manage:crud {action}';
    
    protected $description = 'Create or delete CRUD operations for specified tables';

    protected $tables = [
        'pages',
        'categories',
        'posts',
        'media',
        'tags',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');

        if (!in_array($action, ['create', 'delete'])) {
            $this->error("Invalid action. Please use 'create' or 'delete'.");
            return 1;
        }

        foreach ($this->tables as $table) {
            $this->info(ucfirst($action) . " CRUD operations for table: {$table}");

            $exitCode = 0;

            if ($action === 'create') {
                $exitCode = Artisan::call('generate:crud', ['table' => $table]);
            } elseif ($action === 'delete') {
                $exitCode = Artisan::call('delete:crud', ['table' => $table]);
            }

            // Capture and display the output
            $this->line(Artisan::output());

            // Check if there was an error
            if ($exitCode !== 0) {
                $this->error("Failed to {$action} CRUD operations for table: {$table}");
            } else {
                $this->info("Successfully completed {$action} CRUD operations for table: {$table}");
            }
        }

        $this->info('CRUD operations have been processed successfully.');
        return 0;
    }
}