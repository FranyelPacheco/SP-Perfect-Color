<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if (is_file(__DIR__ . '/app/controllers/frontController.php')) {
    require_once __DIR__ . '/app/controllers/frontController.php';
    $objeto = new frontController();
} else {
    echo 'Error de entrada';
}