<?php

namespace Config;

/**
 * Path aplikasi. system HARUS mengarah ke vendor (composer),
 * bukan folder /system (tidak ada di layout composer).
 */
class Paths
{
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    public string $appDirectory = __DIR__ . '/..';

    public string $writableDirectory = __DIR__ . '/../../writable';

    public string $testsDirectory = __DIR__ . '/../../tests';

    public string $viewDirectory = __DIR__ . '/../Views';
}
