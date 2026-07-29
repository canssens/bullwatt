<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Bullwatt\\Mcp\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});
