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
$routes->get('auth/blok-login', 'PengaturanController::listBlokLogin');

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

$routes->get('keuangan/iuran', 'KeuanganController::daftarIuran', ['filter' => 'auth']);
$routes->get('keuangan/keluarga/(:num)', 'KeuanganController::ringkasanKeluarga/$1', ['filter' => 'auth']);
$routes->post('keuangan/keluarga/(:num)/pratinjau', 'KeuanganController::pratinjauAlokasi/$1', ['filter' => 'auth']);
$routes->post('keuangan/nominal', 'KeuanganController::ubahNominal', ['filter' => 'auth']);
$routes->post('keuangan/pastikan-tagihan-kas', 'KeuanganController::pastikanTagihanKas', ['filter' => 'auth']);
$routes->post('keuangan/pembayaran', 'KeuanganController::catatPembayaran', ['filter' => 'auth']);
$routes->post('keuangan/pembayaran/(:num)/batal', 'KeuanganController::batalkanPembayaran/$1', ['filter' => 'auth']);
$routes->post('keuangan/denda/(:num)/batal', 'KeuanganController::batalkanDenda/$1', ['filter' => 'auth']);
$routes->get('keuangan/permintaan', 'KeuanganController::listPermintaan', ['filter' => 'auth']);
$routes->post('keuangan/permintaan/(:num)/konfirmasi', 'KeuanganController::konfirmasiPermintaan/$1', ['filter' => 'auth']);
$routes->post('keuangan/permintaan/(:num)/tolak', 'KeuanganController::tolakPermintaan/$1', ['filter' => 'auth']);
$routes->get('keuangan/kas/saldo', 'KeuanganController::saldoKas', ['filter' => 'auth']);
$routes->get('keuangan/kas', 'KeuanganController::listKas', ['filter' => 'auth']);
$routes->post('keuangan/kas', 'KeuanganController::tambahKas', ['filter' => 'auth']);
$routes->post('keuangan/kas/(:num)/batal', 'KeuanganController::batalkanKas/$1', ['filter' => 'auth']);
$routes->get('keuangan/iuran-khusus', 'KeuanganController::listIuranKhusus', ['filter' => 'auth']);
$routes->post('keuangan/iuran-khusus', 'KeuanganController::buatIuranKhusus', ['filter' => 'auth']);
$routes->put('keuangan/iuran-khusus/(:num)', 'KeuanganController::ubahIuranKhusus/$1', ['filter' => 'auth']);
$routes->post('keuangan/iuran-khusus/(:num)/batal', 'KeuanganController::batalkanIuranKhusus/$1', ['filter' => 'auth']);
$routes->get('keuangan/laporan', 'KeuanganController::laporan', ['filter' => 'auth']);

$routes->get('portal/keuangan', 'PortalKeuanganController::ringkasanSaya', ['filter' => 'auth']);
$routes->post('portal/keuangan/transfer', 'PortalKeuanganController::ajukanTransfer', ['filter' => 'auth']);
$routes->get('portal/keuangan/permintaan', 'PortalKeuanganController::statusPermintaan', ['filter' => 'auth']);

$routes->get('notifikasi', 'NotifikasiController::index', ['filter' => 'auth']);
$routes->get('notifikasi/badge', 'NotifikasiController::badge', ['filter' => 'auth']);
$routes->post('notifikasi/(:num)/baca', 'NotifikasiController::baca/$1', ['filter' => 'auth']);
$routes->post('notifikasi/baca-semua', 'NotifikasiController::bacaSemua', ['filter' => 'auth']);
$routes->get('push/vapid-public', 'PushController::vapidPublic', ['filter' => 'auth']);
$routes->post('push/subscribe', 'PushController::subscribe', ['filter' => 'auth']);
$routes->post('push/unsubscribe', 'PushController::unsubscribe', ['filter' => 'auth']);

