<?php

declare(strict_types=1);

use Bullwatt\Mcp\Application;
use Bullwatt\Mcp\Http\HttpFactory;
use Bullwatt\Mcp\Http\ServerRequest;
use Bullwatt\Mcp\ServerFactory;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/src/Http/Psr7.php';

$request = ServerRequest::fromGlobals();
$factory = new HttpFactory();
$middleware = [
    new CorsMiddleware(),
    new DnsRebindingProtectionMiddleware(['localhost', '127.0.0.1', '[::1]', 'bullwatt.com'], $factory, $factory),
    new ProtocolVersionMiddleware(null, $factory, $factory),
];
$transport = new StreamableHttpTransport($request, $factory, $factory, middleware: $middleware);
$response = ServerFactory::create(new Application(), true)->run($transport);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header($name . ': ' . $value, false);
    }
}
echo $response->getBody();
