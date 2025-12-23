<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MealChoiceController;
use App\Http\Controllers\Api\FileUploadController;

Route::get('/', function () {
    return view('app');
});

Route::get('/meal-choices', [MealChoiceController::class, 'index'])->name('meal-choices.index');
Route::get('/statistics', [MealChoiceController::class, 'statistics'])->name('statistics');
Route::post('/upload', [FileUploadController::class, 'store'])->name('upload.store');
