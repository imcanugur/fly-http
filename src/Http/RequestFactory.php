<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-17 HTTP Request Factory implementation.
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * Create a new request.
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
