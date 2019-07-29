<?php
/**
 * lel since 2019-07-29
 */

namespace Tests\Unit\SW\Export;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_content;

class CategoryMapperTest extends TestCase
{
    /** @var Filesystem */
    protected $localFS;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localFS = Storage::fake('local');
    }

    public function categoryMappingDataProvider(): array
    {
        return [
            ['732', '38'],
            ['736', '49'],
            ['787', '110'],
            ['2305', null],
        ];
    }

    /**
     * @dataProvider categoryMappingDataProvider
     */
    public function testCategoryMapping(string $inputCategoryID, ?string $outputCategoryID)
    {
        $this->localFS->put(config('shopware.categoryMappingFile'), fixture_content('category-mapping.csv'));

        $categoryMapper = $this->createCategoryMapper();

        static::assertEquals($outputCategoryID, $categoryMapper->map($inputCategoryID));
    }

    protected function createCategoryMapper(array $params = []): \App\SW\Export\CategoryMapper
    {
        return $this->app->make(\App\SW\Export\CategoryMapper::class, $params);
    }
}
