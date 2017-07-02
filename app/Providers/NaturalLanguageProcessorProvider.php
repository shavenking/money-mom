<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Lib\NaturalLanguageProcessor\GoogleNaturalLanguageProcessor;
use Lib\NaturalLanguageProcessor\NaturalLanguageProcessor;

class NaturalLanguageProcessorProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(NaturalLanguageProcessor::class, function () {
            return new GoogleNaturalLanguageProcessor(
                new Client,
                env('GOOGLE_API_KEY')
            );
        });
    }
}
