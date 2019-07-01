<?php
/**
 * lel since 2019-07-01
 */

namespace App\Utils;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser;

class GuessDownloadFilenames
{
    /** @var string */
    protected $uri;

    /** @var ResponseInterface */
    protected $response;

    public function __construct(string $uri, ResponseInterface $response)
    {
        $this->uri = $uri;
        $this->response = $response;
    }

    public function __invoke(): string
    {
        $filename = $this->determineFileNameFromContentDispositionHeader();
        $filename = !empty($filename) ? $filename : basename($this->getURIPath());

        $filenameInfo = new SplFileInfo($filename);
        $extension = $filenameInfo->getExtension();

        if ((empty($extension) || $this->isUnknownExtension($extension))
            && $extension = $this->guessExtensionFromContentTypeHeader())
        {
            $filename .= ".$extension";
        }

        return $filename;
    }

    protected function getURIPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH);
    }

    protected function determineFileNameFromContentDispositionHeader(): ?string
    {
        $header = Arr::last($this->response->getHeader('content-disposition'), function ($header) {
            return Str::contains($header, 'filename');
        });

        $filenameDirective = Arr::last(explode('; ', $header), function ($directive) {
            return Str::contains($directive, 'filename=');
        });

        if (!$filenameDirective) return null;

        list(, $filename) = array_map(function ($v) { return trim($v); }, explode('=', $filenameDirective));

        return $filename
            ? trim($filename, '"')
            : null;
    }

    /**
     * @return null|string
     */
    protected function guessExtensionFromContentTypeHeader(): ?string
    {
        $contentTypeHeader = Arr::last($this->response->getHeader('content-type'));
        if (!$contentTypeHeader) return null;

        return (new MimeTypeExtensionGuesser())->guess($contentTypeHeader);
    }

    protected function isUnknownExtension(string $extension): bool
    {
        $extensionGuesser = new class extends MimeTypeExtensionGuesser {
            public function isKnownExtension(string $extension): bool
            {
                $fileExtensions = array_flip($this->defaultExtensions);

                return isset($fileExtensions[$extension]);
            }
        };

        return !$extensionGuesser->isKnownExtension($extension);
    }
}
