<?php

namespace JeremySalmon\LaravelLLMContext\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateLLMContext extends Command
{
    protected $signature = 'llm:generate-context {--output= : Path to save the context file}';
    protected $description = 'Generate context from migrations and models for LLM consumption';

    private $context = [];

    public function handle()
    {
        $this->info('Generating LLM context from your Laravel application...');

        // Process migrations
        $this->processMigrations();

        // Process models
        $this->processModels();

        // Generate and save the context
        $this->saveContext();

        $this->info('Context generation completed!');
    }

    private function processMigrations()
    {
        $migrationPath = database_path('migrations');
        $files = File::glob($migrationPath . '/*.php');

        $this->context['database_schema'] = [];

        foreach ($files as $file) {
            require_once $file;
            
            $className = $this->getMigrationClassName($file);
            if (!class_exists($className)) {
                continue;
            }

            $migration = new $className;
            
            // Get the table name if available
            $tableName = $this->getTableNameFromMigration($migration);
            if (!$tableName) {
                continue;
            }

            $schemaDefinition = $this->extractSchemaFromMigration($file);
            
            $this->context['database_schema'][$tableName] = [
                'file' => basename($file),
                'schema' => $schemaDefinition
            ];
        }
    }

    private function processModels()
    {
        $modelPath = app_path('Models');
        $files = File::glob($modelPath . '/*.php');

        $this->context['models'] = [];

        foreach ($files as $file) {
            $className = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (!class_exists($className)) {
                continue;
            }

            $model = new $className;
            $reflection = new \ReflectionClass($model);

            $this->context['models'][class_basename($className)] = [
                'table' => $model->getTable(),
                'fillable' => $model->getFillable(),
                'hidden' => $model->getHidden(),
                'casts' => $model->getCasts(),
                'relationships' => $this->getModelRelationships($reflection),
            ];
        }
    }

    private function getMigrationClassName($file)
    {
        $className = Str::studly(preg_replace('/\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($file, '.php')));
        return $className;
    }

    private function getTableNameFromMigration($migration)
    {
        if (method_exists($migration, 'getTable')) {
            return $migration->getTable();
        }
        
        // Try to extract table name from up() method using reflection
        $reflection = new \ReflectionClass($migration);
        $method = $reflection->getMethod('up');
        $contents = file_get_contents($reflection->getFileName());
        
        if (preg_match('/Schema::create\([\'"](.+?)[\'"]/i', $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractSchemaFromMigration($file)
    {
        $contents = file_get_contents($file);
        
        // Extract the schema definition from the up() method
        if (preg_match('/Schema::create.+?{(.+?)}\);/s', $contents, $matches)) {
            $schema = trim($matches[1]);
            // Clean up the schema definition
            $schema = preg_replace('/\s+/', ' ', $schema);
            return $schema;
        }

        return null;
    }

    private function getModelRelationships(\ReflectionClass $reflection)
    {
        $relationships = [];
        
        foreach ($reflection->getMethods() as $method) {
            if ($method->class !== $reflection->getName()) {
                continue;
            }

            $contents = file_get_contents($reflection->getFileName());
            $methodName = $method->getName();
            
            // Check if method contains relationship definitions
            if (preg_match_all('/(hasOne|hasMany|belongsTo|belongsToMany|hasManyThrough|hasOneThrough)\s*\(/', 
                $method->getDocComment() . $contents, $matches)) {
                $relationships[$methodName] = $matches[1][0];
            }
        }

        return $relationships;
    }

    private function saveContext()
    {
        $output = $this->option('output') ?: storage_path('app/llm-context.json');
        
        // Format the context as a string suitable for LLM consumption
        $contextString = "Laravel Application Schema:\n\n";
        
        // Add database schema
        $contextString .= "Database Schema:\n";
        foreach ($this->context['database_schema'] as $table => $info) {
            $contextString .= "Table: {$table}\n";
            $contextString .= "Schema: {$info['schema']}\n\n";
        }
        
        // Add model information
        $contextString .= "\nModels:\n";
        foreach ($this->context['models'] as $model => $info) {
            $contextString .= "Model: {$model}\n";
            $contextString .= "Table: {$info['table']}\n";
            $contextString .= "Fillable Fields: " . implode(', ', $info['fillable']) . "\n";
            $contextString .= "Relationships:\n";
            foreach ($info['relationships'] as $relation => $type) {
                $contextString .= "  - {$relation}: {$type}\n";
            }
            $contextString .= "\n";
        }

        // Save both JSON and text versions
        File::put($output, json_encode($this->context, JSON_PRETTY_PRINT));
        File::put(str_replace('.json', '.txt', $output), $contextString);
        
        $this->info("Context saved to: {$output}");
        $this->info("Human-readable context saved to: " . str_replace('.json', '.txt', $output));
    }
}
