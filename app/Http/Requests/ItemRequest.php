<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard()->check();
    }

    public function rules(): array
    {
        $isPost = request()->method('post');
        $itemId = request()->route('id');
        $warehouseId = request()->route('warehouse_id') ?? request()->input('warehouse_id');

        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'name')
                    ->where('warehouse_id', $warehouseId)
                    ->ignore($itemId),
            ],
            'unit' => ['required', 'string', 'max:20'],
            'stock' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Warehouse wajib dipilih.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'name.required' => 'Nama item wajib diisi.',
            'name.unique' => 'Item dengan nama ini sudah ada di gudang ini.',
            'unit.required' => 'Satuan wajib diisi.',
            'stock.required' => 'Stok wajib diisi.',
        ];
    }
}
