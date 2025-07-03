<?php

namespace Fahlgrendigital\StaticCacheClean;

use Fahlgrendigital\StaticCacheClean\Console\Commands\CleanStaticPageCache;
use Statamic\Providers\AddonServiceProvider;

class StaticCacheCleanProvider extends AddonServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanStaticPageCache::class
            ]);
        }
    }
}