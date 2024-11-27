<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'model' => 'required|string|max:255',
            'oe_number' => 'nullable|string|max:50',
            'situation' => 'nullable|string|max:50',
            'price_cost' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'price_with_transport' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'balen' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
            'created' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('rules.Name_is_required'),
            // 'email.required' => __('rules.Email_is_required'),
            // 'email.email' => __('rules.Email_must_be_a_valid_email_address'),
            // 'email.unique' => __('rules.This_email_has_already_been_taken'),
            // 'password.required' => __('rules.Password_is_required'),
            // 'password.min' => __('rules.Password_must_be_at_least_8_characters'),
            // 'avatar.image' => __('rules.The_avatar_must_be_an_image_file'),
            // 'avatar.mimes' => __('rules.The_avatar_must_be_a_file_of_type_jpeg_png_jpg_gif'),
            // 'avatar.max' => __('rules.The_avatar_must_not_exceed_2MB'),
        ];
        
    }

    
}
