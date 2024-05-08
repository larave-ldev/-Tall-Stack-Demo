<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\cronSchedule
 *
 * @property int $id
 * @property string $command_name The command name which is being running
 * @property string $description Description of command
 * @property string $schedule Command schedule when it will be run
 * @property string|null $start_time Command start time
 * @property string|null $end_time Command end time
 * @property int|null $duration_minutes How much command takes running time
 * @property int $is_active
 * @property int $is_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|cronSchedule newModelQuery()
 * @method static Builder|cronSchedule newQuery()
 * @method static Builder|cronSchedule query()
 * @method static Builder|cronSchedule whereCommandName($value)
 * @method static Builder|cronSchedule whereCreatedAt($value)
 * @method static Builder|cronSchedule whereDescription($value)
 * @method static Builder|cronSchedule whereDurationMinutes($value)
 * @method static Builder|cronSchedule whereEndTime($value)
 * @method static Builder|cronSchedule whereId($value)
 * @method static Builder|cronSchedule whereIsActive($value)
 * @method static Builder|cronSchedule whereIsEnabled($value)
 * @method static Builder|cronSchedule whereSchedule($value)
 * @method static Builder|cronSchedule whereStartTime($value)
 * @method static Builder|cronSchedule whereUpdatedAt($value)
 *
 * @property int|null $duration_seconds How much command took running time
 * @property string $status 0 - Inactive, 1 - Cron is Active, 2 - Cron is Running
 *
 * @method static Builder|cronSchedule whereDurationSeconds($value)
 * @method static Builder|cronSchedule whereStatus($value)
 *
 * @mixin Eloquent
 * @mixin \Eloquent
 */
class cronSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'command_name',
        'description',
        'schedule',
        'start_time',
        'end_time',
        'duration_seconds',
        'status',
    ];
}
