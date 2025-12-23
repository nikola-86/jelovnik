<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $slack_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Employee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'slack_id',
    ];

    public function mealChoices(): HasMany
    {
        return $this->hasMany(MealChoice::class);
    }

    /**
     * Get employee statistics
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        $total = static::count();
        $withSlackId = static::whereNotNull('slack_id')
            ->where('slack_id', '!=', '')
            ->count();
        $withoutSlackId = $total - $withSlackId;

        return [
            'total' => $total,
            'with_slack_id' => $withSlackId,
            'without_slack_id' => $withoutSlackId,
        ];
    }
}
