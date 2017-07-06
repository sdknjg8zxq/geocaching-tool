<?php

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');
$app['db.options'] = array(
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'geocaching',
    'user' => 'user',
    'password' => 'password',
    'port' => 3306
);
$app['google.options'] = array(
    'api_key' => 'YOUR_GOOGLE_API_KEY'
);
$app['github_url'] = 'https://github.com/eibt/geocaching-tool';
$app['version'] = '0.1';
$app['require_https'] = true;
