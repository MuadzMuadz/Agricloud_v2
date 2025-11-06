<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard()->check();
    }

    public function rules(): array
    {
        $isPost = strtolower(request()->method()) === 'post';
        return [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'image' => $isPost
                ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
                : 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama gudang wajib diisi.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar hanya boleh jpg, jpeg, png, atau webp.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
