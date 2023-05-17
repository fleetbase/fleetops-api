<?php

namespace Fleetbase\FleetOps\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateDriverRequest extends FleetbaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        return request()->is('navigator/v1/*') || request()->session()->has('api_credential');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isCreating = $this->isMethod('POST');

        return [
            'name' => [Rule::requiredIf($this->isMethod('POST'))],
            'email' => ['nullable', 'email', $isCreating ? Rule::unique('users')->whereNull('deleted_at') : null],
            'password' => 'nullable|string',
            'phone' => ['nullable', $isCreating ? Rule::unique('users')->whereNull('deleted_at') : null],
            'country' => 'nullable|size:2',
            'city' => 'nullable|string',
            'vehicle' => 'nullable|string|starts_with:vehicle_|exists:drivers,public_id',
            'status' => 'nullable|string|in:active,inactive',
            'vendor' => 'nullable|exists:vendors,public_id',
            'job' => 'nullable|exists:orders,public_id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'email address',
            'phone' => 'phone number',
        ];
    }
}
