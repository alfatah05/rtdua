<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'HealthController::index');
$routes->get('health', 'HealthController::index');

// Setup (sekali pakai)
$routes->get('setup/status', 'SetupController::status');
$routes->post('setup', 'SetupController::run');

// Auth
$routes->post('auth/login', 'AuthController::login');
$routes->post('auth/logout', 'AuthController::logout');
$routes->get('auth/me', 'AuthController::me', ['filter' => 'auth']);
$routes->post('auth/change-credential', 'AuthController::changeCredential', ['filter' => 'auth']);
