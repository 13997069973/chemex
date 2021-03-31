<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static where(string $key, string $value, string $value = null)
 * @method static whereBetween(string $string, array $array)
 * @method static count()
 *
 * @property string name
 * @property string purchased
 * @property string version
 * @property int category_id
 * @property int vendor_id
 * @property float price
 * @property string expired
 * @property int purchased_channel_id
 * @property string asset_number
 * @property int counts
 * @property int distribution
 */
class SoftwareRecord extends Model
{
    use HasDateTimeFormatter;
    use SoftDeletes;
    use ModelTree;

    /**
     * 需要被包括进排序字段的字段，一般来说是虚拟出来的关联字段.
     *
     * @var string[]
     */
    public $sortIncludeColumns = [
        'category.name',
        'vendor.name',
        'channel.name',
        'left_counts',
        'expiration_left_days',

    ];

    /**
     * 需要被排除出排序字段的字段，一般来说是关联字段的原始字段.
     *
     * @var string[]
     */
    public $sortExceptColumns = [
        'category_id',
        'vendor_id',
        'purchased_channel_id',
        'deleted_at',
    ];

    protected $table = 'software_records';

    /**
     * 模型事件
     */
    protected static function booting()
    {
        static::saving(function ($model) {
            if (!empty($model->deleted_at)) {
                abort(401, 'you can not do that.');
            }
        });
    }

    /**
     * 软件记录有一个分类.
     *
     * @return HasOne
     */
    public function category(): HasOne
    {
        return $this->hasOne(SoftwareCategory::class, 'id', 'category_id');
    }

    /**
     * 软件记录有一个厂商.
     *
     * @return HasOne
     */
    public function vendor(): HasOne
    {
        return $this->hasOne(VendorRecord::class, 'id', 'vendor_id');
    }

    /**
     * 软件记录有一个购入途径.
     *
     * @return HasOne
     */
    public function channel(): HasOne
    {
        return $this->hasOne(PurchasedChannel::class, 'id', 'purchased_channel_id');
    }

    /**
     * 软件剩余可用授权数量.
     *
     * @return int|string
     */
    public function leftCounts()
    {
        $used = $this->track()->count();
        $counts = $this->counts;
        if ($counts <= 0) {
            return '不受限';
        }

        return $this->counts - $used;
    }

    /**
     * 软件记录有很多追踪.
     *
     * @return HasMany
     */
    public function track(): HasMany
    {
        return $this->hasMany(SoftwareTrack::class, 'software_id', 'id');
    }
}
