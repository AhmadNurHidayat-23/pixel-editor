<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $size = in_array((int) $this->input('size'), [16, 32], true) ? (int) $this->input('size') : 16;

        return ['name' => 'required|string|max:100', 'size' => 'required|integer|in:16,32', 'pixels' => ['required', 'array', 'list', 'size:'.($size * $size)], 'pixels.*' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/D']];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'max' => 'Nama maksimal 100 karakter.', 'size' => 'Jumlah pixel tidak sesuai ukuran grid.', 'in' => 'Ukuran harus 16 atau 32.', 'regex' => 'Warna harus berupa kode heksadesimal enam digit.', 'list' => 'Pixel harus berupa daftar berurutan.'];
    }
}
