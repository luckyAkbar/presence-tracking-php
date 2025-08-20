<?php

// Bootstrap the application to make Config class available
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/bootstrap.php';

use App\Config\Config;

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        // 'production' => [
        //     'adapter' => 'mysql',
        //     'host' => 'localhost',
        //     'name' => 'production_db',
        //     'user' => 'root',
        //     'pass' => '',
        //     'port' => '3306',
        //     'charset' => 'utf8',
        // ],
        'development' => [
            'adapter' => 'mysql',
            'host' => Config::getString('db_host'),
            'name' => Config::getString('db_name'),
            'user' => Config::getString('db_user'),
            'pass' => Config::getString('db_pass'),
            'port' => Config::getInt('db_port'),
            'charset' => 'utf8',
        ],
        // 'testing' => [
        //     'adapter' => 'mysql',
        //     'host' => 'localhost',
        //     'name' => 'testing_db',
        //     'user' => 'root',
        //     'pass' => '',
        //     'port' => '3306',
        //     'charset' => 'utf8',
        // ]
    ],
    'version_order' => 'creation'
];
