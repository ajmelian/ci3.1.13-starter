<?php
declare(strict_types=1);

use Dotenv\Dotenv;

define('APPPATH', __DIR__ . '/application/');
define('BASEPATH', __DIR__ . '/system/');
define('VIEWPATH', APPPATH . 'views/');

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'development');

if (file_exists(BASEPATH . 'core/CodeIgniter.php')) {
    require BASEPATH . 'core/CodeIgniter.php';
} else {
    echo 'Falta el núcleo de CodeIgniter. Asegúrate de colocar la carpeta system/.';
}
