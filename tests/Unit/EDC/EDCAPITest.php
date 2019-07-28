<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\EDC;

use App\EDC\EDCAPI;
use App\EDCExportStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use function App\Utils\fixture_content;

class EDCAPITest extends TestCase
{
    public function testRegular()
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], fixture_content('edc-order-export-response-success.json')),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'handler' => $stack,
        ]);

        $edcAPI = $this->createEDCAPI(['httpClient' => $client]);
        $result = $edcAPI->exportOrder('foo the bar');

        static::assertEquals(EDCExportStatus::OK, $result->getStatus());
        static::assertEquals('DR19072814488521', $result->getOrderNumber());

        static::assertCount(1, $container);
        /** @var Request $request */
        $request = $container[0]['request'];

        static::assertEquals(config('edc.orderExportURI'), (string)$request->getUri());
        static::assertEquals('POST', $request->getMethod());
        static::assertInstanceOf(MultipartStream::class, $request->getBody());
        static::assertContains(
            'Content-Disposition: form-data; name="data"',
            (string)$request->getBody()
        );
    }

    public function testFailureWithJSON()
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], fixture_content('edc-order-export-response-failure.json')),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'handler' => $stack,
        ]);

        $edcAPI = $this->createEDCAPI(['httpClient' => $client]);
        $result = $edcAPI->exportOrder('foo the bar');

        static::assertEquals(EDCExportStatus::FAIL, $result->getStatus());
        static::assertEquals('Please enter an address', $result->getMessage());
        static::assertEquals('3', $result->getErrorCode());

        static::assertCount(1, $container);
        /** @var Request $request */
        $request = $container[0]['request'];

        static::assertEquals(config('edc.orderExportURI'), (string)$request->getUri());
        static::assertEquals('POST', $request->getMethod());
        static::assertInstanceOf(MultipartStream::class, $request->getBody());
        static::assertContains(
            'Content-Disposition: form-data; name="data"',
            (string)$request->getBody()
        );
    }

    public function testFailureWithoutJSON()
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '1 - Username and password do not match'),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'handler' => $stack,
        ]);

        $edcAPI = $this->createEDCAPI(['httpClient' => $client]);
        try {
            $result = $edcAPI->exportOrder('foo the bar');

            static::fail('The expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            static::addToAssertionCount(1);
        }
    }

    protected function createEDCAPI(array $params = []): EDCAPI
    {
        return $this->app->make(EDCAPI::class, $params);
    }
}
