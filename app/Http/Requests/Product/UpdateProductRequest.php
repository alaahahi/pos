<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // هنا يمكنك وضع شرط لتحديد إذا ما كان المستخدم مسموح له بتعديل المنتج أم لا
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

    /**
     * Custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('rules.Name_is_required'),
            'model.required' => __('rules.Model_is_required'),
            'price_cost.required' => __('rules.Price_cost_is_required'),
            'quantity.required' => __('rules.Quantity_is_required'),
            'quantity.integer' => __('rules.Quantity_must_be_an_integer'),
            'image.image' => __('rules.The_image_must_be_an_image_file'),
            'image.mimes' => __('rules.The_image_must_be_a_file_of_type_jpg_jpeg_png'),
            'image.max' => __('rules.The_image_must_not_exceed_2MB'),
        ];
    }
}
