<?php

use Illuminate\Foundation\Application;

require_once __DIR__.'/vendor/autoload.php';
require __DIR__.'/vendor/larastan/larastan/bootstrap.php';

if (! defined('Larastan\\Larastan\\LARAVEL_VERSION')) {
    define('Larastan\\Larastan\\LARAVEL_VERSION', defined('LARAVEL_VERSION') ? LARAVEL_VERSION : Application::VERSION);
}
