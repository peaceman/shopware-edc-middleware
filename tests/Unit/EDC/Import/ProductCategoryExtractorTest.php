<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\EDC\Import;

use App\EDC\Import\Parser\ProductFeedPartParser;
use App\EDC\Import\ProductCategoryExtractor;
use App\EDC\Import\ProductImageLoader;
use App\EDCFeed;
use App\EDCFeedPartProduct;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductCategoryExtractorTest extends TestCase
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

    public function testCategoryExtraction()
    {
        // setup edc products
        $imageLoader = $this->createMock(ProductImageLoader::class);

        /** @var ProductFeedPartParser $productFeedPartParser */
        $productFeedPartParser = $this->app->make(ProductFeedPartParser::class, ['imageLoader' => $imageLoader]);
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-1.xml')));
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-2.xml')));
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-3.xml')));

        $productCategoryExtractor = $this->createProductCategoryExtractor();
        $categories = $productCategoryExtractor->extract();

        static::assertEquals(
            [
                ['id' => 8, 'title' => 'Damenwäsche', 'childs' => [
                    ['id' => 143, 'title' => 'Strümpfe'],
                ]],
                ['id' => 2, 'title' => 'Dildo', 'childs' => [
                    ['id' => 129, 'title' => 'Dildo Normal'],
                ]],
                ['id' => 3, 'title' => 'Toys für Herren', 'childs' => [
                    ['id' => 166, 'title' => 'Penissleeves'],
                ]],
            ],
            $categories
        );
    }

    protected function createProductFeedPartFromFile(string $filePath): EDCFeedPartProduct
    {
        $rf = $this->storageDirector->createFileFromPath('product-feed-part.xml', $filePath);

        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartProduct();
        $feed->fullFeed()->associate($fullFeed);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function createProductCategoryExtractor(array $params = []): ProductCategoryExtractor
    {
        return $this->app->make(ProductCategoryExtractor::class, $params);
    }
}
