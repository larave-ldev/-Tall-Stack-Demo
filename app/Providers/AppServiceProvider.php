<?php

namespace App\Providers;

use App\Promodata\PromodataClient;
use App\Promodata\PromodataClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //        $this->app->singleton(PromodataApi::class, function ($app) {
        //            return new PromodataApi;
        //        });
        $this->app->bind(PromodataClientInterface::class, PromodataClient::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
