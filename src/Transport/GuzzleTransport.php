<?php

declare(strict_types=1);

namespace Fly\Http\Transport;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle HTTP transport adapter.
 */
class GuzzleTransport implements ClientInterface
{
    /**
     * @var \GuzzleHttp\Client
     */
    private \GuzzleHttp\Client $client;

    /**
     * @param \GuzzleHttp\Client|null $client
     */
    public function __construct(?\GuzzleHttp\Client $client = null)
    {
        $this->client = $client ?? new \GuzzleHttp\Client();
    }

    /**
     * Send HTTP request using Guzzle.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->send($request);
    }
}
