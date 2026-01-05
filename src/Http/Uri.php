<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\UriInterface;

/**
 * PSR-7 URI implementation.
 */
class Uri implements UriInterface
{
    /**
     * @var string
     */
    private string $scheme = '';

    /**
     * @var string
     */
    private string $userInfo = '';

    /**
     * @var string
     */
    private string $host = '';

    /**
     * @var int|null
     */
    private ?int $port = null;

    /**
     * @var string
     */
    private string $path = '';

    /**
     * @var string
     */
    private string $query = '';

    /**
     * @var string
     */
    private string $fragment = '';

    /**
     * @param string $uri
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $this->parseUri($uri);
        }
    }

    /**
     * Parse URI string.
     */
    private function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid URI: ' . $uri);
        }

        $this->scheme = $parts['scheme'] ?? '';
        $this->host = $parts['host'] ?? '';
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? '';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';

        if (isset($parts['user'])) {
            $this->userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo !== '') {
            $authority .= $this->userInfo . '@';
        }

        $authority .= $this->host;

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): self
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        return $clone;
    }

    public function withUserInfo($user, $password = null): self
    {
        $clone = clone $this;
        $clone->userInfo = $user;
        if ($password !== null) {
            $clone->userInfo .= ':' . $password;
        }
        return $clone;
    }

    public function withHost($host): self
    {
        $clone = clone $this;
        $clone->host = strtolower($host);
        return $clone;
    }

    public function withPort($port): self
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    public function withPath($path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    public function withQuery($query): self
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function withFragment($fragment): self
    {
        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
