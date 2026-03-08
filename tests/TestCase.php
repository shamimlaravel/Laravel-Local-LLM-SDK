<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \LaravelLocalLlm\LocalLlmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('llm.default', 'ollama');
        $app['config']->set('llm.drivers.ollama.url', 'http://localhost:11434');
        $app['config']->set('llm.drivers.ollama.default_model', 'llama3.2');
    }
}
