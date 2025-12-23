<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\DataProviderInterface;
use App\Services\Interfaces\NotifierInterface;
use App\Services\Implementations\CsvDataProvider;
use App\Services\Implementations\SlackNotifier;
use App\Services\MealChoiceProcessor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DataProviderInterface::class, CsvDataProvider::class);
        $this->app->bind(NotifierInterface::class, SlackNotifier::class);
        
        $this->app->bind(MealChoiceProcessor::class, function ($app) {
            return new MealChoiceProcessor(
                $app->make(DataProviderInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
