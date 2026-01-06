<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit\Middleware;

use Fly\Http\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddlewareTest extends TestCase
{
    public function testAuthHeaderIsAdded(): void
    {
        $middleware = new AuthMiddleware('test-token', 'Bearer');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer test-token')
            ->willReturn($request);

        $next = function ($req) use ($response) {
            $this->assertEquals($request, $req);
            return $response;
        };

        $result = $middleware->process($request, $next);

        $this->assertSame($response, $result);
    }

    public function testCustomAuthType(): void
    {
        $middleware = new AuthMiddleware('test-token', 'Basic');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Basic test-token')
            ->willReturn($request);

        $next = function ($req) use ($response) {
            return $response;
        };

        $middleware->process($request, $next);
    }

    public function testDefaultAuthTypeIsBearer(): void
    {
        $middleware = new AuthMiddleware('test-token');

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->expects($this->once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer test-token')
            ->willReturn($request);

        $next = function ($req) use ($response) {
            return $response;
        };

        $middleware->process($request, $next);
    }
}
