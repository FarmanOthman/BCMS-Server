<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBuyerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */    public function authorize(): bool
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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:7|max:20',
            'address' => 'nullable|string',
            'car_ids' => 'required|array|min:1',
            'car_ids.*' => 'required|uuid|exists:cars,id',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // You can add created_by and updated_by here if you want them to be part of the validated data automatically
        // However, it's often cleaner to set these in the controller after validation.
    }
}
