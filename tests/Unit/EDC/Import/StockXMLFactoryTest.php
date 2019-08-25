<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import;

use App\EDC\Import\StockXML;
use App\EDC\Import\StockXMLFactory;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class StockXMLFactoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @var StorageDirector */
    protected $storageDirector;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake(Storage::getDefaultCloudDriver());

        $this->storageDirector = $this->app[StorageDirector::class];
    }

    public function testFromFile(): void
    {
        $feedPart = $this->createStockFeedPartFromFileToFile(fixture_path('product-stocks-1.xml'));
        $stockXMLFactory = $this->createStockXMLFactory();

        $stockXML = $stockXMLFactory->create($feedPart);
        static::assertInstanceOf(StockXML::class, $stockXML);
    }

    public function testFromContent(): void
    {
        $feedPart = $this->createStockFeedPartFromFileToContent(fixture_path('product-stocks-1.xml'));
        $stockXMLFactory = $this->createStockXMLFactory();

        $stockXML = $stockXMLFactory->create($feedPart);
        static::assertInstanceOf(StockXML::class, $stockXML);
    }

    protected function createStockXMLFactory(): StockXMLFactory
    {
        return $this->app[StockXMLFactory::class];
    }

    protected function createStockFeedPartFromFileToFile(string $filePath): EDCFeedPartStock
    {
        $rf = $this->storageDirector->createFileFromPath('product-stock-part.xml', $filePath);

        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartStock();
        $feed->fullFeed()->associate($fullFeed);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function createStockFeedPartFromFileToContent(string $filePath): EDCFeedPartStock
    {
        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartStock();
        $feed->fullFeed()->associate($fullFeed);
        $feed->content = file_get_contents($filePath);
        $feed->save();

        return $feed;
    }
}
