<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional avatar upload validation
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && $this->phone !== null && $this->phone !== '') {
            $this->merge([
                'phone' => (string) $this->phone,
            ]);
        }
    }

    public function messages()
    {
        return [
            'name.required' => __('rules.Name_is_required'),
            'avatar.image' => __('rules.The_avatar_must_be_an_image_file'),
            'avatar.mimes' => __('rules.The_avatar_must_be_a_file_of_type_jpeg_png_jpg_gif'),
            'avatar.max' => __('rules.The_avatar_must_not_exceed_2MB'),
        ];
        
    }

    
}
