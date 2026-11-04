<?php

return [
    'required' => ':attribute wajib diisi.', 'required_without' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.', 'integer' => ':attribute harus berupa bilangan bulat.',
    'array' => ':attribute harus berupa daftar.', 'list' => ':attribute harus berupa daftar berurutan.',
    'in' => ':attribute tidak valid.', 'present' => ':attribute wajib disertakan.',
    'regex' => ':attribute tidak valid.', 'distinct' => ':attribute tidak boleh berulang.',
    'prohibited' => ':attribute tidak boleh diubah.',
    'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.', 'array' => ':attribute minimal :min item.'],
    'max' => ['numeric' => ':attribute maksimal :max.', 'string' => ':attribute maksimal :max karakter.', 'array' => ':attribute maksimal :max item.'],
    'size' => ['numeric' => ':attribute harus :size.', 'array' => ':attribute harus berisi :size item.'],
    'attributes' => ['name' => 'Nama karya', 'size' => 'Ukuran grid', 'pixels' => 'Pixel', 'changes' => 'Perubahan'],
];
