<?php

declare(strict_types=1);

namespace Fly\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 compliant HTTP client with middleware pipeline support.
 */
class Client implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $transport;

    /**
     * @var array<MiddlewareInterface>
     */
    private array $middlewares = [];

    /**
     * @param ClientInterface $transport
     */
    public function __construct(ClientInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Add middleware to the pipeline.
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Send HTTP request through middleware pipeline.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $handler = $this->transport;

        // Build middleware pipeline
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = function (RequestInterface $req) use ($middleware, $handler) {
                return $middleware->process($req, $handler);
            };
        }

        return $handler($request);
    }
}
