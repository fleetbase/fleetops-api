<?php

namespace Fleetbase\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Http\Requests\CreateServiceAreaRequest;
use Fleetbase\Http\Requests\UpdateServiceAreaRequest;
use Fleetbase\Http\Resources\v1\DeletedResource;
use Fleetbase\Http\Resources\v1\ServiceArea as ServiceAreaResource;
use Fleetbase\Models\ServiceArea;

class ServiceAreaController extends Controller
{
    /**
     * Creates a new Fleetbase ServiceArea resource.
     *
     * @param  \Fleetbase\Http\Requests\CreateServiceAreaRequest  $request
     * @return \Fleetbase\Http\Resources\ServiceArea
     */
    public function create(CreateServiceAreaRequest $request)
    {
        // get request input
        $input = $request->only(['name', 'type', 'status']);

        // make sure company is set
        $input['company_uuid'] = session('company');

        // @todo some geocoding here

        // create the serviceArea
        $serviceArea = ServiceArea::create($input);

        // response the driver resource
        return new ServiceAreaResource($serviceArea);
    }

    /**
     * Updates a Fleetbase ServiceArea resource.
     *
     * @param  string  $id
     * @param  \Fleetbase\Http\Requests\UpdateServiceAreaRequest  $request
     * @return \Fleetbase\Http\Resources\ServiceArea
     */
    public function update($id, UpdateServiceAreaRequest $request)
    {
        // find for the serviceArea
        try {
            $serviceArea = ServiceArea::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'ServiceArea resource not found.',
                ],
                404
            );
        }

        // get request input
        $input = $request->only(['name', 'type', 'status']);

        // @todo some geocoding here

        // update the serviceArea
        $serviceArea->update($input);

        // response the serviceArea resource
        return new ServiceAreaResource($serviceArea);
    }

    /**
     * Query for Fleetbase ServiceArea resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ServiceAreaCollection
     */
    public function query(Request $request)
    {
        $results = ServiceArea::queryFromRequest($request);

        return ServiceAreaResource::collection($results);
    }

    /**
     * Finds a single Fleetbase ServiceArea resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ServiceAreaCollection
     */
    public function find($id, Request $request)
    {
        // find for the serviceArea
        try {
            $serviceArea = ServiceArea::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'ServiceArea resource not found.',
                ],
                404
            );
        }

        // response the serviceArea resource
        return new ServiceAreaResource($serviceArea);
    }

    /**
     * Deletes a Fleetbase ServiceArea resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ServiceAreaCollection
     */
    public function delete($id, Request $request)
    {
        // find for the driver
        try {
            $serviceArea = ServiceArea::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'ServiceArea resource not found.',
                ],
                404
            );
        }

        // delete the serviceArea
        $serviceArea->delete();

        // response the serviceArea resource
        return new DeletedResource($serviceArea);
    }
}
