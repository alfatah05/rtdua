<?php

namespace App\Services;

class UploadService
{
    /** @var array<string, array{dir:string,max:int,public:bool,mimes:list<string>}> */
    private array $jenis = [
        'bukti'       => ['dir' => 'bukti', 'max' => 3_000_000, 'public' => false, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'ronda'       => ['dir' => 'ronda', 'max' => 3_000_000, 'public' => false, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'galeri'      => ['dir' => 'galeri', 'max' => 4_000_000, 'public' => true, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'banner'      => ['dir' => 'banner', 'max' => 2_000_000, 'public' => true, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'foto_profil' => ['dir' => 'profil', 'max' => 1_500_000, 'public' => true, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'logo'        => ['dir' => 'logo', 'max' => 1_500_000, 'public' => true, 'mimes' => ['image/jpeg', 'image/png', 'image/webp']],
        'lampiran'    => ['dir' => 'lampiran', 'max' => 5_000_000, 'public' => true, 'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']],
    ];

    public function simpan(?object $file, string $jenis, ?string $thumbDataUrl = null): array
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return ['ok' => false, 'message' => 'File tidak valid.'];
        }
        if (!isset($this->jenis[$jenis])) {
            return ['ok' => false, 'message' => 'Jenis upload tidak dikenal.'];
        }
        $cfg = $this->jenis[$jenis];
        if ($file->getSize() > $cfg['max']) {
            return ['ok' => false, 'message' => 'File terlalu besar.'];
        }

        // Deteksi MIME dari isi file (bukan hanya header klien)
        $tmp = $file->getTempName();
        $mime = $this->detectMime($tmp, (string) $file->getMimeType());
        if (!in_array($mime, $cfg['mimes'], true)) {
            return ['ok' => false, 'message' => $jenis === 'lampiran' ? 'Hanya PDF/JPG/PNG/WebP.' : 'Hanya JPG/PNG/WebP.'];
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'jpg',
        };
        $dir = WRITEPATH . 'uploads/' . $cfg['dir'];
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        try {
            $file->move($dir, $name, true);
        } catch (\Throwable $e) {
            log_message('error', 'UploadService::simpan move: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Gagal menyimpan file.'];
        }

        $rel = $cfg['dir'] . '/' . $name;
        $thumbRel = null;
        if ($thumbDataUrl && $jenis === 'galeri') {
            $thumbRel = $this->simpanThumbDataUrl($thumbDataUrl, $cfg['dir']);
        }

        return [
            'ok' => true,
            'data' => [
                'path'   => $rel,
                'thumb'  => $thumbRel,
                'url'    => '/api/media/' . $rel,
                'mime'   => $mime,
                'public' => $cfg['public'],
            ],
        ];
    }

    /**
     * MIME nyata dari isi file (finfo), fallback getimagesize / magic PDF.
     */
    private function detectMime(string $tmpPath, string $clientMime = ''): string
    {
        $mime = '';
        if (is_file($tmpPath) && is_readable($tmpPath)) {
            if (function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                if ($f) {
                    $detected = finfo_file($f, $tmpPath);
                    finfo_close($f);
                    if (is_string($detected) && $detected !== '') {
                        $mime = strtolower($detected);
                    }
                }
            }
            if ($mime === '' || $mime === 'application/octet-stream') {
                $img = @getimagesize($tmpPath);
                if (is_array($img) && !empty($img['mime'])) {
                    $mime = strtolower((string) $img['mime']);
                }
            }
            if (($mime === '' || $mime === 'application/octet-stream') && is_readable($tmpPath)) {
                $fh = fopen($tmpPath, 'rb');
                if ($fh) {
                    $head = fread($fh, 5);
                    fclose($fh);
                    if (is_string($head) && str_starts_with($head, '%PDF')) {
                        $mime = 'application/pdf';
                    }
                }
            }
        }

        // Normalisasi alias
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        if ($mime !== '' && $mime !== 'application/octet-stream') {
            return $mime;
        }

        // Fallback terakhir: client mime (sudah dinormalisasi) — tetap dibatasi whitelist pemanggil
        $client = strtolower(trim($clientMime));
        if ($client === 'image/jpg') {
            $client = 'image/jpeg';
        }
        return $client;
    }

    private function simpanThumbDataUrl(string $dataUrl, string $subdir): ?string
    {
        if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $dataUrl, $m)) {
            return null;
        }
        $raw = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($raw === false || strlen($raw) < 50 || strlen($raw) > 1_500_000) {
            return null;
        }
        $ext = strtolower($m[1]) === 'jpeg' || strtolower($m[1]) === 'jpg' ? 'jpg' : strtolower($m[1]);
        if (!in_array($ext, ['jpg', 'png', 'webp'], true)) {
            return null;
        }
        $dir = WRITEPATH . 'uploads/' . $subdir;
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $name = 't_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (file_put_contents($dir . '/' . $name, $raw) === false) {
            return null;
        }
        return $subdir . '/' . $name;
    }

    public function pathFisik(string $rel): ?string
    {
        $rel = str_replace(['..', '\\'], '', $rel);
        $rel = ltrim($rel, '/');
        if ($rel === '' || !preg_match('#^[a-z]+/[a-zA-Z0-9._-]+$#', $rel)) {
            return null;
        }
        $full = WRITEPATH . 'uploads/' . $rel;
        return is_file($full) ? $full : null;
    }

    public function isPublicJenis(string $rel): bool
    {
        $jenis = explode('/', $rel)[0] ?? '';
        $map = [
            'bukti' => false,
            'ronda' => false,
            'galeri' => true,
            'banner' => true,
            'profil' => true,
            'logo' => true,
            'lampiran' => true,
        ];
        return !empty($map[$jenis]);
    }
}
