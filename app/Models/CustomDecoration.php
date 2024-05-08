<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CustomDecoration
 *
 * @property int $id
 * @property string $decoration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration whereDecoration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomDecoration whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CustomDecoration extends Model
{
    use HasFactory;

    protected $fillable = [
        'decoration',
    ];
}
