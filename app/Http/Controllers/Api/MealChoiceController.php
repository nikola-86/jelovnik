<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MealChoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $mealChoices = \App\Models\MealChoice::getFormattedForApi();

        return response()->json($mealChoices);
    }

    public function statistics(Request $request): JsonResponse
    {
        return response()->json([
            'employees' => \App\Models\Employee::getStatistics(),
            'meal_choices' => \App\Models\MealChoice::getStatistics(),
        ]);
    }

}

