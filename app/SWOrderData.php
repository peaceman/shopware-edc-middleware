<?php
/**
 * lel since 2019-07-24
 */

namespace App;

use App\SW\ShopwareOrderInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SWOrderData
 * @package App
 *
 * @property int $id
 * @property int $order_id
 * @property int $order_detail_id
 * @property string $sw_transfer_status
 * @property string $edc_transfer_status
 * @property string|null $edc_order_number
 * @property string|null $tracking_number
 * @property Carbon|null $current_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read SWOrder $order
 * @property-read SWOrderDetail $orderDetail
 */
class SWOrderData extends Model
{
    protected $table = 'sw_order_data';
    protected static $unguarded = true;

    public function order(): BelongsTo
    {
        return $this->belongsTo(SWOrder::class, 'order_id', 'id');
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(SWOrderDetail::class, 'order_detail_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id',
            'order_id',
            'order_detail_id',
            'sw_transfer_status',
            'edc_transfer_status',
        ]);
    }

    public function asShopwareOrderInfo(): ShopwareOrderInfo
    {
        return new ShopwareOrderInfo($this->orderDetail->data);
    }
}
