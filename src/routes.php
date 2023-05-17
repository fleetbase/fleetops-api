<?php

use Illuminate\Support\Facades\Route;
use PhpParser\Node\Expr\FuncCall;

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

Route::prefix(config('fleetops.api.routing.prefix', null))->namespace('Fleetbase\FleetOps\Http\Controllers')->group(
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | Internal FleetOps API Routes
        |--------------------------------------------------------------------------
        |
        | Primary internal routes for console.
        */
        $router->prefix(config('fleetops.api.routing.internal_prefix', 'int'))->namespace('Internal')->group(
            function ($router) {
                $router->group(
                    ['prefix' => 'v1', 'namespace' => 'v1', 'middleware' => ['fleetbase.protected']],
                    function ($router) {
                        $router->fleetbaseRoutes(
                            'contacts',
                            function ($router, $controller) {
                                $router->get('export', $controller('export'));
                                $router->get('facilitators/{id}', $controller('getAsFacilitator'));
                                $router->get('customers/{id}', $controller('getAsCustomer'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'drivers',
                            function ($router, $controller) {
                                $router->get('statuses', $controller('statuses'));
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes('entities');
                        $router->fleetbaseRoutes(
                            'fleets',
                            function ($router, $controller) {
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'fuel-reports',
                            function ($router, $controller) {
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'issues',
                            function ($router, $controller) {
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'integrated-vendors',
                            function ($router, $controller) {
                                $router->get('supported', $controller('getSupported'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
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
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes('proofs');
                        $router->fleetbaseRoutes('purchase-rates');
                        $router->fleetbaseRoutes('routes');
                        $router->fleetbaseRoutes(
                            'service-areas',
                            function ($router, $controller) {
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes('zones');
                        $router->fleetbaseRoutes(
                            'service-quotes',
                            function ($router, $controller) {
                                $router->post('preliminary', $controller('preliminaryQuery'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'service-rates',
                            function ($router, $controller) {
                                $router->get('for-route', $controller('getServicesForRoute'));
                            }
                        );
                        $router->fleetbaseRoutes('tracking-numbers');
                        $router->fleetbaseRoutes('tracking-statuses');
                        $router->fleetbaseRoutes(
                            'vehicles',
                            function ($router, $controller) {
                                $router->get('statuses', $controller('statuses'));
                                $router->get('avatars', $controller('avatars'));
                                $router->get('export', $controller('export'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->fleetbaseRoutes(
                            'vendors',
                            function ($router, $controller) {
                                $router->get('statuses', $controller('statuses'));
                                $router->get('export', $controller('export'));
                                $router->get('facilitators/{id}', $controller('getAsFacilitator'));
                                $router->get('customers/{id}', $controller('getAsCustomer'));
                                $router->delete('bulk-delete', $controller('bulkDelete'));
                            }
                        );
                        $router->group(
                            ['prefix' => 'query'],
                            function () use ($router) {
                                $router->get('customers', 'MorphController@queryCustomersOrFacilitators');
                                $router->get('facilitators', 'MorphController@queryCustomersOrFacilitators');
                            }
                        );
                        $router->group(
                            ['prefix' => 'geocoder'],
                            function ($router) {
                                $router->get('reverse', 'GeocoderController@reverse');
                                $router->get('query', 'GeocoderController@geocode');
                            }
                        );
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
