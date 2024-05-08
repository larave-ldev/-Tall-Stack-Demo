<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\SupplierAddresses
 *
 * @property int $id
 * @property int $supplier_id
 * @property string|null $type
 * @property string|null $state
 * @property string|null $suburb
 * @property string|null $address
 * @property string|null $country
 * @property string|null $postcode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 *
 * @method static Builder|SupplierAddresses newModelQuery()
 * @method static Builder|SupplierAddresses newQuery()
 * @method static Builder|SupplierAddresses query()
 * @method static Builder|SupplierAddresses whereAddress($value)
 * @method static Builder|SupplierAddresses whereCountry($value)
 * @method static Builder|SupplierAddresses whereCreatedAt($value)
 * @method static Builder|SupplierAddresses whereId($value)
 * @method static Builder|SupplierAddresses wherePostcode($value)
 * @method static Builder|SupplierAddresses whereState($value)
 * @method static Builder|SupplierAddresses whereSuburb($value)
 * @method static Builder|SupplierAddresses whereSupplierId($value)
 * @method static Builder|SupplierAddresses whereType($value)
 * @method static Builder|SupplierAddresses whereUpdatedAt($value)
 * <<<<<<< HEAD
 * =======
 *
 * >>>>>>> dev
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class SupplierAddresses extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'type',
        'state',
        'suburb',
        'address',
        'country',
        'postcode',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
