<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property int $employee_id
 * @property string $choice
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\Employee $employee
 * @property \App\Models\SlackNotification|null $slackNotification
 */
class MealChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'choice',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function slackNotification(): HasOne
    {
        return $this->hasOne(\App\Models\SlackNotification::class);
    }

    /**
     * Get all meal choices formatted for API response
     *
     * @return \Illuminate\Support\Collection
     */
    /**
     * Get all meal choices formatted for API response
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public static function getFormattedForApi(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, MealChoice> $mealChoices */
        $mealChoices = static::with(['employee', 'slackNotification'])
            ->orderBy('date', 'desc')
            ->get();
            
        return $mealChoices->map(fn (MealChoice $mealChoice) => $mealChoice->toApiArray());
    }

    /**
     * Convert meal choice to API array format
     *
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'choice' => $this->choice,
            'date' => $this->date->format('Y-m-d'),
            'employee' => [
                'name' => $this->employee->name,
                'email' => $this->employee->email,
                'slack_id' => $this->employee->slack_id,
            ],
            'slack_status' => $this->slackNotification ? $this->slackNotification->status : 'pending',
        ];
    }

    /**
     * Get meal choice statistics
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        $total = static::count();
        $withSlackId = static::whereHas('employee', function ($q) {
            $q->whereNotNull('slack_id')->where('slack_id', '!=', '');
        })->count();
        $withoutSlackId = $total - $withSlackId;

        return [
            'total' => $total,
            'with_slack_id' => $withSlackId,
            'without_slack_id' => $withoutSlackId,
        ];
    }
}
