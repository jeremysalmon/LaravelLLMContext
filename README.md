# README.md
# Laravel LLM Context Generator

Generate comprehensive context about your Laravel application for LLM consumption.

## Installation

```bash
composer require your-username/laravel-llm-context
```

## Usage

1. (Optional) Publish the configuration:
   ```bash
   php artisan vendor:publish --tag="llm-context-config"
   ```

2. Generate the context:
   ```bash
   php artisan llm:generate-context
   ```

This will create two files in your storage/app/llm-context directory:
- llm-context.json: Structured data about your application
- llm-context.txt: Human-readable format suitable for LLM consumption

## Configuration

You can customize the behavior by modifying config/llm-context.php:

```php
return [
    'output_path' => storage_path('app/llm-context'),
    'include' => [
        'migrations' => true,
        'models' => true,
        'relationships' => true,
    ],
    'model_path' => app_path('Models'),
];
```
