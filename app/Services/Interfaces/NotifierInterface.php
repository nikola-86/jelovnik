<?php

namespace App\Services\Interfaces;

use App\Models\Employee;
use App\Models\MealChoice;

interface NotifierInterface
{
    /**
     * Notify about a meal choice
     *
     * @param Employee $employee The employee who made the choice
     * @param MealChoice $mealChoice The meal choice to notify about
     * @param bool $isNew Whether this is a new meal choice or an update
     * @return bool Success/failure
     */
    public function notify(Employee $employee, MealChoice $mealChoice, bool $isNew): bool;
}

