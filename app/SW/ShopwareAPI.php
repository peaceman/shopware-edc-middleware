<?php
/**
 * lel since 20.08.18
 */

namespace App\SW;

use App\Domain\ShopwareArticleInfo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ShopwareAPI
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * ShopwareAPI constructor.
     * @param LoggerInterface $logger
     * @param Client $httpClient
     */
    public function __construct(LoggerInterface $logger, Client $httpClient)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
    }

    public function fetchShopwareArticleInfoByArticleNumber(string $articleNumber): ?ShopwareArticleInfo
    {
        try {
            $response = $this->httpClient->get("/api/articles/{$articleNumber}", [
                'query' => [
                    'useNumberAsId' => true,
                ],
            ]);

            $swArticleData = json_decode($response->getBody(), true);
            $swArticleInfo = new ShopwareArticleInfo($swArticleData);

            if ($swArticleInfo) {
                $this->logger->info(__METHOD__ . ' Found article in shopware', [
                    'articleNumber' => $articleNumber,
                    'swArticleID' => $swArticleInfo->getMainDetailArticleId(),
                ]);
            }

            return $swArticleInfo;
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->logger->info(__METHOD__ . ' Article does not exist in shopware', [
                    'articleNumber' => $articleNumber,
                ]);

                return null;
            }

            throw $e;
        }
    }

    public function updateShopwareArticle(int $swArticleID, array $articleData): void
    {
        try {
            $response = $this->httpClient->put("/api/articles/{$swArticleID}", [
                'json' => $articleData
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            $this->logger->error('ShopwareAPI: failed to update article', [
                'swArticleID' => $swArticleID,
                'articleData' => $articleData,
                'responseHeaders' => $response->getHeaders(),
                'responseBody' => (string)$response->getBody(),
            ]);

            throw $e;
        }
    }

    public function createShopwareArticle(array $articleData): ShopwareArticleInfo
    {
        try {
            $response = $this->httpClient->post('/api/articles', [
                'json' => $articleData
            ]);

            $articleData = json_decode($response->getBody(), true);
            $sai = new ShopwareArticleInfo($articleData);

            return $this->fetchShopwareArticleInfoByArticleID($sai->getArticleID());
        } catch (RequestException $e) {
            $response = $e->getResponse();

            $this->logger->error('ShopwareAPI: failed to create article', [
                'articleData' => $articleData,
                'responseHeaders' => $response->getHeaders(),
                'responseBody' => (string)$response->getBody(),
            ]);

            throw $e;
        }
    }

    public function deactivateShopwareArticle(int $swArticleID): void
    {
        $response = $this->httpClient->put("/api/articles/{$swArticleID}", [
            'json' => [
                'active' => false,
            ],
        ]);
    }

    public function fetchShopwareArticleInfoByArticleID(int $swArticleID): ?ShopwareArticleInfo
    {
        try {
            $response = $this->httpClient->get("/api/articles/{$swArticleID}");

            $swArticleData = json_decode($response->getBody(), true);
            $swArticleInfo = new ShopwareArticleInfo($swArticleData);

            if ($swArticleInfo) {
                $this->logger->info(__METHOD__ . ' Found article in shopware', [
                    'swArticleID' => $swArticleInfo->getMainDetailArticleId(),
                ]);
            }

            return $swArticleInfo;
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->logger->info(__METHOD__ . ' Article does not exist in shopware', [
                    'swArticleID' => $swArticleID,
                ]);

                return null;
            }

            throw $e;
        }
    }

    public function fetchOrders(array $filters): ?array
    {
        $loggingContext = ['filters' => $filters];

        try {
            $this->logger->info(__METHOD__, $loggingContext);
            $response = $this->httpClient->get('/api/orders', [
                'query' => [
                    'filter' => $filters,
                ],
            ]);

            return \GuzzleHttp\json_decode((string)$response->getBody(), true);
        } catch (BadResponseException | \InvalidArgumentException $e) {
            $this->logger->warning(__METHOD__ . ' Failed to fetch orders from shopware', $loggingContext);
            report($e);

            return null;
        }
    }

    public function fetchOrderDetails(int $orderID): ?array
    {
        $loggingContext = ['orderID' => $orderID];

        try {
            $this->logger->info(__METHOD__, $loggingContext);
            $response = $this->httpClient->get("/api/orders/{$orderID}");

            return \GuzzleHttp\json_decode((string)$response->getBody(), true);
        } catch (BadResponseException | \InvalidArgumentException $e) {
            $this->logger->warning(__METHOD__ . ' Failed to fetch order details from shopware', $loggingContext);
            report($e);

            return null;
        }
    }

    public function updateOrderStatus(int $orderID, int $newStatusID, array $details)
    {
        $loggingContext = [
            'orderID' => $orderID, 'newStatusID' => $newStatusID, 'details' => $details,
        ];
        $this->logger->info(__METHOD__, $loggingContext);

        $response = $this->httpClient->put("/api/orders/{$orderID}", [
            'json' => [
                'orderStatusId' => $newStatusID,
                'details' => $details,
            ],
        ]);
    }

    public function updateOrder(string $orderNumber, array $data): void
    {
        $loggingContext = [
            'orderNumber' => $orderNumber,
            'data' => $data,
        ];
        $this->logger->info(__METHOD__, $loggingContext);

        try {
            $this->httpClient->put("/api/orders{$orderNumber}", [
                'query' => ['useNumberAsId' => 1],
                'json' => $data,
            ]);
        } catch (RequestException $e) {
            $this->logger->error(__METHOD__ . ' failed to update order', array_merge($loggingContext, [
                'responseHeaders' => $e->hasResponse() ? $e->getResponse()->getHeaders() : null,
            ]));

            if ($e->hasResponse()) $this->logger->error((string)$e->getResponse()->getBody());

            throw $e;
        }
    }
}
