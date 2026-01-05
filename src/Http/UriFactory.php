<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-17 HTTP URI Factory implementation.
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * Create a new URI.
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
