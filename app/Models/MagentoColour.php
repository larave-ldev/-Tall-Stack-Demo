<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\MagentoColour
 *
 * @property int $id
 * @property string|null $color
 * @property int|null $magento_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|MagentoColour newModelQuery()
 * @method static Builder|MagentoColour newQuery()
 * @method static Builder|MagentoColour query()
 * @method static Builder|MagentoColour whereColor($value)
 * @method static Builder|MagentoColour whereCreatedAt($value)
 * @method static Builder|MagentoColour whereId($value)
 * @method static Builder|MagentoColour whereMagentoId($value)
 * @method static Builder|MagentoColour whereUpdatedAt($value)
 *
 * @property string $colour
 * @property-read Collection<int, ProductColourMapper> $productColourMapper
 * @property-read int|null $product_colour_mapper_count
 *
 * @method static Builder|MagentoColour whereColour($value)
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class MagentoColour extends Model
{
    use HasFactory;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'colour',
        'magento_id',
    ];

    public function productColourMapper(): HasMany
    {
        return $this->hasMany(ProductColourMapper::class);
    }
}
