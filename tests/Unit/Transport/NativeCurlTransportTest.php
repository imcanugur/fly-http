<?php

declare(strict_types=1);

namespace Fly\Http\Tests\Unit\Transport;

use Fly\Http\Transport\NativeCurlTransport;
use PHPUnit\Framework\TestCase;

class NativeCurlTransportTest extends TestCase
{
    public function testTransportImplementsPsr18Interface(): void
    {
        $transport = new NativeCurlTransport();

        $this->assertInstanceOf(\Psr\Http\Client\ClientInterface::class, $transport);
    }

    public function testTransportAcceptsCustomOptions(): void
    {
        $customOptions = [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $transport = new NativeCurlTransport($customOptions);

        $this->assertInstanceOf(NativeCurlTransport::class, $transport);
    }

    public function testTransportHandlesCurlError(): void
    {
        $transport = new NativeCurlTransport();

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn(new class {
            public function __toString() { return 'http://invalid-domain-that-does-not-exist-12345.com'; }
        });
        $request->method('getHeaders')->willReturn([]);
        $request->method('getBody')->willReturn(new class {
            public function getContents() { return ''; }
            public function __toString() { return ''; }
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cURL error');

        $transport->sendRequest($request);
    }

    public function testTransportWithTimeoutOption(): void
    {
        $transport = new NativeCurlTransport([
            CURLOPT_TIMEOUT => 1,
        ]);

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn(new class {
            public function __toString() { return 'http://httpbin.org/delay/2'; }
        });
        $request->method('getHeaders')->willReturn([]);
        $request->method('getBody')->willReturn(new class {
            public function getContents() { return ''; }
            public function __toString() { return ''; }
        });

        // This should timeout and throw an exception
        $this->expectException(\RuntimeException::class);
        $transport->sendRequest($request);
    }
}
