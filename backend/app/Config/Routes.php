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
$routes->post('auth/update-foto', 'AuthController::updateFoto', ['filter' => 'auth']);
$routes->put('auth/profil', 'AuthController::updateProfil', ['filter' => 'auth']);
$routes->get('auth/blok-login', 'PengaturanController::listBlokLogin');

$routes->get('pengaturan', 'PengaturanController::index', ['filter' => 'auth']);
$routes->put('pengaturan/aplikasi', 'PengaturanController::updateAplikasi', ['filter' => 'auth']);
$routes->put('pengaturan/warga', 'PengaturanController::updateWarga', ['filter' => 'auth']);
$routes->get('blok', 'PengaturanController::listBlok', ['filter' => 'auth']);
$routes->post('blok', 'PengaturanController::tambahBlok', ['filter' => 'auth']);

$routes->get('warga', 'KeluargaController::index', ['filter' => 'auth']);
$routes->get('warga/cari-anggota', 'KeluargaController::cariAnggota', ['filter' => 'auth']);
$routes->get('warga/(:num)', 'KeluargaController::show/$1', ['filter' => 'auth']);
$routes->post('warga', 'KeluargaController::create', ['filter' => 'auth']);
$routes->put('warga/(:num)', 'KeluargaController::update/$1', ['filter' => 'auth']);
$routes->delete('warga/(:num)', 'KeluargaController::hapus/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/hapus', 'KeluargaController::hapus/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/anggota', 'KeluargaController::tambahAnggota/$1', ['filter' => 'auth']);
$routes->put('anggota/(:num)', 'KeluargaController::updateAnggota/$1', ['filter' => 'auth']);
$routes->post('anggota/(:num)/status', 'KeluargaController::statusAnggota/$1', ['filter' => 'auth']);
$routes->post('anggota/(:num)/hapus', 'KeluargaController::hapusAnggota/$1', ['filter' => 'auth']);
$routes->delete('anggota/(:num)', 'KeluargaController::hapusAnggota/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/pindah', 'KeluargaController::pindah/$1', ['filter' => 'auth']);
$routes->post('warga/(:num)/reset-pin', 'KeluargaController::resetPin/$1', ['filter' => 'auth']);

$routes->get('pengurus', 'PengurusController::index', ['filter' => 'auth']);
$routes->post('pengurus', 'PengurusController::angkat', ['filter' => 'auth']);
$routes->put('pengurus/(:num)', 'PengurusController::update/$1', ['filter' => 'auth']);
$routes->get('struktur', 'PengurusController::struktur', ['filter' => 'auth']);
$routes->get('bantuan', 'PengurusController::bantuan', ['filter' => 'auth']);

$routes->get('aktivitas', 'AktivitasController::index', ['filter' => 'auth']);
$routes->get('aktivitas/(:num)', 'AktivitasController::detail/$1', ['filter' => 'auth']);
$routes->get('catatan', 'CatatanController::index', ['filter' => 'auth']);
$routes->get('catatan/search', 'CatatanController::search', ['filter' => 'auth']);
$routes->get('catatan/hal/(:num)', 'CatatanController::byHal/$1', ['filter' => 'auth']);
$routes->post('catatan', 'CatatanController::create', ['filter' => 'auth']);
$routes->patch('catatan/(:num)', 'CatatanController::patch/$1', ['filter' => 'auth']);
$routes->put('catatan/(:num)', 'CatatanController::patch/$1', ['filter' => 'auth']);
$routes->delete('catatan/(:num)', 'CatatanController::delete/$1', ['filter' => 'auth']);


$routes->get('portal/warga', 'PortalController::daftarWarga', ['filter' => 'auth']);
$routes->get('portal/warga/(:num)', 'PortalController::detailKeluarga/$1', ['filter' => 'auth']);
$routes->get('portal/keluarga', 'PortalController::keluargaSaya', ['filter' => 'auth']);
$routes->post('portal/keluarga/anggota', 'PortalController::tambahAnggotaSaya', ['filter' => 'auth']);
$routes->put('portal/keluarga/anggota/(:num)', 'PortalController::updateAnggotaSaya/$1', ['filter' => 'auth']);

