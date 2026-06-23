<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('SRC',  ROOT . '/src');

require ROOT . '/config/bootstrap.php';

$router = new \Core\Router();
require ROOT . '/config/routes.php';
$router->dispatch();
