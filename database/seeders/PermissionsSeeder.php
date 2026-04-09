<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'product',
            'category',
            'account',
            'role'
        ];

        $actions = [
            'view',
            'create',
            'update',
            'delete'
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => $module . ' ' . $action
                ]);
            }
        }
    }
}
