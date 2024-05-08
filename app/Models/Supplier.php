<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Supplier
 *
 * @property int $id
 * @property string $name
 * @property string $country
 * @property int $active
 * @property string|null $abn
 * @property string|null $fax
 * @property string|null $email
 * @property string|null $brands
 * @property string|null $phone_1
 * @property string|null $phone_2
 * @property string|null $website
 * @property string|null $appa_name
 * @property string|null $appa_notes
 * @property string|null $appa_profile
 * @property string|null $appa_identifier
 * @property int $promodata_id Insert id of suppliers API
 * @property string $promodata_created_at Insert created_at of suppliers API
 * @property string $promodata_updated_at Insert updated_at of suppliers API
 * @property int|null $magento_id Update last inserted id of magento
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SupplierAddresses> $addresses
 * @property-read int|null $addresses_count
 * @property-read Collection<int, SupplierContacts> $contacts
 * @property-read int|null $contacts_count
 *
 * @method static Builder|Supplier newModelQuery()
 * @method static Builder|Supplier newQuery()
 * @method static Builder|Supplier query()
 * @method static Builder|Supplier whereAbn($value)
 * @method static Builder|Supplier whereActive($value)
 * @method static Builder|Supplier whereAppaIdentifier($value)
 * @method static Builder|Supplier whereAppaName($value)
 * @method static Builder|Supplier whereAppaNotes($value)
 * @method static Builder|Supplier whereAppaProfile($value)
 * @method static Builder|Supplier whereBrands($value)
 * @method static Builder|Supplier whereCountry($value)
 * @method static Builder|Supplier whereCreatedAt($value)
 * @method static Builder|Supplier whereEmail($value)
 * @method static Builder|Supplier whereFax($value)
 * @method static Builder|Supplier whereId($value)
 * @method static Builder|Supplier whereMagentoId($value)
 * @method static Builder|Supplier whereName($value)
 * @method static Builder|Supplier wherePhone1($value)
 * @method static Builder|Supplier wherePhone2($value)
 * @method static Builder|Supplier wherePromodataCreatedAt($value)
 * @method static Builder|Supplier wherePromodataId($value)
 * @method static Builder|Supplier wherePromodataUpdatedAt($value)
 * @method static Builder|Supplier whereUpdatedAt($value)
 * @method static Builder|Supplier whereWebsite($value)
 * <<<<<<< HEAD
 * =======
 *
 * >>>>>>> dev
 *
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property int $is_sync_on_magento Is sync is true then will create or update all products of supplier
 * @property string|null $magento_updated_at If updated, that means it will take same day in cron and update on magento
 *
 * @method static Builder|Supplier whereIsSyncOnMagento($value)
 * @method static Builder|Supplier whereMagentoUpdatedAt($value)
 *
 * @mixin Eloquent
 *
 * @property string|null $supplier_short_name Stored supplier short name
 *
 * @method static Builder|Supplier whereSupplierShortName($value)
 *
 * @mixin \Eloquent
 */
class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'country',
        'active',
        'abn',
        'fax',
        'email',
        'brands',
        'phone_1',
        'phone_2',
        'website',
        'appa_name',
        'appa_notes',
        'appa_profile',
        'appa_identifier',
        'promodata_id',
        'promodata_created_at',
        'promodata_updated_at',
        'magento_id',
        'is_sync_on_magento',
        'magento_updated_at',
    ];

    public static function getProductCountsBySyncStatus()
    {
        return Supplier::select('is_sync_on_magento', DB::raw('count(products.id) as product_count'))
            ->leftJoin('products', 'suppliers.id', '=', 'products.supplier_id')
            ->groupBy('is_sync_on_magento') // Added 'suppliers.id' to GROUP BY clause
            ->pluck('product_count', 'is_sync_on_magento');
    }

    public static function getSupplierCountsBySyncStatus()
    {
        return Supplier::select('is_sync_on_magento')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('is_sync_on_magento')
            ->pluck('count', 'is_sync_on_magento');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContacts::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddresses::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
