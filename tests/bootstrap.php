<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Loads .env and the env-specific .env.test so the suite targets the app_test database.
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
