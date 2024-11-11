<?php

namespace JeremySalmon\LaravelLLMContext;

use Illuminate\Support\ServiceProvider;
use JeremySalmon\LaravelLLMContext\Commands\GenerateLLMContext;

class LLMContextServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateLLMContext::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/llm-context.php' => config_path('llm-context.php'),
            ], 'llm-context-config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/llm-context.php',
            'llm-context'
        );
    }
}
