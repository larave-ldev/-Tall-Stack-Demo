<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GeneralLog
 *
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog query()
 *
 * @property int $id
 * @property string|null $module_name
 * @property string|null $action
 * @property string|null $api_end_point
 * @property string|null $payload
 * @property string|null $response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereApiEndPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GeneralLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_name',
        'action',
        'api_end_point',
        'payload',
        'response',
    ];
}
