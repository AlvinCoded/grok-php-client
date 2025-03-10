<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
    
    echo "Loaded .env file\n";
    echo "GROK_API_KEY: " . (isset($_ENV['GROK_API_KEY']) ? 'is set' : 'is NOT set') . "\n";
}

