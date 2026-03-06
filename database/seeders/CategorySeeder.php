<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'title' => 'Acoustic Guitar',
            'slug' => Str::slug('Acoustic Guitar'),
            'description' => 'Guitar acoustic',
            'parent_id' => 1,
            'status' => true,
            'position' => 2
        ]);

        Category::create([
            'title' => 'Electric Guitar',
            'slug' => Str::slug('Electric Guitar'),
            'description' => 'Guitar điện',
            'parent_id' => 1,
            'status' => true,
            'position' => 3
        ]);

        Category::create([
            'title' => 'Guitar Accessories',
            'slug' => Str::slug('Guitar Accessories'),
            'description' => 'Phụ kiện guitar',
            'status' => true,
            'position' => 4
        ]);

        Category::create([
            'title' => 'Guitar Lessons',
            'slug' => Str::slug('Guitar Lessons'),
            'description' => 'Khóa học guitar',
            'status' => true,
            'position' => 5
        ]);
    }
}
