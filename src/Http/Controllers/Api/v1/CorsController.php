<?php

namespace Fleetbase\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
// use Illuminate\Http\Request;

class CorsController extends Controller
{
    /**
     * Determines CORS access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkAccess()
    {
        return response()->json(['status' => 'ok']);
    }
}
