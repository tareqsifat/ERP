<?php

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    */
    'namespace' => 'Modules',

    'vapor_maintenance_mode' => env('VAPOR_MAINTENANCE_MODE', false),

    'stubs' => [
        'enabled' => false,
    ],

    'paths' => [
        'modules' => base_path('Modules'),
        'assets' => public_path('modules'),
        'migration' => base_path('database/migrations'),
        'app_folder' => 'app/',

        'generator' => [
            'model' => ['path' => 'app/Models', 'generate' => true],
            'provider' => ['path' => 'app/Providers', 'generate' => true],
            'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
            'filter' => ['path' => 'app/Http/Middleware', 'generate' => false],
            'request' => ['path' => 'app/Http/Requests', 'generate' => true],
            'resource' => ['path' => 'app/Http/Resources', 'generate' => true],
            'policies' => ['path' => 'app/Policies', 'generate' => false],
            'services' => ['path' => 'app/Services', 'generate' => true],
            'config' => ['path' => 'config', 'generate' => true],
            'factory' => ['path' => 'database/factories', 'generate' => true],
            'migration' => ['path' => 'database/migrations', 'generate' => true],
            'seeder' => ['path' => 'database/seeders', 'generate' => true],
            'lang' => ['path' => 'lang', 'generate' => false],
            'views' => ['path' => 'resources/views', 'generate' => false],
            'routes' => ['path' => 'routes', 'generate' => true],
            'test-feature' => ['path' => 'tests/Feature', 'generate' => true],
            'test-unit' => ['path' => 'tests/Unit', 'generate' => true],
        ],
    ],

    'auto-discover' => [
        'migrations' => true,
        'translations' => false,
    ],

    'commands' => ConsoleServiceProvider::defaultCommands()->toArray(),

    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    'composer' => [
        'vendor' => env('MODULE_VENDOR', 'garments-erp'),
        'author' => [
            'name' => env('MODULE_AUTHOR_NAME', 'Vishesh Textiles'),
            'email' => env('MODULE_AUTHOR_EMAIL', 'dev@vishesh-textiles.example'),
        ],
        'composer-output' => false,
    ],

    'register' => [
        'translations' => true,
        'files' => 'register',
    ],

    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
        ],
    ],

    'activator' => 'file',
];
