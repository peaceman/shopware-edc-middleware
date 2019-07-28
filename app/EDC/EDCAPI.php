<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class EDCAPI
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Client */
    protected $httpClient;

    /** @var string */
    protected $orderExportURI;

    public function __construct(LoggerInterface $logger, Client $httpClient)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
    }

    public function setOrderExportURI(string $uri): void
    {
        $this->orderExportURI = $uri;
    }

    public function exportOrder(string $orderXML): EDCOrderExportInfo
    {
        try {
            $this->logger->info('EDCAPI: exporting order', [
                'uri' => $this->orderExportURI,
            ]);

            $response = $this->httpClient->post($this->orderExportURI, [
                'multipart' => [
                    [
                        'name' => 'data',
                        'contents' => $orderXML,
                    ]
                ]
            ]);

            $responseData = \GuzzleHttp\json_decode((string)$response->getBody(), true);

            return new EDCOrderExportInfo($responseData);
        } catch (\Exception $e) {
            $this->logger->error('EDCAPI: failed to export order');
            $this->logger->error('EDCAPI: orderxml' . PHP_EOL . $orderXML);
            $this->logger->error('EDCAPI: response' . PHP_EOL . (string)$response->getBody());

            throw $e;
        }
    }
}
