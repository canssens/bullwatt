#!/usr/bin/env php
<?php

declare(strict_types=1);

use Bullwatt\Mcp\Application;
use Bullwatt\Mcp\ServerFactory;
use Mcp\Server\Transport\StdioTransport;

require __DIR__ . '/bootstrap.php';

$server = ServerFactory::create(new Application());
$server->run(new StdioTransport());
