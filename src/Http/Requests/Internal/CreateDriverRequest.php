<?php

namespace Fleetbase\Http\Requests\Internal;

use Fleetbase\Http\Requests\CreateDriverRequest as CreateDriverApiRequest;
use Illuminate\Validation\Rule;

class CreateDriverRequest extends CreateDriverApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return session('company');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [Rule::requiredIf($this->isMethod('POST'))],
            'email' => ['nullable', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'nullable|string',
            'phone' => ['nullable', Rule::unique('users')->whereNull('deleted_at')],
            'country' => 'nullable|size:2',
            'city' => 'nullable|string',
            // 'vehicle' => 'nullable|exists:vehicles,uuid',
            'status' => 'nullable|string|in:active,inactive',
            // 'vendor' => 'nullable|exists:vendors,public_id',
            'job' => 'nullable|exists:orders,public_id',
        ];
    }
}
