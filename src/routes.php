<?php

use Fleetbase\Support\InternalConfig;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix(InternalConfig::get('api.routing.prefix', 'flb'))->namespace('Fleetbase\Http\Controllers')->group(
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | Internal FleetOps API Routes
        |--------------------------------------------------------------------------
        |
        | Primary internal routes for console.
        */
        $router->prefix(InternalConfig::get('api.routing.internal_prefix', 'int'))->namespace('Internal')->group(
            function ($router) {
                $router->group(
                    ['prefix' => 'v1', 'namespace' => 'v1', 'middleware' => ['fleetbase.protected']],
                    function ($router) {
                        $router->fleetbaseRoutes('contacts');
                        $router->fleetbaseRoutes(
                            'drivers',
                            function ($router, $controller) {
                                $router->get('statuses', $controller('statuses'));
                            }
                        );
                        $router->fleetbaseRoutes('entities');
                        $router->fleetbaseRoutes('fleets');
                        $router->fleetbaseRoutes('fuel-reports');
                        $router->fleetbaseRoutes('integrated-vendors');
                        $router->fleetbaseRoutes(
                            'orders',
                            function ($router, $controller) {
                                $router->get('search', $controller('search'));
                                $router->get('statuses', $controller('statuses'));
                                $router->get('types', $controller('types'));
                                $router->get('label/{id}', $controller('label'));
                                $router->get('next-activity/{id}', $controller('nextActivity'));
                                $router->post('process-imports', $controller('importFromFiles'));
                                $router->patch('update-activity/{id}', $controller('updateActivity'));
                                $router->patch('bulk-cancel', $controller('bulkCancel'));
                                $router->patch('cancel', $controller('cancel'));
                                $router->patch('dispatch', $controller('_dispatch'));
                                $router->patch('start', $controller('start'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes('payloads');
                        $router->fleetbaseRoutes(
                            'places',
                            function ($router, $controller) {
                                $router->get('search', $controller('search'))->middleware('cache.headers:private;max_age=3600');
                                $router->get('lookup', $controller('geocode'))->middleware('cache.headers:private;max_age=3600');
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulk-delete'));
                            }
                        );
                        $router->fleetbaseRoutes('proofs');
                        $router->fleetbaseRoutes('purchase-rates');
                        $router->fleetbaseRoutes('routes');
                        $router->fleetbaseRoutes('service-areas');
                        $router->fleetbaseRoutes('service-quotes');
                        $router->fleetbaseRoutes('service-rates');
                        $router->fleetbaseRoutes('tracking-numbers');
                        $router->fleetbaseRoutes('tracking-statuses');
                        $router->fleetbaseRoutes(
                            'vehicles',
                            function ($router, $controller) {
                                $router->get('statuses', $controller('statuses'));
                            }
                        );
                        $router->fleetbaseRoutes('vendors');
                        $router->group(
                            ['prefix' => 'fleet-ops'],
                            function ($router) {
                                $router->group(
                                    ['prefix' => 'order-configs'],
                                    function () use ($router) {
                                        $router->get('get-installed', 'OrderConfigController@getInstalled');
                                        $router->get('dynamic-meta-fields', 'OrderConfigController@getDynamicMetaFields');
                                        $router->post('save', 'OrderConfigController@save');
                                        $router->post('new', 'OrderConfigController@new');
                                        $router->post('clone', 'OrderConfigController@clone');
                                        $router->delete('{id}', 'OrderConfigController@delete');
                                    }
                                );
                                $router->group(
                                    ['prefix' => 'lookup'],
                                    function ($router) {
                                        $router->get('customers', 'FleetOpsLookupController@polymorphs');
                                        $router->get('facilitators', 'FleetOpsLookupController@polymorphs');
                                    }
                                );
                                $router->group(
                                    ['prefix' => 'live'],
                                    function ($router) {
                                        $router->get('coordinates', 'LiveController@coordinates');
                                        $router->get('routes', 'LiveController@routes');
                                        $router->get('orders', 'LiveController@orders');
                                        $router->get('drivers', 'LiveController@drivers');
                                    }
                                );
                                $router->group(
                                    ['prefix' => 'metrics'],
                                    function ($router) {
                                        $router->get('all', 'MetricsController@all');
                                    }
                                );
                            }
                        );
                    }
                );
            }
        );
    }
);
