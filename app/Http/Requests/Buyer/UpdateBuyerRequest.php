<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateBuyerRequest extends FormRequest
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
        $buyerId = $this->route('buyer'); // Get the buyer ID from the route

        return [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|min:7|max:20',
            'address' => 'nullable|string',
            'car_ids' => 'sometimes|required|array|min:1',
            'car_ids.*' => 'required|uuid|exists:cars,id',
        ];
    }
}
