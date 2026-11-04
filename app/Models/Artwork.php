<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['owner_hash', 'edit_hash', 'edit_token'];

    protected function casts(): array
    {
        return ['pixels' => 'array', 'edit_token' => 'encrypted', 'size' => 'integer', 'version' => 'integer'];
    }

    public function state(): array
    {
        return $this->only(['name', 'size', 'pixels', 'version']);
    }
}
