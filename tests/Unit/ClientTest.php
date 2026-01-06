<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit;

use Fly\Http\Client\Client;
use Fly\Http\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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

    public function testMiddlewareIsExecutedInOrder(): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $transport->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $middleware1 = new class {
            public $executed = false;
            public function process(RequestInterface $request, callable $next): ResponseInterface {
                $this->executed = true;
                return $next($request);
            }
        };

        $middleware2 = new class {
            public $executed = false;
            public function process(RequestInterface $request, callable $next): ResponseInterface {
                $this->executed = true;
                return $next($request);
            }
        };

        $client = new Client($transport);
        $client->addMiddleware($middleware1);
        $client->addMiddleware($middleware2);

        $client->sendRequest($request);

        $this->assertTrue($middleware1->executed);
        $this->assertTrue($middleware2->executed);
    }
}
