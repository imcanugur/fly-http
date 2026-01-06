<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit\Http;

use Fly\Http\Http\Request;
use Fly\Http\Http\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testRequestImplementsPsr7Interface(): void
    {
        $request = new Request('GET', 'http://example.com');

        $this->assertInstanceOf(\Psr\Http\Message\RequestInterface::class, $request);
    }

    public function testRequestMethod(): void
    {
        $request = new Request('POST', 'http://example.com');

        $this->assertEquals('POST', $request->getMethod());

        $newRequest = $request->withMethod('PUT');
        $this->assertEquals('PUT', $newRequest->getMethod());
        $this->assertEquals('POST', $request->getMethod()); // Immutable
    }

    public function testRequestTarget(): void
    {
        $request = new Request('GET', 'http://example.com/path?query=value');

        $this->assertEquals('/path?query=value', $request->getRequestTarget());

        $newRequest = $request->withRequestTarget('/new-path');
        $this->assertEquals('/new-path', $newRequest->getRequestTarget());
    }

    public function testUri(): void
    {
        $uri = new \Fly\Http\Http\Uri('http://example.com/path');
        $request = new Request('GET', $uri);

        $this->assertSame($uri, $request->getUri());

        $newUri = new \Fly\Http\Http\Uri('http://example.com/new-path');
        $newRequest = $request->withUri($newUri);
        $this->assertSame($newUri, $newRequest->getUri());
    }

    public function testHeaders(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Content-Type' => 'application/json',
            'X-Custom' => ['value1', 'value2']
        ]);

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));

        $newRequest = $request->withHeader('Authorization', 'Bearer token');
        $this->assertTrue($newRequest->hasHeader('Authorization'));
        $this->assertFalse($request->hasHeader('Authorization')); // Immutable
    }

    public function testProtocolVersion(): void
    {
        $request = new Request('GET', 'http://example.com');

        $this->assertEquals('1.1', $request->getProtocolVersion());

        $newRequest = $request->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $newRequest->getProtocolVersion());
    }

    public function testBody(): void
    {
        $body = new \Fly\Http\Http\Stream('test content');
        $request = new Request('POST', 'http://example.com', [], $body);

        $this->assertSame($body, $request->getBody());

        $newBody = new \Fly\Http\Http\Stream('new content');
        $newRequest = $request->withBody($newBody);
        $this->assertSame($newBody, $newRequest->getBody());
        $this->assertSame($body, $request->getBody()); // Immutable
    }

    public function testAttributes(): void
    {
        $request = new Request('GET', 'http://example.com');

        $this->assertNull($request->getAttribute('test'));
        $this->assertEquals('default', $request->getAttribute('test', 'default'));

        $newRequest = $request->withAttribute('test', 'value');
        $this->assertEquals('value', $newRequest->getAttribute('test'));
        $this->assertNull($request->getAttribute('test')); // Immutable

        $withoutRequest = $newRequest->withoutAttribute('test');
        $this->assertNull($withoutRequest->getAttribute('test'));
    }
}
