<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import;

use App\EDCFeedPartStock;
use App\ResourceFile\StorageDirector;
use Assert\Assert;

class StockXMLFactory
{
    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function create(EDCFeedPartStock $feedPart): StockXML
    {
        if ($feedPart->content) {
            return $this->createFromContent($feedPart);
        }

        return $this->createFromFile($feedPart);
    }

    protected function createFromContent(EDCFeedPartStock $feedPart): StockXML
    {
        return StockXML::fromString($feedPart->content);
    }

    protected function createFromFile(EDCFeedPartStock $feedPart): StockXML
    {
        Assert::that($feedPart->file)->notNull();

        $filePath = $this->storageDirector->getLocalPath($feedPart->file);

        return StockXML::fromFilePath($filePath);
    }
}
