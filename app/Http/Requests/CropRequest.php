<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CropRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guard()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPost = $this->isMethod('post');
        $cropId = $this->route('crop');

        return [
            'name' => $isPost 
                ? 'required|string|max:255|unique:crops,name'
                : 'sometimes|required|string|max:255|unique:crops,name,' . $cropId,
            'description' => 'nullable|string|max:1000',
            'image' => $isPost
                ? 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                : 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tanaman harus diisi',
            'name.string' => 'Nama tanaman harus berupa teks',
            'name.max' => 'Nama tanaman maksimal 255 karakter',
            'name.unique' => 'Nama tanaman sudah digunakan',
            'description.string' => 'Deskripsi harus berupa teks',
            'description.max' => 'Deskripsi maksimal 1000 karakter',
            'image.required' => 'Gambar tanaman harus diisi',
            'image.image' => 'File harus berupa gambar',
            'image.mimes' => 'Gambar harus berformat: jpeg, png, jpg, gif, atau webp',
            'image.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama tanaman',
            'description' => 'deskripsi',
            'image' => 'gambar tanaman',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // ✅ Optional: bersihkan data sebelum validation
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name)
            ]);
        }
        
        if ($this->has('description')) {
            $this->merge([
                'description' => trim($this->description) ?: null
            ]);
        }
    }
}