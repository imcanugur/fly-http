<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 HTTP Request implementation.
 */
class Request implements RequestInterface
{
    /**
     * @var string
     */
    private string $method;

    /**
     * @var UriInterface
     */
    private UriInterface $uri;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var StreamInterface
     */
    private StreamInterface $body;

    /**
     * @var string
     */
    private string $protocolVersion = '1.1';

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @param string $method
     * @param mixed $uri
     * @param array $headers
     * @param mixed $body
     * @param string $protocolVersion
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri((string) $uri);
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body ?? '');
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Normalize headers to lowercase keys.
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_array($value) ? $value : [$value];
        }
        return $normalized;
    }

    public function getRequestTarget(): string
    {
        if ($this->uri->getPath() === '') {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('/\?/', $requestTarget)) {
            [$path, $query] = explode('?', $requestTarget, 2);
        } else {
            $path = $requestTarget;
            $query = '';
        }

        $newUri = $this->uri->withPath($path)->withQuery($query);
        $clone = clone $this;
        $clone->uri = $newUri;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost && $uri->getHost() !== '') {
            $clone->headers['host'] = [$uri->getHost()];
        }

        return $clone;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return array_map(function ($values) {
            return $values[0] ?? '';
        }, $this->headers);
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $clone;
    }

    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $key = strtolower($name);
        $existing = $clone->headers[$key] ?? [];
        $clone->headers[$key] = array_merge($existing, is_array($value) ? $value : [$value]);
        return $clone;
    }

    public function withoutHeader($name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Get an attribute.
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Get all attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set an attribute.
     */
    public function withAttribute($name, $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * Remove an attribute.
     */
    public function withoutAttribute($name): self
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}
