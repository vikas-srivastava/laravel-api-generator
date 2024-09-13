<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\APIBuilder;

class GenerateAPICommand extends Command
{
    protected $signature = 'vcapi:generate {--config=cms_modules}';
    protected $description = 'Generate CMS modules based on the configuration file';

    private $apiBuilder;

    public function __construct(APIBuilder $apiBuilder)
    {
        parent::__construct();
        $this->apiBuilder = $apiBuilder;
    }

    public function handle()
    {
        $configKey = $this->option('config');
        $config = config($configKey);

        if (!$config) {
            $this->error("Configuration not found: $configKey");
            return 1;
        }

        foreach ($config['modules'] as $moduleName => $moduleConfig) {
            $this->info("Generating module: $moduleName");
            $this->apiBuilder->generate($moduleName, $moduleConfig, $this);
        }

        $this->info('CMS modules generated successfully.');
    }
}