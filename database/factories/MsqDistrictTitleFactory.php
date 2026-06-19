<?php

namespace Database\Factories;

use App\Models\MsqDistrictTitle;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class MsqDistrictTitleFactory extends Factory
{
    protected $model = MsqDistrictTitle::class;

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
