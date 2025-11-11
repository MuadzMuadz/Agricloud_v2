<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // nanti bisa dikaitin ke policy kalau udah ada
    }

    public function rules(): array
    {
        $isPost = strtolower(request()->method()) === 'post';
        $isPut = strtolower(request()->method()) === 'put';
        $rules = [
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'area' => 'required|numeric',
        ];

        // kalau update (PUT/PATCH), semua jadi optional
        if ($this->$isPut || $this->$isPost) {
            foreach ($rules as $key => &$rule) {
                $rule = str_replace('required', 'sometimes', $rule);
            }
        }

        return $rules;
    }
}
