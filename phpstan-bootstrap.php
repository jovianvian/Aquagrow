<?php

require __DIR__.'/vendor/larastan/larastan/bootstrap.php';

if (defined('LARAVEL_VERSION') && ! defined('Larastan\\Larastan\\LARAVEL_VERSION')) {
    define('Larastan\\Larastan\\LARAVEL_VERSION', LARAVEL_VERSION);
}
