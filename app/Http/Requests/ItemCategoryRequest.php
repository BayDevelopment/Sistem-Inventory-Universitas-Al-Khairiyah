<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemCategoryRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       // Ambil ID kategori saat ini jika sedang proses update (route parameter-nya biasanya 'category')
        $categoryId = $this->route('category');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('item_categories', 'code')->ignore($categoryId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
