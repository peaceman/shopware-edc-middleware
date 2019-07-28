<?php
/**
 * lel since 2019-07-24
 */

namespace App;

use App\Utils\RetiringRelation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Class SWOrder
 * @package App
 *
 * @property int $id
 * @property string $sw_order_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read SWOrderData $currentData
 * @property-read SWOrderData[]|Collection $data
 * @property-read SWOrderDetail[]|Collection $details
 * @property-read EDCOrderExport[]|Collection $exports
 * @property-read EDCOrderUpdate[]|Collection $updates
 * @property-read EDCOrderExport[]|Collection $failedExports
 *
 * @method static Builder withOrderNumber($orderNumber)
 */
class SWOrder extends Model
{
    protected $table = 'sw_orders';
    protected static $unguarded = true;

    public function currentData(): HasOne
    {
        return $this->hasOne(SWOrderData::class, 'order_id', 'id')
            ->whereNull('current_until');
    }

    public function data(): HasMany
    {
        return $this->hasMany(SWOrderData::class, 'order_id', 'id');
    }

    public function saveData(SWOrderData $data): void
    {
        (new RetiringRelation($this, 'currentData'))->save($data);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SWOrderDetail::class, 'order_id', 'id');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(EDCOrderExport::class, 'order_id', 'id');
    }

    public function failedExports(): HasMany
    {
        return $this->hasMany(EDCOrderExport::class, 'order_id', 'id')
            ->where('status', EDCExportStatus::FAIL);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(EDCOrderUpdate::class, 'order_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only(['id', 'sw_order_number']);
    }

    public function scopeWithOrderNumber(Builder $query, $orderNumber): Builder
    {
        return $query->where('sw_order_number', '=', $orderNumber);
    }
}
