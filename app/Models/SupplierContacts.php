<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\SupplierContacts
 *
 * @property int $id
 * @property int $supplier_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone_1
 * @property string|null $phone_2
 * @property string|null $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 *
 * @method static Builder|SupplierContacts newModelQuery()
 * @method static Builder|SupplierContacts newQuery()
 * @method static Builder|SupplierContacts query()
 * @method static Builder|SupplierContacts whereCreatedAt($value)
 * @method static Builder|SupplierContacts whereEmail($value)
 * @method static Builder|SupplierContacts whereId($value)
 * @method static Builder|SupplierContacts whereName($value)
 * @method static Builder|SupplierContacts wherePhone1($value)
 * @method static Builder|SupplierContacts wherePhone2($value)
 * @method static Builder|SupplierContacts wherePosition($value)
 * @method static Builder|SupplierContacts whereSupplierId($value)
 * @method static Builder|SupplierContacts whereUpdatedAt($value)
 * <<<<<<< HEAD
 * =======
 *
 * >>>>>>> dev
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class SupplierContacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'name',
        'email',
        'phone_1',
        'phone_2',
        'position',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
