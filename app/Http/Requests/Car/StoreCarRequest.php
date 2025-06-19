<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
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
        return [            
            'make_id' => 'required|uuid|exists:makes,id',
            'model_id' => 'required|uuid|exists:models,id',
            'year' => 'required|integer|min:1900|max:2100',
            'cost_price' => 'required|numeric|min:0',
            'public_price' => 'required|numeric|gt:0',
            'transition_cost' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['available', 'sold'])],
            'vin' => 'required|string|min:10|max:20|unique:cars,vin',
            'repair_items' => 'nullable|json', // Input as JSON string
        ];
    }
}
