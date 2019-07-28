<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC;

use App\EDCExportStatus;
use Assert\Assert;

class EDCOrderExportInfo
{
    /** @var array */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getStatus(): string
    {
        $status = $this->get('result');
        Assert::that($status)->inArray(EDCExportStatus::getConstants());

        return $status;
    }

    public function getOrderNumber(): string
    {
        return $this->get('ordernumber');
    }

    public function getMessage(): string
    {
        return $this->get('message');
    }

    public function getErrorCode(): string
    {
        return $this->get('errorcode');
    }

    protected function get(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
