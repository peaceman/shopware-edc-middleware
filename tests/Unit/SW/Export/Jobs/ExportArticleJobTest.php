<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\SW\Export\Jobs;

use App\EDCProduct;
use App\SW\Export\ArticleExporter;
use App\SW\Export\Jobs\ExportArticle;
use Tests\TestCase;

class ExportArticleJobTest extends TestCase
{
    public function testJob()
    {
        $edcProduct = new EDCProduct();

        $articleExporter = $this->createMock(ArticleExporter::class);
        $articleExporter->expects(static::once())
            ->method('export')
            ->with($edcProduct);

        $job = new ExportArticle($edcProduct);
        $this->app->call([$job, 'handle'], ['articleExporter' => $articleExporter]);
    }
}
