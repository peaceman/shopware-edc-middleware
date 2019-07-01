<?php
/**
 * lel since 2019-07-01
 */

namespace Tests\Unit\Utils;

use App\Utils\GuessDownloadFilenames;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GuessDownloadFilenamesTest extends TestCase
{
    public function testMissingContentDispositionHeader()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/lel.txt', new Response(200)))();
        static::assertEquals('lel.txt', $filename);
    }

    public function testContentDispositionHeaderWithoutFilename()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/lel.txt', new Response(200, [
            'content-disposition' => 'form-data; name="field1"',
        ])))();

        static::assertEquals('lel.txt', $filename);
    }

    public function testRegularContentDispositionHeader()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/lel.txt', new Response(200, [
            'content-disposition' => 'inline; filename=cool.html',
        ])))();

        static::assertEquals('cool.html', $filename);
    }

    public function testQuotedFilenameInContentDispositionHeader()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/lel.txt', new Response(200, [
            'content-disposition' => 'attachment; filename="i fckn luv spaces.png"',
        ])))();

        static::assertEquals('i fckn luv spaces.png', $filename);
    }

    public function testMissingExtensionWithFallbackContentType()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/los-crappos/muchos', new Response(200, [
            'content-type' => 'image/tiff',
        ])))();

        static::assertEquals('muchos.tiff', $filename);
    }

    public function testMissingExtensionWithoutAlternative()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/los-crappos/muchos', new Response(200)))();

        static::assertEquals('muchos', $filename);
    }

    public function testMissingExtensionWithUnknownMimeType()
    {
        $filename = (new GuessDownloadFileNames('https://foo.bar/los-crappos/muchos', new Response(200, [
            'content-type' => 'magix/x-bowl-master'
        ])))();

        static::assertEquals('muchos', $filename);
    }

    public function testQueryParamsAreIgnored()
    {
        $uri = 'https://foo.bar/los-crappos/lel.txt?on=off&foo=bar&crap=lord#magicians';
        $filename = (new GuessDownloadFileNames($uri, new Response(200)))();
        static::assertEquals('lel.txt', $filename);
    }

    public function testMissingExtensionWithQueryParams()
    {
        $uri = 'https://foo.bar/los-crappos/lel?on=off&foo=bar&crap=lord#magicians';
        $filename = (new GuessDownloadFileNames($uri, new Response(200, [
            'content-type' => 'image/png',
        ])))();

        static::assertEquals('lel.png', $filename);
    }

    public function testUnknownExtensionWithMimeType()
    {
        $uri = 'http://foo.bar/lel/magician.crap';
        $filename = (new GuessDownloadFileNames($uri, new Response(200, [
            'content-type' => 'image/jpeg',
        ])))();

        static::assertEquals('magician.crap.jpeg', $filename);
    }

    public function testUnknownExtensionWithoutMimeType()
    {
        $uri = 'http://foo.bar/lel/magician.crap';
        $filename = (new GuessDownloadFilenames($uri, new Response(200)))();

        static::assertEquals('magician.crap', $filename);
    }

    public function testUnknownExtensionWithUnknownMimeType()
    {
        $uri = 'http://foo.bar/lel/magician.bowl';
        $filename = (new GuessDownloadFilenames($uri, new Response(200, [
            'content-type' => 'magix/x-bowl-master',
        ])))();

        static::assertEquals('magician.bowl', $filename);
    }
}
