<?php

namespace Fleetbase\FleetOps\Jobs;

use Fleetbase\FleetOps\Events\DriverSimulatedLocationChanged;
use Fleetbase\FleetOps\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Grimzy\LaravelMysqlSpatial\Types\Point;

/**
 * Class SimulateWaypointReached
 * Simulates the reaching of a waypoint for a given driver by dispatching an event.
 */
class SimulateWaypointReached implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Fleetbase\FleetOps\Models\Driver The driver for whom the waypoint is being simulated.
     */
    public Driver $driver;

    /**
     * @var \Grimzy\LaravelMysqlSpatial\Types\Point The waypoint that the driver is simulated to have reached.
     */
    public Point $waypoint;

    /**
     * Create a new job instance.
     *
     * @param Driver $driver The driver for whom the waypoint is being simulated.
     * @param mixed $waypoint The waypoint that the driver is simulated to have reached.
     */
    public function __construct(Driver $driver, Point $waypoint)
    {
        $this->driver = $driver;
        $this->waypoint = $waypoint;
    }

    /**
     * Execute the job.
     * Dispatches an event to notify that the driver has reached the simulated waypoint.
     *
     * @return void
     */
    public function handle(): void
    {
        event(new DriverSimulatedLocationChanged($this->driver, $this->waypoint));
    }
}
