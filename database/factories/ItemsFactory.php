<?php

namespace Database\Factories;

use App\Models\Categories;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemsFactory extends Factory
{
    public function definition(): array
    {
        $warehouseId = Warehouse::inRandomOrder()->value('id');
        $categoryId = Categories::inRandomOrder()->value('id');

        return [
            'warehouse_id' => $warehouseId,
            'category_id' => $categoryId, // ✅ pastikan kategori valid
            'name' => ucfirst($this->faker->word()),
            'stock' => $this->faker->numberBetween(10, 200),
            'unit' => $this->faker->randomElement(['kg', 'liter', 'pcs']),
        ];
    }
}
