<?php

declare(strict_types=1);

namespace Bullwatt\Mcp\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Stream implements StreamInterface
{
    /** @param resource $resource */
    public function __construct(private $resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('A stream resource is required.');
        }
    }

    public static function fromString(string $content): self
    {
        $resource = fopen('php://temp', 'r+');
        if ($resource === false) {
            throw new \RuntimeException('Unable to create a temporary stream.');
        }
        fwrite($resource, $content);
        rewind($resource);
        return new self($resource);
    }

    public function __toString(): string
    {
        try {
            if (!is_resource($this->resource)) {
                return '';
            }
            $position = ftell($this->resource);
            rewind($this->resource);
            $contents = stream_get_contents($this->resource);
            if ($position !== false) {
                fseek($this->resource, $position);
            }
            return $contents === false ? '' : $contents;
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->resource = null;
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        $stats = fstat($this->resource);
        return $stats === false ? null : $stats['size'];
    }

    public function tell(): int
    {
        $position = is_resource($this->resource) ? ftell($this->resource) : false;
        if ($position === false) {
            throw new \RuntimeException('Unable to determine stream position.');
        }
        return $position;
    }

    public function eof(): bool { return !is_resource($this->resource) || feof($this->resource); }
    public function isSeekable(): bool { return (bool) ($this->getMetadata('seekable') ?? false); }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!is_resource($this->resource) || fseek($this->resource, $offset, $whence) !== 0) {
            throw new \RuntimeException('Unable to seek stream.');
        }
    }

    public function rewind(): void { $this->seek(0); }
    public function isWritable(): bool { return is_resource($this->resource) && strpbrk((string) $this->getMetadata('mode'), 'waxc+') !== false; }

    public function write(string $string): int
    {
        $written = is_resource($this->resource) ? fwrite($this->resource, $string) : false;
        if ($written === false) {
            throw new \RuntimeException('Unable to write stream.');
        }
        return $written;
    }

    public function isReadable(): bool { return is_resource($this->resource) && strpbrk((string) $this->getMetadata('mode'), 'r+') !== false; }

    public function read(int $length): string
    {
        $contents = is_resource($this->resource) ? fread($this->resource, $length) : false;
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream.');
        }
        return $contents;
    }

    public function getContents(): string
    {
        $contents = is_resource($this->resource) ? stream_get_contents($this->resource) : false;
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream.');
        }
        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if (!is_resource($this->resource)) {
            return $key === null ? [] : null;
        }
        $metadata = stream_get_meta_data($this->resource);
        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }
}

abstract class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';
    /** @var array<string, list<string>> */
    protected array $headers = [];
    protected StreamInterface $body;

    /** @param array<string, string|list<string>> $headers */
    public function __construct(array $headers = [], ?StreamInterface $body = null)
    {
        $this->body = $body ?? Stream::fromString('');
        foreach ($headers as $name => $value) {
            $this->headers[$name] = self::headerValues($value);
        }
    }

    public function getProtocolVersion(): string { return $this->protocolVersion; }
    public function withProtocolVersion(string $version): MessageInterface { $clone = clone $this; $clone->protocolVersion = $version; return $clone; }
    public function getHeaders(): array { return $this->headers; }
    public function hasHeader(string $name): bool { return $this->headerName($name) !== null; }
    public function getHeader(string $name): array { $found = $this->headerName($name); return $found === null ? [] : $this->headers[$found]; }
    public function getHeaderLine(string $name): string { return implode(', ', $this->getHeader($name)); }

    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $found = $clone->headerName($name);
        if ($found !== null) {
            unset($clone->headers[$found]);
        }
        $clone->headers[$name] = self::headerValues($value);
        return $clone;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $found = $clone->headerName($name) ?? $name;
        $clone->headers[$found] = array_merge($clone->headers[$found] ?? [], self::headerValues($value));
        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        $found = $clone->headerName($name);
        if ($found !== null) {
            unset($clone->headers[$found]);
        }
        return $clone;
    }

    public function getBody(): StreamInterface { return $this->body; }
    public function withBody(StreamInterface $body): MessageInterface { $clone = clone $this; $clone->body = $body; return $clone; }

    private function headerName(string $name): ?string
    {
        foreach (array_keys($this->headers) as $candidate) {
            if (strcasecmp($candidate, $name) === 0) {
                return $candidate;
            }
        }
        return null;
    }

    /** @return list<string> */
    private static function headerValues(mixed $value): array
    {
        return array_map(static fn (mixed $item): string => (string) $item, is_array($value) ? array_values($value) : [$value]);
    }
}

