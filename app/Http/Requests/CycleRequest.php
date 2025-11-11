<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cycle;

class CycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 🔹 CREATE (POST)
        return auth()->guard()->check();
    }


    public function rules(): array
    {
        $isPost = request()->isMethod('post');

        return $isPost
            ? [
                'land_id' => 'required|exists:lands,id',
                'crop_id' => 'required|exists:crops,id',
                'description' => 'nullable|string',
            ]
            : [
                'status_id' => 'nullable|exists:statuses,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'description' => 'nullable|string',
            ];
    }
}
