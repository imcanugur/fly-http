<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit\Middleware;

use Fly\Http\Middleware\RetryMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddlewareTest extends TestCase
{
    public function testSuccessfulRequestDoesNotRetry(): void
    {
        $middleware = new RetryMiddleware(3, 1.0);

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $callCount = 0;
        $next = function ($req) use ($response, &$callCount) {
            $callCount++;
            return $response;
        };

        $result = $middleware->process($request, $next);

        $this->assertSame($response, $result);
        $this->assertEquals(1, $callCount);
    }

    public function testNetworkExceptionTriggersRetry(): void
    {
        $middleware = new RetryMiddleware(2, 0.1);

        $request = $this->createMock(RequestInterface::class);
        $networkException = $this->createMock(NetworkExceptionInterface::class);

        $callCount = 0;
        $next = function ($req) use ($networkException, &$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw $networkException;
            }
            return $this->createMock(ResponseInterface::class);
        };

        $result = $middleware->process($request, $next);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(2, $callCount);
    }

    public function testServerErrorTriggersRetry(): void
    {
        $middleware = new RetryMiddleware(2, 0.1);

        $request = $this->createMock(RequestInterface::class);
        $response500 = $this->createMock(ResponseInterface::class);
        $response500->method('getStatusCode')->willReturn(500);

        $requestException = $this->createMock(RequestExceptionInterface::class);
        $requestException->method('getResponse')->willReturn($response500);

        $callCount = 0;
        $next = function ($req) use ($requestException, &$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw $requestException;
            }
            return $this->createMock(ResponseInterface::class);
        };

        $result = $middleware->process($request, $next);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(2, $callCount);
    }

    public function testClientErrorDoesNotTriggerRetry(): void
    {
        $middleware = new RetryMiddleware(3, 0.1);

        $request = $this->createMock(RequestInterface::class);
        $response400 = $this->createMock(ResponseInterface::class);
        $response400->method('getStatusCode')->willReturn(400);

        $requestException = $this->createMock(RequestExceptionInterface::class);
        $requestException->method('getResponse')->willReturn($response400);

        $next = function ($req) use ($requestException) {
            throw $requestException;
        };

        $this->expectException(get_class($requestException));
        $middleware->process($request, $next);
    }

    public function testMaxRetriesExceeded(): void
    {
        $middleware = new RetryMiddleware(2, 0.1);

        $request = $this->createMock(RequestInterface::class);
        $networkException = $this->createMock(NetworkExceptionInterface::class);

        $callCount = 0;
        $next = function ($req) use ($networkException, &$callCount) {
            $callCount++;
            throw $networkException;
        };

        $this->expectException(get_class($networkException));

        $middleware->process($request, $next);

        $this->assertEquals(3, $callCount); // Initial + 2 retries
    }
}
