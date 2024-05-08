<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductMedia
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $type Like image, videos,line_art
 * @property string|null $actual_url
 * @property string|null $downloaded_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductMedia newModelQuery()
 * @method static Builder|ProductMedia newQuery()
 * @method static Builder|ProductMedia query()
 * @method static Builder|ProductMedia whereActualUrl($value)
 * @method static Builder|ProductMedia whereCreatedAt($value)
 * @method static Builder|ProductMedia whereDownloadedUrl($value)
 * @method static Builder|ProductMedia whereId($value)
 * @method static Builder|ProductMedia whereProductId($value)
 * @method static Builder|ProductMedia whereType($value)
 * @method static Builder|ProductMedia whereUpdatedAt($value)
 *
 * @property-read Product|null $products
 * @property int|null $is_media_uploaded
 *
 * @method static Builder|ProductMedia whereIsMediaUploaded($value)
 *
 * @property int|null $is_uploaded This flag for media is uploaded on magento or not
 *
 * @method static Builder|ProductMedia whereIsUploaded($value)
 *
 * @mixin Eloquent
 * @mixin Eloquent
 *
 * @property int|null $magento_img_id
 *
 * @method static Builder|ProductMedia whereMagentoImgId($value)
 *
 * @mixin \Eloquent
 */
class ProductMedia extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'actual_url',
        'downloaded_url',
        'is_uploaded',
        'magento_img_id',
    ];

    public function products(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function downloadedUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? asset($value) : null,
        );
    }
}
