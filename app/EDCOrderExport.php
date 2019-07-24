<?php
/**
 * lel since 2019-07-24
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EDCOrderExport
 * @package App
 *
 * @property int $id
 * @property int $order_id
 * @property string $status
 * @property string $sent
 * @property array $received
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read SWOrder $order
 */
class EDCOrderExport extends Model
{
    protected $table = 'edc_order_exports';
    protected static $unguarded = true;

    protected $casts = [
        'received' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SWOrder::class, 'order_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only(['id', 'order_id', 'status']);
    }
}
