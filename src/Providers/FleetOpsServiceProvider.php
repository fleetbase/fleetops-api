<?php

namespace Fleetbase\Providers;

use Exception;

require __DIR__ . '/../../vendor/autoload.php';

if (!class_exists(CoreServiceProvider::class)) {
    throw new Exception('FleetOps cannot be loaded without `fleetbase/core-api` installed!');
}

/**
 * CoreServiceProvider
 */
class FleetOpsServiceProvider extends CoreServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerExpansionsFrom(__DIR__ . '/../Expansions');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        $this->mergeConfigFrom(__DIR__ . '/../../config/fleetops.php', 'fleetops');
    }
}