$routes->get('tugas/harian', 'TugasController::harian', ['filter' => 'cron']);
$routes->get('tugas/sore', 'TugasController::sore', ['filter' => 'cron']);
$routes->get('tugas/5menit', 'TugasController::tiapLimaMenit', ['filter' => 'cron']);

$routes->get('pengumuman', 'KontenController::listPengumuman', ['filter' => 'auth']);
$routes->get('pengumuman/(:num)', 'KontenController::detailPengumuman/$1', ['filter' => 'auth']);
$routes->post('pengumuman', 'KontenController::buatPengumuman', ['filter' => 'auth']);
$routes->put('pengumuman/(:num)', 'KontenController::ubahPengumuman/$1', ['filter' => 'auth']);
$routes->delete('pengumuman/(:num)', 'KontenController::hapusPengumuman/$1', ['filter' => 'auth']);
$routes->get('program', 'KontenController::listProgram', ['filter' => 'auth']);
$routes->get('program/(:num)', 'KontenController::detailProgram/$1', ['filter' => 'auth']);
$routes->post('program', 'KontenController::buatProgram', ['filter' => 'auth']);
$routes->put('program/(:num)', 'KontenController::ubahProgram/$1', ['filter' => 'auth']);
$routes->delete('program/(:num)', 'KontenController::hapusProgram/$1', ['filter' => 'auth']);
$routes->get('galeri', 'KontenController::listAlbum', ['filter' => 'auth']);
$routes->get('galeri/(:num)', 'KontenController::detailAlbum/$1', ['filter' => 'auth']);
$routes->post('galeri', 'KontenController::buatAlbum', ['filter' => 'auth']);
$routes->delete('galeri/(:num)', 'KontenController::hapusAlbum/$1', ['filter' => 'auth']);
$routes->post('galeri/(:num)/foto', 'KontenController::tambahFoto/$1', ['filter' => 'auth']);
$routes->post('galeri/foto/hapus', 'KontenController::hapusFoto', ['filter' => 'auth']);

$routes->get('ronda/kalender', 'RondaController::kalender', ['filter' => 'auth']);
$routes->get('ronda/malam-ini', 'RondaController::malamIni', ['filter' => 'auth']);
$routes->get('ronda/terdekat', 'RondaController::malamTerdekat', ['filter' => 'auth']);
$routes->get('ronda/malam/(:segment)', 'RondaController::detailMalam/$1', ['filter' => 'auth']);
$routes->get('ronda/jadwal-tetap', 'RondaController::listJadwalTetap', ['filter' => 'auth']);
$routes->post('ronda/jadwal-tetap', 'RondaController::simpanJadwalTetap', ['filter' => 'auth']);
$routes->get('ronda/jadwal-khusus', 'RondaController::listJadwalKhusus', ['filter' => 'auth']);
$routes->post('ronda/jadwal-khusus', 'RondaController::simpanJadwalKhusus', ['filter' => 'auth']);
$routes->post('ronda/generate', 'RondaController::generateBulan', ['filter' => 'auth']);
$routes->post('ronda/isi-otomatis', 'RondaController::isiOtomatis', ['filter' => 'auth']);
$routes->post('ronda/buat-slot', 'RondaController::buatSlotKosong', ['filter' => 'auth']);
$routes->post('ronda/absen-manual', 'RondaController::absenManual', ['filter' => 'auth']);
$routes->post('ronda/absen', 'RondaController::absenWarga', ['filter' => 'auth']);
$routes->post('ronda/malam/(:num)/ganti-keluarga', 'RondaController::gantiKeluarga/$1', ['filter' => 'auth']);
$routes->post('ronda/absen/(:num)/batal', 'RondaController::batalkanAbsen/$1', ['filter' => 'auth']);
$routes->post('ronda/terbitkan-denda', 'RondaController::terbitkanDenda', ['filter' => 'auth']);

$routes->post('upload', 'UploadController::store');
$routes->get('media/(:segment)/(:segment)', 'UploadController::media/$1/$2');
