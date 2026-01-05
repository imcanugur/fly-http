<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for setting request and connection timeouts.
 */
class TimeoutMiddleware implements MiddlewareInterface
{
    /**
     * @var float
     */
    private float $requestTimeout;

    /**
     * @var float
     */
    private float $connectTimeout;

    /**
     * @param float $requestTimeout
     * @param float $connectTimeout
     */
    public function __construct(float $requestTimeout = 30.0, float $connectTimeout = 10.0)
    {
        $this->requestTimeout = $requestTimeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Add timeout options to request.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        // Add timeout to request attributes for transport to use
        $request = $request->withAttribute('timeout', $this->requestTimeout);
        $request = $request->withAttribute('connect_timeout', $this->connectTimeout);

        return $next($request);
    }
}