$routes->get('keuangan/iuran', 'KeuanganController::daftarIuran', ['filter' => 'auth']);
$routes->get('keuangan/keluarga/(:num)', 'KeuanganController::ringkasanKeluarga/$1', ['filter' => 'auth']);
$routes->post('keuangan/keluarga/(:num)/pratinjau', 'KeuanganController::pratinjauAlokasi/$1', ['filter' => 'auth']);
$routes->post('keuangan/nominal', 'KeuanganController::ubahNominal', ['filter' => 'auth']);
$routes->get('keuangan/iuran-komponen', 'KeuanganController::listIuranKomponen', ['filter' => 'auth']);
$routes->post('keuangan/iuran-komponen', 'KeuanganController::buatIuranKomponen', ['filter' => 'auth']);
$routes->put('keuangan/iuran-komponen/(:num)', 'KeuanganController::ubahIuranKomponen/$1', ['filter' => 'auth']);
$routes->delete('keuangan/iuran-komponen/(:num)', 'KeuanganController::hapusIuranKomponen/$1', ['filter' => 'auth']);
$routes->post('keuangan/iuran-komponen/terapkan-bulan-ini', 'KeuanganController::terapkanKomponenBulanIni', ['filter' => 'auth']);
$routes->get('keuangan/tarif-iuran', 'KeuanganController::listTarifIuran', ['filter' => 'auth']);
$routes->get('keuangan/tarif-iuran/map-keluarga', 'KeuanganController::mapKeluargaTarif', ['filter' => 'auth']);
$routes->get('keuangan/tarif-iuran/(:num)', 'KeuanganController::detailTarifIuran/$1', ['filter' => 'auth']);
$routes->post('keuangan/tarif-iuran', 'KeuanganController::buatTarifKhusus', ['filter' => 'auth']);
$routes->put('keuangan/tarif-iuran/(:num)', 'KeuanganController::ubahTarifIuran/$1', ['filter' => 'auth']);
$routes->delete('keuangan/tarif-iuran/(:num)', 'KeuanganController::hapusTarifKhusus/$1', ['filter' => 'auth']);
$routes->post('keuangan/tarif-iuran/(:num)/anggota', 'KeuanganController::setAnggotaTarif/$1', ['filter' => 'auth']);
$routes->post('keuangan/tarif-iuran/(:num)/anggota/tambah', 'KeuanganController::tambahAnggotaTarif/$1', ['filter' => 'auth']);
$routes->post('keuangan/tarif-iuran/(:num)/anggota/(:num)/hapus', 'KeuanganController::hapusAnggotaTarif/$1/$2', ['filter' => 'auth']);
$routes->get('keuangan/tarif-iuran/(:num)/komponen', 'KeuanganController::listKomponenTarif/$1', ['filter' => 'auth']);

