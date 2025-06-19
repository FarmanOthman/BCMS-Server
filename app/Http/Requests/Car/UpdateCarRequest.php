<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the route middleware, so always return true here
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $carId = $this->route('car'); // Get the car ID from the route parameter

        return [            
            'make_id' => 'sometimes|required|uuid|exists:makes,id',
            'model_id' => 'sometimes|required|uuid|exists:models,id',
            'year' => 'sometimes|required|integer|min:1900|max:2100',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'public_price' => 'sometimes|required|numeric|gt:0',
            'transition_cost' => 'nullable|numeric|min:0',
            'status' => ['sometimes', 'required', Rule::in(['available', 'sold'])],
            'vin' => 'sometimes|required|string|min:10|max:20|unique:cars,vin,' . $carId,
            'repair_items' => 'nullable|json',
        ];
    }
}
