<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'icon' => 'tv'],
            ['name' => 'Mobiles', 'icon' => 'smartphone'],
            ['name' => 'Laptops', 'icon' => 'laptop'],
            ['name' => 'Fashion', 'icon' => 'shirt'],
            ['name' => 'Vehicles', 'icon' => 'car'],
            ['name' => 'Property', 'icon' => 'home'],
            ['name' => 'Furniture', 'icon' => 'armchair'],
            ['name' => 'Jobs', 'icon' => 'briefcase'],
            ['name' => 'Services', 'icon' => 'wrench'],
            ['name' => 'Books', 'icon' => 'book-open'],
            ['name' => 'Sports', 'icon' => 'trophy'],
            ['name' => 'Pets', 'icon' => 'dog'],
            ['name' => 'Other', 'icon' => 'box'],
        ];

        foreach ($categories as $index => $cat) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'order' => $index + 1,
                ]
            );
        }
    }
}
