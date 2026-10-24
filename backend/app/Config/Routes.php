<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = service('routes');

// TEMP STUB - see artifacts/Routes.RESTORE.php if broken
$routes->get('aktivitas', 'AktivitasController::index', ['filter' => 'auth']);
$routes->get('aktivitas/(:num)', 'AktivitasController::detail/$1', ['filter' => 'auth']);