$routes->post('keuangan/pastikan-tagihan-kas', 'KeuanganController::pastikanTagihanKas', ['filter' => 'auth']);
$routes->get('keuangan/pembayaran', 'KeuanganController::listPembayaran', ['filter' => 'auth']);
$routes->get('keuangan/pembayaran/(:num)', 'KeuanganController::detailPembayaran/$1', ['filter' => 'auth']);
$routes->post('keuangan/pembayaran', 'KeuanganController::catatPembayaran', ['filter' => 'auth']);
$routes->post('keuangan/pembayaran/(:num)/batal', 'KeuanganController::batalkanPembayaran/$1', ['filter' => 'auth']);
$routes->post('keuangan/denda/(:num)/batal', 'KeuanganController::batalkanDenda/$1', ['filter' => 'auth']);
$routes->get('keuangan/permintaan', 'KeuanganController::listPermintaan', ['filter' => 'auth']);
$routes->post('keuangan/permintaan/(:num)/konfirmasi', 'KeuanganController::konfirmasiPermintaan/$1', ['filter' => 'auth']);
$routes->post('keuangan/permintaan/(:num)/tolak', 'KeuanganController::tolakPermintaan/$1', ['filter' => 'auth']);
$routes->get('keuangan/kas/saldo', 'KeuanganController::saldoKas', ['filter' => 'auth']);
$routes->get('keuangan/kas', 'KeuanganController::listKas', ['filter' => 'auth']);
$routes->get('keuangan/kas/(:num)', 'KeuanganController::detailKas/$1', ['filter' => 'auth']);
$routes->post('keuangan/kas', 'KeuanganController::tambahKas', ['filter' => 'auth']);
$routes->post('keuangan/kas/(:num)/batal', 'KeuanganController::batalkanKas/$1', ['filter' => 'auth']);
$routes->post('keuangan/alokasi-pos', 'KeuanganController::alokasiKasPos', ['filter' => 'auth']);
$routes->post('keuangan/kas-pos/alokasi', 'KeuanganController::alokasiKasPos', ['filter' => 'auth']);
$routes->get('keuangan/kas-pos', 'KeuanganController::listKasPos', ['filter' => 'auth']);
$routes->post('keuangan/kas-pos', 'KeuanganController::buatKasPos', ['filter' => 'auth']);
$routes->put('keuangan/kas-pos/(:num)', 'KeuanganController::ubahKasPos/$1', ['filter' => 'auth']);
$routes->post('keuangan/kas-pos/(:num)/nonaktif', 'KeuanganController::nonaktifKasPos/$1', ['filter' => 'auth']);
$routes->get('keuangan/sub-pos', 'KeuanganController::listSubPos', ['filter' => 'auth']);
$routes->post('keuangan/sub-pos', 'KeuanganController::buatSubPos', ['filter' => 'auth']);
$routes->put('keuangan/sub-pos/(:num)', 'KeuanganController::ubahSubPos/$1', ['filter' => 'auth']);
$routes->delete('keuangan/sub-pos/(:num)', 'KeuanganController::hapusSubPos/$1', ['filter' => 'auth']);
$routes->get('keuangan/sub-pos/(:num)', 'KeuanganController::detailSubPos/$1', ['filter' => 'auth']);
$routes->get('keuangan/sub-pos/(:num)/mutasi', 'KeuanganController::listSubPosMutasi/$1', ['filter' => 'auth']);
$routes->post('keuangan/sub-pos/(:num)/masuk', 'KeuanganController::subPosMasuk/$1', ['filter' => 'auth']);
$routes->post('keuangan/sub-pos/(:num)/keluar', 'KeuanganController::subPosKeluar/$1', ['filter' => 'auth']);
$routes->post('keuangan/sub-pos/(:num)/alokasi', 'KeuanganController::subPosAlokasi/$1', ['filter' => 'auth']);
$routes->post('keuangan/sub-pos/mutasi/(:num)/batal', 'KeuanganController::subPosBatalMutasi/$1', ['filter' => 'auth']);
$routes->get('keuangan/iuran-khusus', 'KeuanganController::listIuranKhusus', ['filter' => 'auth']);
$routes->post('keuangan/iuran-khusus', 'KeuanganController::buatIuranKhusus', ['filter' => 'auth']);
$routes->put('keuangan/iuran-khusus/(:num)', 'KeuanganController::ubahIuranKhusus/$1', ['filter' => 'auth']);
$routes->post('keuangan/iuran-khusus/(:num)/batal', 'KeuanganController::batalkanIuranKhusus/$1', ['filter' => 'auth']);
$routes->post('keuangan/keluarga/(:num)/tagihan-khusus', 'KeuanganController::buatTagihanKhususKeluarga/$1', ['filter' => 'auth']);
$routes->put('keuangan/tagihan-khusus/(:num)', 'KeuanganController::ubahTagihanKhusus/$1', ['filter' => 'auth']);
$routes->post('keuangan/tagihan-khusus/(:num)/batal', 'KeuanganController::hapusTagihanKhusus/$1', ['filter' => 'auth']);
$routes->get('keuangan/laporan', 'KeuanganController::laporan', ['filter' => 'auth']);
$routes->get('keuangan/rekening', 'RekeningPembayaranController::index', ['filter' => 'auth']);
$routes->post('keuangan/rekening', 'RekeningPembayaranController::create', ['filter' => 'auth']);
$routes->put('keuangan/rekening/(:num)', 'RekeningPembayaranController::update/$1', ['filter' => 'auth']);
$routes->delete('keuangan/rekening/(:num)', 'RekeningPembayaranController::delete/$1', ['filter' => 'auth']);

$routes->get('portal/keuangan', 'PortalKeuanganController::ringkasanSaya', ['filter' => 'auth']);
$routes->post('portal/keuangan/transfer', 'PortalKeuanganController::ajukanTransfer', ['filter' => 'auth']);
$routes->get('portal/keuangan/permintaan', 'PortalKeuanganController::statusPermintaan', ['filter' => 'auth']);
$routes->get('portal/keuangan/kas', 'PortalKeuanganController::kas', ['filter' => 'auth']);
$routes->get('portal/keuangan/status-iuran', 'PortalKeuanganController::statusIuranWarga', ['filter' => 'auth']);
$routes->get('portal/keuangan/rekening', 'RekeningPembayaranController::portalList', ['filter' => 'auth']);

$routes->get('notifikasi', 'NotifikasiController::index', ['filter' => 'auth']);
$routes->get('notifikasi/badge', 'NotifikasiController::badge', ['filter' => 'auth']);
$routes->get('notifikasi/(:num)', 'NotifikasiController::show/$1', ['filter' => 'auth']);
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
$routes->put('pengumuman/(:num debatable'); // truncated intentionally - NEED FULL FILE
