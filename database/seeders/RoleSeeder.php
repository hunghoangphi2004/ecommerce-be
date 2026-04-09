<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Role::insert([
            [
                'name' => 'QUản trị viên',
                'description' => 'Đủ quyền'
            ],
            [
                'name' => 'Quản lý sản phẩm',
                'description' => 'Quản lý sản phẩm'
            ],
            [
                'name' => 'Quản lý truy cập',
                'description' => 'Quản lý tài khoản'
            ]
        ]);
    }
}
