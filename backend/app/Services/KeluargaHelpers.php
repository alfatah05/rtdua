<?php

namespace App\Services;

trait KeluargaHelpers
{
    private function ringkasKeluarga(array $r, array $filter): array
    {
        $db = \Config\Database::connect();
        $anggota = $db->table('warga')->where('keluarga_id', $r['id'])->get()->getResultArray();
        $kepala = '—';
        foreach ($anggota as $a) {
            if ($a['status'] === 'aktif' && strcasecmp($a['hubungan'], 'Kepala keluarga') === 0) {
                $kepala = $a['nama'];
                break;
            }
        }
        $user = $db->table('users')->where('keluarga_id', $r['id'])->where('role', 'warga')->get()->getRowArray();
        return [
            'id'                 => (int) $r['id'],
            'nama'               => $kepala,
            'alamat'             => $this->labelAlamat($r),
            'blok'               => $r['blok_nama'],
            'jumlah_anggota'     => count(array_filter($anggota, static fn ($a) => $a['status'] === 'aktif')),
            'data_belum_lengkap' => $this->cekBelumLengkap($r, $anggota),
            'belum_ganti_pin'    => $user ? (bool) $user['harus_ganti_kredensial'] : false,
            'mulai_bulan_depan'  => empty($r['mulai_periode']),
            'status'             => $r['status'],
        ];
    }

    private function labelAlamat(array $r): string
    {
        return $r['blok_nama'] . '-' . $r['nomor'] . ($r['akhiran'] ?? '');
    }

    private function cekBelumLengkap(array $k, array $anggota): bool
    {
        if (empty($k['telepon'])) {
            return true;
        }
        foreach ($anggota as $a) {
            if ($a['status'] !== 'aktif') {
                continue;
            }
            if (empty($a['nik_enc']) || empty($a['tanggal_lahir'])) {
                return true;
            }
        }
        return false;
    }

    private function alamatDuplikat(int $blokId, string $nomor, string $akhiran, ?int $kecualiId = null): bool
    {
        $db = \Config\Database::connect();
        $q = $db->table('keluarga')
            ->where('blok_id', $blokId)
            ->where('nomor', $nomor)
            ->where('akhiran', $akhiran)
            ->where('status', 'aktif');
        if ($kecualiId) {
            $q->where('id !=', $kecualiId);
        }
        return $q->countAllResults() > 0;
    }

    private function nikDuplikat(string $hash, ?int $kecualiId = null): bool
    {
        $db = \Config\Database::connect();
        $q = $db->table('warga')
            ->where('nik_hash', $hash)
            ->whereIn('status', ['aktif']);
        if ($kecualiId) {
            $q->where('id !=', $kecualiId);
        }
        return $q->countAllResults() > 0;
    }

    private function buatUsername(string $blok, string $nomor, string $akhiran): string
    {
        $base = strtolower($blok . '-' . $nomor . $akhiran);
        $db = \Config\Database::connect();
        $u = $base;
        $i = 0;
        while ($db->table('users')->where('username', $u)->countAllResults() > 0) {
            $i++;
            $u = $base . 'x' . $i;
        }
        return $u;
    }

    private function nonaktifkanPengurusDariWarga(int $wargaId, int $userId): void
    {
        $db = \Config\Database::connect();
        $u = $db->table('users')->where('warga_id', $wargaId)->whereIn('role', ['pengurus', 'ketua'])->where('is_developer', 0)->get()->getRowArray();
        if ($u && (int) $u['aktif'] === 1) {
            $db->table('users')->where('id', $u['id'])->update(['aktif' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            (new AuditService())->log('nonaktif_pengurus_otomatis', 'users', (int) $u['id'], null, ['warga_id' => $wargaId], 'sistem_otomatis', true, $userId);
        }
    }
}
