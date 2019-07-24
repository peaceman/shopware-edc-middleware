<?php
/**
 * lel since 2019-07-24
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SWOrderDetail
 * @package App
 *
 * @property int $id
 * @property int $order_id
 * @property array $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read SWOrder $order
 */
class SWOrderDetail extends Model
{
    protected $table = 'sw_order_details';
    protected static $unguarded = true;

    protected $casts = [
        'data' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SWOrder::class, 'order_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only(['id', 'order_id']);
    }
}
