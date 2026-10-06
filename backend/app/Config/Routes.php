<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'HealthController::index');
$routes->get('health', 'HealthController::index');

$routes->get('setup/status', 'SetupController::status');
$routes->post('setup', 'SetupController::run');

$routes->post('auth/login', 'AuthController::login');
$routes->post('auth/logout', 'AuthController::logout');
$routes->get('auth/me', 'AuthController::me', ['filter' => 'auth']);
$routes->post('auth/change-credential', 'AuthController::changeCredential', ['filter' => 'auth']);

$routes->get('pengaturan', 'PengaturanController::index', ['filter' => 'auth']);
$routes->put('pengaturan/aplikasi', 'PengaturanController::updateAplikasi', ['filter' => 'auth']);
$routes->put('pengaturan/warga', 'PengaturanController::updateWarga', ['filter' => 'auth']);
$routes->get('blok', 'PengaturanController::listBlok', ['filter' => 'auth']);
$routes->post('blok', 'PengaturanController::tambahBlok', ['filter' => 'auth']);

$routes->get('warga', 'KeluargaController::index', ['filter' => 'auth']);
$routes->get('warga/(:num)', 'KeluargaController::show/$1', ['filter' => 'auth']);
$routes->post('warga', 'KeluargaController::create', ['filter' => 'auth']);
$routes->put('warga/(:num)', 'KeluargaController::update/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/anggota', 'KeluargaController::tambahAnggota/$1', ['filter' => 'auth']);
$routes->put('anggota/(:num)', 'KeluargaController::updateAnggota/$1', ['filter' => 'auth']);
$routes->post('anggota/(:num)/status', 'KeluargaController::statusAnggota/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/pindah', 'KeluargaController::pindah/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/reset-pin', 'KeluargaController::resetPin/$1', ['filter' => 'auth']);

$routes->get('pengurus', 'PengurusController::index', ['filter' => 'auth']);
$routes->post('pengurus', 'PengurusController::angkat', ['filter' => 'auth']);
$routes->put('pengurus/(:num)', 'PengurusController::update/$1', ['filter' => 'auth']);
$routes->get('struktur', 'PengurusController::struktur', ['filter' => 'auth']);
$routes->get('bantuan', 'PengurusController::bantuan', ['filter' => 'auth']);

$routes->get('aktivitas', 'AktivitasController::index', ['filter' => 'auth']);

$routes->get('portal/warga', 'PortalController::daftarWarga', ['filter' => 'auth']);
