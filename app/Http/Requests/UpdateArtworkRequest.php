<?php

namespace App\Http\Requests;

use App\Services\ArtworkAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(ArtworkAccess::class)->editable($this);
    }

    public function rules(): array
    {
        $artwork = app(ArtworkAccess::class)->resolve($this);

        return ['name' => 'sometimes|required|string|max:100', 'size' => 'missing', 'pixels' => 'missing',
            'changes' => 'required_without:name|array|list|max:1024', 'changes.*' => 'array:index,color',
            'changes.*.index' => 'required|integer|distinct|min:0|max:'.($artwork->size * $artwork->size - 1),
            'changes.*.color' => ['present', 'nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/D']];
    }

    public function messages(): array
    {
        return ['required_without' => 'Nama atau perubahan pixel wajib diisi.', 'max' => ':attribute melebihi batas yang diizinkan.', 'regex' => 'Kode warna tidak valid.', 'missing' => 'Ukuran karya tersimpan tidak dapat diubah. Buat kanvas baru.', 'distinct' => 'Indeks pixel tidak boleh berulang.', 'present' => 'Warna pixel wajib disertakan.'];
    }
}
