<?php

namespace Fleetbase\FleetOps\Http\Requests;



class UpdateTrackingStatusRequest extends FleetbaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return request()->session()->has('api_credential');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'required|string',
            'details' => 'required|string',
            'code' => 'nullable|string'
        ];
    }
}
