<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Hasan',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'balance' => 0.00,
            'room_number' => null,
            'building' => null
        ]);

        $supplier = User::create([
            'name' =>  'Zain',
            'email' => 'supplier@test.com',
            'password' => Hash::make('password1234'),
            'role' => 'supplier',
            'balance' => 500.00,
            'room_number' => 'B-12',
            'building' => 'building 15',
        ]);

        User::create([
            'name' => 'Rawan',
            'email' => 'customer@test.com',
            'password' => Hash::make('password12345'),
            'role' => 'customer',
            'balance' => 1000.00,
            'room_number' => '500.00',
            'building' => 'building 1',
        ]);

        $foodCategory = Category::create([
            'name' => 'Food',
        ]);

        $drinksCategory = Category::create([
            'name' => 'Drinks',
        ]);

        $preservesCategory = Category::create([
            'name' => 'Preserves',
        ]);

        $entreesCategory = Category::create([
            'name' => 'Entrees',
        ]);

        Product::create([
            'name' => 'Premium Rice',
            'description' => 'High quality basmati rice 1kg',
            'price' => 5.50,
            'stock' => 50,
            'category_id' => $foodCategory->id,
            'supplier_id' => $supplier->id,
        ]);

        Product::create([
            'name' => 'Pasta',
            'description' => 'Italian style pasta 500g',
            'price' => 2.25,
            'stock' => 100,
            'category_id' => $foodCategory->id,
            'supplier_id' => $supplier->id,
        ]);

        Product::create([
            'name' => 'Orange Juice',
            'description' => 'Fresh natural orange juice 1L',
            'price' => 3.00,
            'stock' => 30,
            'category_id' => $drinksCategory->id,
            'supplier_id' => $supplier->id,
        ]);

        Product::create([
            'name' => 'Canned Tuna',
            'description' => 'Tuna in sunflower oil',
            'price' => 4.20,
            'stock' => 75,
            'category_id' => $preservesCategory->id,
            'supplier_id' => $supplier->id,
        ]);
    }
}
