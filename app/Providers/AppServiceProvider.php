<?php

namespace App\Providers;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Services\TranslationExportServiceInterface;
use App\Contracts\Services\TranslationServiceInterface;
use App\Repositories\Eloquent\TranslationRepository;
use App\Services\TranslationExportService;
use App\Services\TranslationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(TranslationRepositoryInterface::class, TranslationRepository::class);
        $this->app->bind(TranslationServiceInterface::class, TranslationService::class);
        $this->app->bind(TranslationExportServiceInterface::class, TranslationExportService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
