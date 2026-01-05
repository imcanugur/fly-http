<?php

declare(strict_types=1);

namespace Fly\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware interface for HTTP client pipeline.
 */
interface MiddlewareInterface
{
    /**
     * Process the request through middleware.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}
