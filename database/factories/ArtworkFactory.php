<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtworkFactory extends Factory
{
    public function definition(): array
    {
        $token = Str::random(64);

        return ['name' => fake()->words(2, true), 'size' => 16, 'pixels' => array_fill(0, 256, null), 'owner_hash' => hash('sha256', 'test-owner'), 'view_token' => Str::random(64), 'edit_token' => $token, 'edit_hash' => hash('sha256', $token)];
    }
}
