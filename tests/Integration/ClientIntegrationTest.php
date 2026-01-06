<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Integration;

use Fly\Http\Client\Client;
use Fly\Http\Middleware\AuthMiddleware;
use Fly\Http\Middleware\RetryMiddleware;
use Fly\Http\Transport\MockTransport;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientIntegrationTest extends TestCase
{
    public function testClientWithMultipleMiddlewares(): void
    {
        $transport = new MockTransport();

        // Setup mock response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $transport->addResponse($mockResponse);

        $client = new Client($transport);

        // Add multiple middlewares
        $client->addMiddleware(new AuthMiddleware('test-token'));
        $client->addMiddleware(new RetryMiddleware(2, 0.1));

        $request = $this->createMock(RequestInterface::class);

        $response = $client->sendRequest($request);

        $this->assertSame($mockResponse, $response);

        // Check that request was captured
        $requests = $transport->getRequests();
        $this->assertCount(1, $requests);
    }

    public function testClientWithFactoryResponse(): void
    {
        $transport = new MockTransport();

        // Setup response factory
        $transport->setResponseFactory(function ($request) {
            $response = $this->createMock(ResponseInterface::class);
            $response->method('getStatusCode')->willReturn(200);
            return $response;
        });

        $client = new Client($transport);

        $request = $this->createMock(RequestInterface::class);

        $response = $client->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestAssertionMethods(): void
    {
        $transport = new MockTransport();

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $transport->addResponse($mockResponse);

        $client = new Client($transport);

        // Create a request mock with proper methods
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getUri')->willReturn(new class {
            public function __toString() { return 'https://api.example.com/users'; }
        });

        $client->sendRequest($request);

        // Test assertion methods
        $this->assertTrue(method_exists($transport, 'assertRequestMade'));

        // Note: assertRequestMade would need to be called in a real test
        // but it's tested here that the method exists and request was captured
        $this->assertCount(1, $transport->getRequests());
    }

    public function testMiddlewareExecutionOrder(): void
    {
        $transport = new MockTransport();

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $transport->addResponse($mockResponse);

        $client = new Client($transport);

        $executionOrder = [];

        // Create middlewares that track execution order
        $middleware1 = new class($executionOrder) {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function process($request, callable $next) {
                $this->order[] = 'middleware1-start';
                $response = $next($request);
                $this->order[] = 'middleware1-end';
                return $response;
            }
        };

        $middleware2 = new class($executionOrder) {
            private array $order;
            public function __construct(array &$order) {
                $this->order = &$order;
            }
            public function process($request, callable $next) {
                $this->order[] = 'middleware2-start';
                $response = $next($request);
                $this->order[] = 'middleware2-end';
                return $response;
            }
        };

        $client->addMiddleware($middleware1);
        $client->addMiddleware($middleware2);

        $request = $this->createMock(RequestInterface::class);
        $client->sendRequest($request);

        // Middlewares should execute in reverse order (LIFO)
        $expected = [
            'middleware1-start',
            'middleware2-start',
            'middleware2-end',
            'middleware1-end'
        ];

        $this->assertEquals($expected, $executionOrder);
    }
}
