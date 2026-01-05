<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for adding authentication to HTTP requests.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    private string $token;

    /**
     * @var string
     */
    private string $type;

    /**
     * @param string $token
     * @param string $type
     */
    public function __construct(string $token, string $type = 'Bearer')
    {
        $this->token = $token;
        $this->type = $type;
    }

    /**
     * Add authentication header to request.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $request = $request->withHeader('Authorization', $this->type . ' ' . $this->token);

        return $next($request);
    }
}
