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
        $mime = (string) $file->getMimeType();
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
            return ['ok' => false, 'message' => 'Gagal menyimpan file.'];
        }
        $rel = $cfg['dir'] . '/' . $name;
        $thumbRel = null;
        if ($thumbDataUrl && $jenis === 'galeri') {
            $thumbRel = $this->simpanThumb($thumbDataUrl, $cfg['dir']);
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

    private function simpanThumb(string $dataUrl, string $subdir): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
            return null;
        }
        $raw = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($raw === false || strlen($raw) < 50 || strlen($raw) > 1_500_000) {
            return null;
        }
        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        if (!in_array($ext, ['jpg', 'png', 'webp'], true)) {
            return null;
        }
        $dir = WRITEPATH . 'uploads/' . $subdir;
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
