<?php

namespace Fleetbase\FleetOps\Jobs;

use Fleetbase\FleetOps\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;

/**
 * Class SimulateDrivingRoute
 * Simulates a driving route for a given driver by dispatching events at each waypoint.
 */
class SimulateDrivingRoute implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Driver The driver for whom the route is being simulated.
     */
    public Driver $driver;

    /**
     * @var array The waypoints that make up the route.
     */
    public array $waypoints = [];

    /**
     * Create a new job instance.
     *
     * @param \Fleetbase\FleetOps\Models\Driver $driver The driver for whom the route is being simulated.
     * @param array $waypoints The waypoints that make up the route.
     */
    public function __construct(Driver $driver, array $waypoints = [])
    {
        $this->driver = $driver->withoutRelations();
        $this->waypoints = $waypoints;
    }

    /**
     * Execute the job.
     * Dispatches an event for each waypoint, simulating the driver's movement along the route.
     */
    public function handle(): void
    {
        $delayInSeconds = 0;

        foreach ($this->waypoints as $waypoint) {
            Queue::later(
                now()->addSeconds($delayInSeconds),
                new SimulateWaypointReached($this->driver, $waypoint)
            );

            $delayInSeconds += rand(5, 30); // Adjust this to control the pace
        }
    }
}
