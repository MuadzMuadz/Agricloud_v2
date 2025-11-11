<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload file gambar baru sesuai jenis entity
     *
     * @param \Illuminate\Http\Request $request
     * @param string $entityName Nama entitas (contoh: nama gudang, lahan, template, user)
     * @param string $type Jenis file ('profile', 'land', 'warehouse', 'template')
     * @param string|null $oldImageUrl Gambar lama untuk dihapus (jika update)
     * @return string|null URL gambar publik
     */
    public function upload(Request $request, string $entityName, string $type, ?string $oldImageUrl = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $oldImageUrl;
        }

        $user = Auth::user();

        // Tentukan direktori berdasarkan tipe upload
        $baseDir = match ($type) {
            'profile'   => "user/{$user->username}_{$user->id}",
            'land'      => "user/{$user->username}_{$user->id}/lands",
            'warehouse' => "user/{$user->username}_{$user->id}/warehouses",
            'template'  => "crops",
            default     => "misc",
        };

        $filename = Str::slug($entityName) . '.' . $request->file('image')->getClientOriginalExtension();

        // Hapus file lama kalau ada
        if ($oldImageUrl) {
            $this->delete($oldImageUrl);
        }

        // Simpan file baru ke storage
        $path = $request->file('image')->storeAs($baseDir, $filename, 'public');

        // Kembalikan URL publik
        return Storage::url($path);
    }

    /**
     * Hapus file dari storage berdasarkan URL publik-nya.
     */
    public function delete(?string $imageUrl): void
    {
        if (!$imageUrl) return;

        $path = str_replace('/storage/', 'public/', $imageUrl);

        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }
}
