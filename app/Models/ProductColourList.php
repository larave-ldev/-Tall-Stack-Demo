<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductColourList
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $for
 * @property string|null $name
 * @property string|null $image
 * @property mixed|null $swatch
 * @property mixed|null $colours
 * @property mixed|null $appa_colours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|ProductColourList newModelQuery()
 * @method static Builder|ProductColourList newQuery()
 * @method static Builder|ProductColourList query()
 * @method static Builder|ProductColourList whereAppaColours($value)
 * @method static Builder|ProductColourList whereColours($value)
 * @method static Builder|ProductColourList whereCreatedAt($value)
 * @method static Builder|ProductColourList whereFor($value)
 * @method static Builder|ProductColourList whereId($value)
 * @method static Builder|ProductColourList whereImage($value)
 * @method static Builder|ProductColourList whereName($value)
 * @method static Builder|ProductColourList whereProductId($value)
 * @method static Builder|ProductColourList whereSwatch($value)
 * @method static Builder|ProductColourList whereUpdatedAt($value)
 *
 * @property string|null $downloaded_image
 *
 * @method static Builder|ProductColourList whereDownloadedImage($value)
 *
 * @property-read Product $product
 * @property mixed|null $magento_primary_colour_ids
 *
 * @method static Builder|ProductColourList whereMagentoPrimaryColourIds($value)
 *
 * @property int|null $is_image_uploaded
 *
 * @method static Builder|ProductColourList whereIsImageUploaded($value)
 *
 * @property int|null $is_uploaded This flag for media is uploaded on magento or not
 *
 * @method static Builder|ProductColourList whereIsUploaded($value)
 *
 * @mixin Eloquent
 * @mixin Eloquent
 *
 * @property int|null $magento_img_id
 *
 * @method static Builder|ProductColourList whereMagentoImgId($value)
 *
 * @mixin \Eloquent
 */
class ProductColourList extends Model
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
        'for',
        'name',
        'image',
        'downloaded_image',
        'swatch',
        'colours',
        'appa_colours',
        'magento_primary_colour_ids',
        'is_uploaded',
        'magento_img_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
