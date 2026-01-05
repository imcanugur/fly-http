<?php

declare(strict_types=1);

namespace Fly\Http\Tests;

use Fly\Http\Client\Client;
use Fly\Http\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Test cases for the HTTP client.
 */
class ClientTest extends TestCase
{
    public function testClientImplementsPsr18Interface(): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $client = new Client($transport);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testMiddlewareIsAdded(): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $client = new Client($transport);

        $middleware = new AuthMiddleware('test-token');

        $result = $client->addMiddleware($middleware);

        $this->assertSame($client, $result);
    }

    public function testRequestIsSentThroughTransport(): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $transport->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $client = new Client($transport);
        $result = $client->sendRequest($request);

        $this->assertSame($response, $result);
    }
}