final class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid URI.');
        }
        $this->scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $this->host = strtolower((string) ($parts['host'] ?? ''));
        $this->port = isset($parts['port']) ? (int) $parts['port'] : null;
        $this->path = (string) ($parts['path'] ?? '');
        $this->query = (string) ($parts['query'] ?? '');
        $this->fragment = (string) ($parts['fragment'] ?? '');
        $this->userInfo = (string) ($parts['user'] ?? '');
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    public function getScheme(): string { return $this->scheme; }
    public function getAuthority(): string { return ($this->userInfo === '' ? '' : $this->userInfo . '@') . $this->host . ($this->port === null ? '' : ':' . $this->port); }
    public function getUserInfo(): string { return $this->userInfo; }
    public function getHost(): string { return $this->host; }
    public function getPort(): ?int { return $this->port; }
    public function getPath(): string { return $this->path; }
    public function getQuery(): string { return $this->query; }
    public function getFragment(): string { return $this->fragment; }
    public function withScheme(string $scheme): UriInterface { $clone = clone $this; $clone->scheme = strtolower($scheme); return $clone; }
    public function withUserInfo(string $user, ?string $password = null): UriInterface { $clone = clone $this; $clone->userInfo = $user . ($password === null ? '' : ':' . $password); return $clone; }
    public function withHost(string $host): UriInterface { $clone = clone $this; $clone->host = strtolower($host); return $clone; }
    public function withPort(?int $port): UriInterface { if ($port !== null && ($port < 1 || $port > 65535)) throw new \InvalidArgumentException('Invalid port.'); $clone = clone $this; $clone->port = $port; return $clone; }
    public function withPath(string $path): UriInterface { $clone = clone $this; $clone->path = $path; return $clone; }
    public function withQuery(string $query): UriInterface { $clone = clone $this; $clone->query = ltrim($query, '?'); return $clone; }
    public function withFragment(string $fragment): UriInterface { $clone = clone $this; $clone->fragment = ltrim($fragment, '#'); return $clone; }

    public function __toString(): string
    {
        $authority = $this->getAuthority();
        return ($this->scheme === '' ? '' : $this->scheme . ':')
            . ($authority === '' ? '' : '//' . $authority)
            . $this->path
            . ($this->query === '' ? '' : '?' . $this->query)
            . ($this->fragment === '' ? '' : '#' . $this->fragment);
    }
}

final class ServerRequest extends Message implements ServerRequestInterface
{
    private string $requestTarget = '';
    private array $cookieParams = [];
    private array $queryParams = [];
    private array $uploadedFiles = [];
    private mixed $parsedBody = null;
    private array $attributes = [];

    public function __construct(
        private string $method,
        private UriInterface $uri,
        private readonly array $serverParams = [],
        array $headers = [],
        ?StreamInterface $body = null,
    ) {
        parent::__construct($headers, $body);
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $target = (string) ($_SERVER['REQUEST_URI'] ?? '/mcp/index.php');
        $input = fopen('php://input', 'r');
        $request = new self((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), new Uri($scheme . '://' . $host . $target), $_SERVER, $headers, new Stream($input));
        $request->cookieParams = $_COOKIE;
        $request->queryParams = $_GET;
        return $request;
    }

    public function getRequestTarget(): string { return $this->requestTarget !== '' ? $this->requestTarget : (($this->uri->getPath() ?: '/') . ($this->uri->getQuery() === '' ? '' : '?' . $this->uri->getQuery())); }
    public function withRequestTarget(string $requestTarget): RequestInterface { $clone = clone $this; $clone->requestTarget = $requestTarget; return $clone; }
    public function getMethod(): string { return $this->method; }
    public function withMethod(string $method): RequestInterface { $clone = clone $this; $clone->method = $method; return $clone; }
    public function getUri(): UriInterface { return $this->uri; }
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface { $clone = clone $this; $clone->uri = $uri; return $clone; }
    public function getServerParams(): array { return $this->serverParams; }
    public function getCookieParams(): array { return $this->cookieParams; }
    public function withCookieParams(array $cookies): ServerRequestInterface { $clone = clone $this; $clone->cookieParams = $cookies; return $clone; }
    public function getQueryParams(): array { return $this->queryParams; }
    public function withQueryParams(array $query): ServerRequestInterface { $clone = clone $this; $clone->queryParams = $query; return $clone; }
    public function getUploadedFiles(): array { return $this->uploadedFiles; }
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface { $clone = clone $this; $clone->uploadedFiles = $uploadedFiles; return $clone; }
    public function getParsedBody() { return $this->parsedBody; }
    public function withParsedBody($data): ServerRequestInterface { $clone = clone $this; $clone->parsedBody = $data; return $clone; }
    public function getAttributes(): array { return $this->attributes; }
    public function getAttribute(string $name, $default = null) { return $this->attributes[$name] ?? $default; }
    public function withAttribute(string $name, $value): ServerRequestInterface { $clone = clone $this; $clone->attributes[$name] = $value; return $clone; }
    public function withoutAttribute(string $name): ServerRequestInterface { $clone = clone $this; unset($clone->attributes[$name]); return $clone; }
}

final class Response extends Message implements ResponseInterface
{
    public function __construct(private int $statusCode = 200, private string $reasonPhrase = '', array $headers = [], ?StreamInterface $body = null)
    {
        parent::__construct($headers, $body);
    }

    public function getStatusCode(): int { return $this->statusCode; }
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface { $clone = clone $this; $clone->statusCode = $code; $clone->reasonPhrase = $reasonPhrase; return $clone; }
    public function getReasonPhrase(): string { return $this->reasonPhrase; }
}

final class HttpFactory implements ResponseFactoryInterface, StreamFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface { return new Response($code, $reasonPhrase); }
    public function createStream(string $content = ''): StreamInterface { return Stream::fromString($content); }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);
        if ($resource === false) {
            throw new \RuntimeException("Unable to open stream file '{$filename}'.");
        }
        return new Stream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface { return new Stream($resource); }
}
