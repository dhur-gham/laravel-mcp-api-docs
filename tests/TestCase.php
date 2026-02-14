<?php

namespace DhurGham\LaravelMcpApiDocs\Tests;

use DhurGham\LaravelMcpApiDocs\LaravelMcpApiDocsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelMcpApiDocsServiceProvider::class];
    }
}
