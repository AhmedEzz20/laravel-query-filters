<?php

namespace Astro\LaravelQueryFilters\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeFilterCommand extends Command
{
    protected $signature = 'make:filter {name}';

    protected $description = 'Create a new query filter class';

    public function handle(): void
    {
        $name = $this->argument('name');

        $path = app_path("Http/Filters/{$name}.php");

        if (File::exists($path)) {
            $this->error('Filter already exists!');
            return;
        }

        $stub = file_get_contents(__DIR__ . '/../../stubs/filter.stub');

        $content = str_replace(
            ['DummyClass', 'DummyNamespace'],
            [$name, 'App\\Http\\Filters'],
            $stub
        );

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $content);

        $this->info("Filter created successfully: {$path}");
    }
}