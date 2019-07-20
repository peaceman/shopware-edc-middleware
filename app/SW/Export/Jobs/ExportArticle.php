<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export\Jobs;

use App\EDCProduct;
use App\SW\Export\ArticleExporter;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExportArticle implements ShouldQueue
{
    /** @var EDCProduct */
    public $edcProduct;

    public function __construct(EDCProduct $edcProduct)
    {
        $this->edcProduct = $edcProduct;
    }

    public function handle(ArticleExporter $articleExporter): void
    {
        $articleExporter->export($this->edcProduct);
    }
}
