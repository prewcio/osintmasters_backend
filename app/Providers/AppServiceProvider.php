<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Broadcast;
use App\Broadcasting\SSEBroadcaster;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Http::withOptions([
            'verify' => env('CURL_CA_BUNDLE', false)
        ]);

        Broadcast::extend('sse', function ($app) {
            return new SSEBroadcaster();
        });
    }
}
