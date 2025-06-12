<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelId = $this->route('model') ? $this->route('model')->id : null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('models')->where(function ($query) {
                    return $query->where('make_id', $this->input('make_id', $this->route('model')->make_id));
                })->ignore($modelId),
            ],
            'make_id' => 'sometimes|required|uuid|exists:makes,id',
        ];
    }
}
