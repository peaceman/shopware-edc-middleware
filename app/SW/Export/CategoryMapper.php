<?php
/**
 * lel since 2019-07-29
 */

namespace App\SW\Export;

use Illuminate\Contracts\Filesystem\Filesystem;
use League\Csv\Reader;

class CategoryMapper
{
    /** @var Filesystem */
    protected $localFS;

    /**
     * edc id -> shopware id
     *
     * @var array
     */
    protected $mapping;

    public function __construct(Filesystem $localFS, string $mappingFilePath)
    {
        $this->localFS = $localFS;

        $csv = $this->openReader($mappingFilePath);
        $this->importMappings($csv);
    }

    protected function openReader(string $filename): Reader
    {
        $reader = Reader::createFromStream($this->localFS->readStream($filename));
        $reader->setHeaderOffset(0);
        $reader->setDelimiter(',');

        return $reader;
    }

    protected function importMappings(Reader $reader): void
    {
        $iter = $reader->getRecords(['sourceID', 'sourceName', 'targetID', 'targetName']);

        foreach ($iter as $catMapping) {
            $this->mapping[$catMapping['sourceID']] = $catMapping['targetID'];
        }
    }

    public function map(string $edcCategoryID): ?string
    {
        return $this->mapping[$edcCategoryID] ?? null;
    }
}
