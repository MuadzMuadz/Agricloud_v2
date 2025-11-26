<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard()->check();
    }

    public function rules(): array
    {
        return [
            'warehouse_id'   => 'required|exists:warehouses,id',
            'item_id'        => 'required|exists:items,id',
            'movetype_id'    => 'required|exists:move_types,id', // Ubah ke move_types
            'status_id'      => 'nullable|exists:statuses,id',
            'land_dest'      => 'nullable|exists:lands,id',
            'warehouse_dest' => 'nullable|exists:warehouses,id',
            'quantity'       => 'required|integer|min:1',
            'note'           => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Gudang asal wajib diisi.',
            'item_id.required'      => 'Item wajib diisi.',
            'movetype_id.required'  => 'Jenis pergerakan wajib diisi.',
            'movetype_id.exists'    => 'Jenis pergerakan yang dipilih tidak valid.', // Tambahkan pesan untuk exists
            'quantity.required'     => 'Jumlah barang wajib diisi.',
            'quantity.min'          => 'Jumlah minimal 1.',
        ];
    }
}