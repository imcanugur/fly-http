<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit\Http;

use Fly\Http\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseImplementsPsr7Interface(): void
    {
        $response = new Response(200);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }

    public function testStatusCode(): void
    {
        $response = new Response(404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());

        $newResponse = $response->withStatus(500, 'Internal Error');
        $this->assertEquals(500, $newResponse->getStatusCode());
        $this->assertEquals('Internal Error', $newResponse->getReasonPhrase());
        $this->assertEquals(404, $response->getStatusCode()); // Immutable
    }

    public function testDefaultStatusCode(): void
    {
        $response = new Response();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function testProtocolVersion(): void
    {
        $response = new Response(200);

        $this->assertEquals('1.1', $response->getProtocolVersion());

        $newResponse = $response->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $newResponse->getProtocolVersion());
    }

    public function testHeaders(): void
    {
        $response = new Response(200, [
            'Content-Type' => 'application/json',
            'X-Custom' => ['value1', 'value2']
        ]);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));

        $newResponse = $response->withHeader('Cache-Control', 'no-cache');
        $this->assertTrue($newResponse->hasHeader('Cache-Control'));
        $this->assertFalse($response->hasHeader('Cache-Control')); // Immutable

        $addedResponse = $newResponse->withAddedHeader('X-Custom', 'value3');
        $this->assertEquals(['value1', 'value2', 'value3'], $addedResponse->getHeader('X-Custom'));
    }

    public function testWithoutHeader(): void
    {
        $response = new Response(200, ['X-Test' => 'value']);

        $this->assertTrue($response->hasHeader('X-Test'));

        $newResponse = $response->withoutHeader('X-Test');
        $this->assertFalse($newResponse->hasHeader('X-Test'));
        $this->assertTrue($response->hasHeader('X-Test')); // Immutable
    }

    public function testBody(): void
    {
        $body = new \Fly\Http\Http\Stream('response content');
        $response = new Response(200, [], $body);

        $this->assertSame($body, $response->getBody());

        $newBody = new \Fly\Http\Http\Stream('new content');
        $newResponse = $response->withBody($newBody);
        $this->assertSame($newBody, $newResponse->getBody());
        $this->assertSame($body, $response->getBody()); // Immutable
    }

    public function testReasonPhraseForUnknownStatus(): void
    {
        $response = new Response(999);

        $this->assertEquals(999, $response->getStatusCode());
        $this->assertEquals('', $response->getReasonPhrase());
    }

    public function testStatusReasonPhraseOverride(): void
    {
        $response = new Response(200, [], null, '1.1', 'Custom OK');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Custom OK', $response->getReasonPhrase());
    }
}
